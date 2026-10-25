<?php
/**
 * REST API Endpoints for Chat and TTS
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_API_Endpoint {

	const NAMESPACE = 'gemini-avatar/v1';

	/**
	 * Register REST routes
	 */
	public static function register_routes() {
		// Chat endpoint
		register_rest_route( self::NAMESPACE, '/chat', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'handle_chat' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'args'                => array(
				'question'       => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'generate_audio' => array(
					'required'          => false,
					'type'              => 'boolean',
					'default'           => false,
				),
				'history'        => array(
					'required'          => false,
					'type'              => 'array',
					'default'           => array(),
				),
				'lang'           => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => 'fi',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Replay / Generate TTS endpoint
		register_rest_route( self::NAMESPACE, '/tts', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'handle_tts' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'args'                => array(
				'text' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		) );
	}

	/**
	 * Permission and rate limiting callback
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public static function check_permissions( $request ) {
		// Verify WordPress REST Nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden_nonce',
				'Virheellinen tai vanhentunut suojaustunniste (nonce). Lataa sivu uudelleen.',
				array( 'status' => 403 )
			);
		}

		// Server-side rate limiting per anonymized IP hash
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		$salt = wp_salt( 'auth' );
		$ip_hash = hash( 'sha256', $ip . $salt );

		$limit_min  = absint( Gemini_Avatar_Settings::get( 'rate_limit_per_minute', 5 ) );
		$limit_hour = absint( Gemini_Avatar_Settings::get( 'rate_limit_per_hour', 30 ) );

		// Minute check
		$min_transient_key = 'gemini_av_rpm_' . substr( $ip_hash, 0, 24 );
		$count_min = (int) get_transient( $min_transient_key );
		if ( $count_min >= $limit_min ) {
			return new WP_Error(
				'rate_limit_minute',
				'Olet lähettänyt liian monta viestiä minuutin sisällä. Odota hetki ennen seuraavaa kysymystä.',
				array( 'status' => 429 )
			);
		}

		// Hour check
		$hour_transient_key = 'gemini_av_rph_' . substr( $ip_hash, 0, 24 );
		$count_hour = (int) get_transient( $hour_transient_key );
		if ( $count_hour >= $limit_hour ) {
			return new WP_Error(
				'rate_limit_hour',
				'Käyttörajasi tälle tunnille on täyttynyt. Yritä myöhemmin uudelleen.',
				array( 'status' => 429 )
			);
		}

		// Increment rate limit counters
		set_transient( $min_transient_key, $count_min + 1, 60 );
		set_transient( $hour_transient_key, $count_hour + 1, 3600 );

		return true;
	}

	/**
	 * Handle incoming chat request
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_chat( $request ) {
		$question       = trim( (string) $request->get_param( 'question' ) );
		$generate_audio = (bool) $request->get_param( 'generate_audio' );
		$history        = (array) $request->get_param( 'history' );
		$lang           = sanitize_key( (string) $request->get_param( 'lang' ) );

		if ( ! in_array( $lang, array( 'fi', 'en', 'ar' ), true ) ) {
			$lang = 'fi';
		}

		$max_chars = absint( Gemini_Avatar_Settings::get( 'max_input_chars', 400 ) );
		if ( empty( $question ) ) {
			$err = ( 'ar' === $lang ) ? 'لا يمكن أن يكون السؤال فارغاً.' : ( ( 'en' === $lang ) ? 'Question cannot be empty.' : 'Kysymys ei voi olla tyhjä.' );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 400 );
		}

		if ( mb_strlen( $question, 'UTF-8' ) > $max_chars ) {
			$err = ( 'ar' === $lang ) ? sprintf( 'الحد الأقصى لطول السؤال هو %d حرفاً.', $max_chars ) : ( ( 'en' === $lang ) ? sprintf( 'Maximum question length is %d characters.', $max_chars ) : sprintf( 'Kysymyksen enimmäispituus on %d merkkiä.', $max_chars ) );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 400 );
		}

		// Check if master widget is enabled
		if ( ! Gemini_Avatar_Settings::get( 'enable_widget', 1 ) ) {
			$err = ( 'ar' === $lang ) ? 'المساعد الذكي معطل مؤقتاً.' : ( ( 'en' === $lang ) ? 'AI assistant is temporarily disabled.' : 'AI-avustaja on tilapäisesti pois käytöstä.' );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 503 );
		}

		// 1. Retrieve relevant knowledge excerpts in the requested language
		$context = Gemini_Avatar_Knowledge_Base::get_prompt_context( $question, 6, $lang );

		// 2. Generate text answer via Gemini in the requested language
		$answer_result = Gemini_Avatar_Gemini_Client::generate_answer(
			$question,
			$history,
			$context['context_text'],
			$lang
		);

		if ( empty( $answer_result['success'] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $answer_result['error'] ?? 'Vastauksen luonti epäonnistui.',
			), 500 );
		}

		$parsed_buttons = self::parse_action_buttons_from_text( $raw_answer );
		$buttons        = $parsed_buttons['buttons'];
		$clean_answer   = $parsed_buttons['clean_text'];

		// Fallback contextual action buttons if none were generated by Gemini
		if ( empty( $buttons ) ) {
			$buttons = self::generate_contextual_fallback_buttons( $question . ' ' . $clean_answer, $lang );
		}

		$audio_data  = null;
		$audio_mime  = null;
		$audio_error = null;

		// 3. Generate speech if requested and enabled using clean text
		$speech_enabled = (bool) Gemini_Avatar_Settings::get( 'enable_speech', 1 );
		if ( $generate_audio && $speech_enabled ) {
			$tts_result = Gemini_Avatar_Gemini_Client::generate_tts( $clean_answer );
			if ( ! empty( $tts_result['success'] ) ) {
				$audio_data = $tts_result['audio_base64'];
				$audio_mime = $tts_result['mime_type'];
			} else {
				$audio_error = $tts_result['error'] ?? 'Puhesynteesi epäonnistui';
			}
		}

		return new WP_REST_Response( array(
			'success'      => true,
			'text'         => $clean_answer,
			'buttons'      => $buttons,
			'sources'      => $context['sources'],
			'audio_base64' => $audio_data,
			'mime_type'    => $audio_mime,
			'audio_error'  => $audio_error,
		), 200 );
	}

	/**
	 * Handle direct TTS generation / replay
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_tts( $request ) {
		$text = trim( (string) $request->get_param( 'text' ) );
		if ( empty( $text ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Teksti puuttuu.',
			), 400 );
		}

		$tts_result = Gemini_Avatar_Gemini_Client::generate_tts( $text );
		if ( ! empty( $tts_result['success'] ) ) {
			return new WP_REST_Response( array(
				'success'      => true,
				'audio_base64' => $tts_result['audio_base64'],
				'mime_type'    => $tts_result['mime_type'],
			), 200 );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'error'   => $tts_result['error'] ?? 'Puhesynteesi epäonnistui.',
		), 500 );
	}

	/**
	 * Parse and whitelist action buttons from raw response text
	 *
	 * @param string $raw_text
	 * @return array
	 */
	public static function parse_action_buttons_from_text( $raw_text ) {
		$buttons   = array();
		$seen_btns = array();

		// Extract [BUTTON:label|target] from response
		if ( preg_match_all( '/\[BUTTON:\s*([^\|\]]+)\s*\|\s*([^\]]+)\s*\]/iu', $raw_text, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$label  = sanitize_text_field( trim( $m[1] ) );
				$target = sanitize_text_field( trim( $m[2] ) );

				$is_valid = false;
				$type     = 'link';

				if ( '#paypal-advertising' === $target ) {
					$is_valid = true;
					$type     = 'modal';
				} elseif ( strpos( $target, 'https://marketplace.atlassian.com/apps/' ) === 0 ) {
					$is_valid = true;
					$type     = 'link';
				} elseif ( preg_match( '#^https?://(www\.)?i4ware\.fi(/.*)?$#i', $target ) ) {
					$is_valid = true;
					$type     = 'link';
				}

				if ( $is_valid ) {
					$key = $label . '|' . $target;
					if ( ! isset( $seen_btns[ $key ] ) ) {
						$seen_btns[ $key ] = true;
						$buttons[] = array(
							'label'  => $label,
							'target' => $target,
							'type'   => $type,
						);
					}
				}
			}
		}

		// Clean answer text by stripping [BUTTON:...] tags
		$clean_text = trim( preg_replace( '/\[BUTTON:[^\]]+\]/iu', '', $raw_text ) );

		return array(
			'clean_text' => $clean_text,
			'buttons'    => array_slice( $buttons, 0, 3 ),
		);
	}

	/**
	 * Generate contextual fallback action buttons based on topic
	 *
	 * @param string $text
	 * @param string $lang
	 * @return array
	 */
	public static function generate_contextual_fallback_buttons( $text, $lang = 'fi' ) {
		$buttons = array();
		$lower_t = mb_strtolower( (string) $text, 'UTF-8' );

		if ( preg_match( '/\b(railroad|rautatie|healthcare|terveydenhuolto|cities|kaupungit)\b/iu', $lower_t ) ) {
			if ( preg_match( '/\b(railroad|rautatie)\b/iu', $lower_t ) ) {
				$buttons[] = array(
					'label'  => 'Timesheet for Railroad',
					'target' => 'https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad',
					'type'   => 'link',
				);
			}
			if ( preg_match( '/\b(healthcare|terveydenhuolto)\b/iu', $lower_t ) ) {
				$buttons[] = array(
					'label'  => 'Timesheet for Healthcare',
					'target' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare',
					'type'   => 'link',
				);
			}
			if ( preg_match( '/\b(cities|kaupungit)\b/iu', $lower_t ) ) {
				$buttons[] = array(
					'label'  => 'Timesheet for Cities',
					'target' => 'https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities',
					'type'   => 'link',
				);
			}
		} elseif ( preg_match( '/\b(jira\w*|timesheet\w*|marketplace\w*|atlassian\w*)\b|جايرا|تايم شيت/iu', $lower_t ) ) {
			$buttons[] = array(
				'label'  => 'Timesheet for Railroad',
				'target' => 'https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad',
				'type'   => 'link',
			);
			$buttons[] = array(
				'label'  => 'Timesheet for Healthcare',
				'target' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare',
				'type'   => 'link',
			);
		}

		if ( preg_match( '/\b(mainos\w*|mainok\w*|mainospaik\w*|kuukausitila\w*|kertamak\w*|banner\w*|advertising|subscription\w*)\b|إعلان|اعلان|اشتراك|بانر|مساحة إعلانية/iu', $lower_t ) ) {
			$lbl = ( 'ar' === $lang ) ? 'شراء مساحة إعلانية' : ( ( 'en' === $lang ) ? 'Buy advertising space' : 'Osta mainospaikka' );
			$buttons[] = array(
				'label'  => $lbl,
				'target' => '#paypal-advertising',
				'type'   => 'modal',
			);
		}

		if ( preg_match( '/\b(tuotte\w*|tuote\w*|product\w*)\b|منتج|منتجات/iu', $lower_t ) ) {
			$prod_url = ( 'ar' === $lang ) ? 'https://www.i4ware.fi/ar/%d8%a7%d9%84%d9%85%d9%86%d8%aa%d8%ac%d8%a7%d8%aa/' : ( ( 'en' === $lang ) ? 'https://www.i4ware.fi/en/products/' : 'https://www.i4ware.fi/tuotteet/' );
			$prod_lbl = ( 'ar' === $lang ) ? 'استعراض المنتجات' : ( ( 'en' === $lang ) ? 'View products' : 'Katso tuotteet' );
			$buttons[] = array(
				'label'  => $prod_lbl,
				'target' => $prod_url,
				'type'   => 'link',
			);
		}

		if ( preg_match( '/\b(tarjous\w*|tarjouspyyn\w*|yhteystie\w*|contact\w*|tiimi\w*|quote\w*)\b|تواصل|فريق|عرض أسعار/iu', $lower_t ) ) {
			$contact_url = ( 'ar' === $lang ) ? 'https://www.i4ware.fi/ar/%d8%b4%d8%b1%d9%83%d8%a9/%d8%a8%d9%8a%d8%a7%d9%86%d8%a7%d8%aa-%d8%a7%d9%84%d8%a7%d8%aa%d8%b5%d8%a7%d9%84-%d8%a7%d9%84%d9%81%d8%b1%d9%8a%d9%82/' : ( ( 'en' === $lang ) ? 'https://www.i4ware.fi/en/company/contact-us-team/' : 'https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/' );
			$contact_lbl = ( 'ar' === $lang ) ? 'الاتصال والفريق' : ( ( 'en' === $lang ) ? 'Contact us & Team' : 'Yhteystiedot & Tiimi' );
			$buttons[] = array(
				'label'  => $contact_lbl,
				'target' => $contact_url,
				'type'   => 'link',
			);
		}

		if ( preg_match( '/\b(palvelu\w*|webhotelli\w*|hosting\w*|kehity\w*|development\w*|service\w*)\b|خدمة|خدمات|استضافة/iu', $lower_t ) ) {
			$serv_url = ( 'ar' === $lang ) ? 'https://www.i4ware.fi/ar/%d8%a7%d9%84%d8%ae%d8%af%d9%85%d8%a7%d8%aa/' : ( ( 'en' === $lang ) ? 'https://www.i4ware.fi/en/services/' : 'https://www.i4ware.fi/palvelut/' );
			$serv_lbl = ( 'ar' === $lang ) ? 'استعراض الخدمات' : ( ( 'en' === $lang ) ? 'View services' : 'Katso palvelut' );
			$buttons[] = array(
				'label'  => $serv_lbl,
				'target' => $serv_url,
				'type'   => 'link',
			);
		}

		// Keep at most 2 distinct buttons
		return array_slice( $buttons, 0, 2 );
	}
}
