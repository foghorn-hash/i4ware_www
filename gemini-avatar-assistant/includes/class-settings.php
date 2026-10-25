<?php
/**
 * Settings and Configuration Manager
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Settings {

	const OPTION_KEY = 'gemini_avatar_settings';

	/**
	 * Default settings values
	 *
	 * @return array
	 */
	public static function get_defaults() {
		// Try to find default profile photo from front page or uploads
		$default_avatar = '';
		$default_avatar_id = 0;
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 && function_exists( 'get_field' ) ) {
			$photo = get_field( 'cv_about_photo', $front_id );
			if ( is_array( $photo ) && ! empty( $photo['url'] ) ) {
				$default_avatar = $photo['url'];
				$default_avatar_id = ! empty( $photo['ID'] ) ? (int) $photo['ID'] : 0;
			}
		}

		if ( empty( $default_avatar ) ) {
			$upload_dir = wp_upload_dir();
			$default_avatar = $upload_dir['baseurl'] . '/2026/02/mattikiviharju5-600-600.jpg';
		}

		// Privacy policy URL
		$privacy_url = '';
		if ( ! empty( $GLOBALS['wp_rewrite'] ) && function_exists( 'get_privacy_policy_url' ) ) {
			$privacy_url = get_privacy_policy_url();
		}
		if ( empty( $privacy_url ) ) {
			$privacy_url = home_url( '/privacy-policy/' );
		}

		return array(
			// API & Models
			'api_key'                 => '',
			'text_model'              => 'gemini-2.5-flash',
			'tts_model'               => 'gemini-2.5-flash',
			'tts_voice'               => 'Puck',

			// Assistant Identity & Appearance
			'assistant_name'          => 'i4ware Software AI-avustaja',
			'greeting_fi'             => 'Hei! Olen i4ware Softwaren tekoälyavustaja. Autan sinua tutustumaan tuotteisiimme, palveluihimme, hintoihimme ja tilausvaihtoehtoihimme, kuten mainospaikkoihin, kuukausitilauksiin ja Atlassian Marketplace -sovelluksiin. Kuinka voin auttaa?',
			'greeting_en'             => 'Hello! I am i4ware Software’s AI assistant. I can help you understand our products, services, pricing, and ordering options, such as advertising spaces, monthly subscriptions, and Atlassian Marketplace apps. How can I help you?',
			'greeting_ar'             => 'مرحباً! أنا المساعد الذكي لموقع i4ware Software. يسعدني مساعدتك في استكشاف منتجاتنا، وخدماتنا، وأسعارنا، وخيارات الطلب مثل المساحات الإعلانية والاشتراكات الشهرية وتطبيقات Atlassian Marketplace. كيف يمكنني مساعدتك اليوم؟',
			'default_language'        => 'fi',
			'avatar_type'             => 'photo', // 'photo' or 'svg'
			'avatar_image_url'        => $default_avatar,
			'avatar_image_id'         => $default_avatar_id,

			// Knowledge Base
			'included_post_types'     => array(
				'page',
				'post',
			),
			'included_pages'          => array(),
			'approved_extra_knowledge'=> "i4ware Software on suomalainen ohjelmistoalan yritys (Y-tunnus: 2739594-6, ALV-tunniste: FI27395946, Kasarmikatu 15, 13100 Hämeenlinna, verkkosivusto: https://i4ware.fi, sähköposti: info@i4ware.fi, puhelin: +358 40 820 0691).\n\n"
				. "MITÄ I4WARELTA VOI OSTAA JA TILATA:\n"
				. "1. MAINOSPAIKAT I4WAREN VERKKOSIVUSTOLLA (VASTIKKEELLINEN MAINOSPALVELU):\n"
				. "- Asiakas ostaa todellisen mainospaikan sovitussa muodossa (logo linkillä, banneri linkillä tai tekstimainos esittelykappaleella) ja sovitussa sijoittelussa (alatunniste & kumppanit, sivupalkki tai pääsivu).\n"
				. "- Kertamaksu: Määräaikainen 30 vuorokauden näkyvyys maksun ja aineiston hyväksymisestä. Kertamaksu ei tarkoita pysyvää mainospaikkaa eikä uusiudu automaattisesti.\n"
				. "- Kuukausitilaus: Toistaiseksi jatkuva kuukausittainen näkyvyys, joka veloitetaan 1 kuukauden jaksoissa. Kuukausitilauksen voi peruuttaa milloin tahansa helposti omalta PayPal-tililtä ilman lisäkuluja tai irtisanomisaikaa. Peruutuksen jälkeen näkyvyys jatkuu maksetun jakson loppuun.\n"
				. "- Mainospaikat tilataan verkkosivuston PayPal-kassalomakkeella (https://www.i4ware.fi/#paypal-advertising). Mainospaikka on vastikkeellinen kaupallinen palvelu, ei lahjoitus tai vastikkeeton tuki.\n"
				. "- Verotus: Suomen arvonlisävero 25,5 % (AVL 1 §) lisätään kotimaan ostoihin. EU-yrityksille ALV 0 % käännetty verovelvollisuus (edellyttää voimassa olevaa VIES-tarkistettua ALV-tunnistetta). EU:n ulkopuolinen myynti ALV 0 % vienti. Verot lasketaan automaattisesti kassalla asiakkaan antamien tietojen perusteella.\n"
				. "- PDF-kuitti: Jokaisesta onnistuneesta maksusta lähetetään PayPalin oman kuitin lisäksi i4ware Softwaren virallinen PDF-kuitti asiakkaan sähköpostiin i4waren VPS-palvelimelta autentikoidun TLS SMTP -yhteyden kautta heti, kun PayPalin webhook vahvistaa maksun.\n\n"
				. "2. ATLASSIAN MARKETPLACE -SOVELLUKSET:\n"
				. "- i4ware Softwaren Timesheet for Jira -sovellukset Atlassian Marketplacessa:\n"
				. "  * Timesheet for Jira for Railroad: https://marketplace.atlassian.com/apps/3844312374/timesheet-for-jira-for-railroad (sivuston esittelysivu: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-railroad/)\n"
				. "  * Timesheet for Jira for Healthcare: https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira-for-healthcare (sivuston esittelysivu: https://www.i4ware.fi/tuotteet/timesheet-for-jira-for-healthcare/)\n"
				. "  * Timesheet for Jira for Cities: https://marketplace.atlassian.com/apps/942289713/timesheet-for-jira-for-cities\n"
				. "- Ammattitason työajanseuranta, tuntiraportointi ja Excel-tyylinen nopea kirjausruudukko Jira Cloud ja Data Center -alustoille.\n"
				. "- TÄRKEÄÄ: Kaikki Timesheet for Jira -sovellukset ostetaan ja lisensoidaan suoraan Atlassian Marketplacesta. Niitä EI osteta i4waren omalla PayPal-lomakkeella!\n\n"
				. "3. WEB-HOTELLIPALVELU:\n"
				. "- Luotettava ja turvallinen web-hotelli WordPress- ja PHP-sovelluksille Leasewebin huippuluokan datakeskuksessa Amsterdamissa (esittelysivu: https://www.i4ware.fi/palvelut/web-hotellipalvelu-wordpress-ja-open-source-php-sovellusten-hostaus/).\n"
				. "- Hinta: 20 € / kk + ALV 25,5 % (laskutusväli 1 vuosi, maksuehto 14 pv netto).\n"
				. "- Sisältää rajattomat MySQL- ja PostgreSQL-tietokannat, rajattomat sähköpostit, SpamAssassin & Abusix AI -roskapostisuodatuksen, ClamAV-virusskannauksen ja Let’s Encrypt SSL-sertifikaatin (SSL Labs Grade A/A+).\n"
				. "- Ei luovuteta FTP-tunnuksia tietoturvasyistä; asennukset hoidetaan turvallisesti puolestasi.\n"
				. "- Tilaaminen tapahtuu ottamalla yhteyttä tai pyytämällä tarjous: info@i4ware.fi tai puhelin +358 40 820 0691.\n\n"
				. "4. RÄÄTÄLÖIDYT OHJELMISTOT, WORDPRESS-KEHITYS JA ATLASSIAN-KONSULTOINTI:\n"
				. "- Räätälöidyt verkkosovellukset (Laravel, React, Node.js), WordPress-kehitys (https://www.i4ware.fi/palvelut/wordpress-kehitys/), lisäosat ja teemat, järjestelmäintegraatiot (ERP, CRM, API), Atlassian Forge -kehitys ja tekoälyratkaisut.\n"
				. "- Hinnoittelu projektikohtaisesti asiakkaan tarpeen ja laajuuden mukaan tarjouksella.\n"
				. "- Yhteydenotto ja tarjouspyynnöt: info@i4ware.fi tai puhelin +358 40 820 0691 (sivuston yhteydenottosivu: https://www.i4ware.fi/yritys/yhteystiedot-ja-tiimi/).\n\n"
				. "SOPIMUSEHDOT JA RAJOITUKSET:\n"
				. "Tekoälyavustaja ei voi tehdä sitovia sopimuksia, antaa virallisia hintalupauksia, käynnistää maksua tai hyväksyä sopimusehtoja asiakkaan puolesta. Kaikki kaupalliset sopimukset vahvistetaan suoraan asiakkaan toimesta kassalla tai kirjallisella sopimuksella.",

			// Limits & Guardrails
			'max_answer_tokens'       => 350,
			'temperature'             => 0.4,
			'rate_limit_per_minute'   => 5,
			'rate_limit_per_hour'     => 30,
			'max_input_chars'         => 400,

			// Toggles
			'enable_widget'           => 1,
			'enable_speech'           => 1,
			'enable_voice_input'      => 1,

			// Privacy
			'privacy_notice'          => 'Viestisi käsitellään Googlen Gemini -tekoälypalvelussa. Emme tallenna keskusteluhistoriaa palvelimelle.',
			'privacy_policy_url'      => $privacy_url,
		);
	}

	/**
	 * Get all settings merged with defaults
	 *
	 * @return array
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Get single setting value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		if ( isset( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Update settings
	 *
	 * @param array $settings
	 * @return bool
	 */
	public static function update_all( $settings ) {
		$current = self::get_all();
		$updated = wp_parse_args( $settings, $current );
		return update_option( self::OPTION_KEY, $updated );
	}

	/**
	 * Retrieve the active Gemini API key from constants, env, or options
	 *
	 * @return string
	 */
	public static function get_api_key() {
		// 1. wp-config constants
		if ( defined( 'GEMINI_API_KEY' ) && ! empty( GEMINI_API_KEY ) ) {
			return trim( (string) GEMINI_API_KEY );
		}
		if ( defined( 'GOOGLE_AI_API_KEY' ) && ! empty( GOOGLE_AI_API_KEY ) ) {
			return trim( (string) GOOGLE_AI_API_KEY );
		}

		// 2. Environment variables
		$env_key = getenv( 'GEMINI_API_KEY' ) ?: getenv( 'GOOGLE_AI_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return trim( $env_key );
		}

		// 3. Stored setting
		$key = self::get( 'api_key', '' );
		if ( ! empty( $key ) ) {
			return trim( $key );
		}

		// 4. Fallback to existing autopilot setting if available
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['google_ai_api_key'] ) && 'password' !== $autopilot_settings['google_ai_api_key'] ) {
			return trim( $autopilot_settings['google_ai_api_key'] );
		}

		return '';
	}

	/**
	 * Check if an API key is available
	 *
	 * @return bool
	 */
	public static function has_api_key() {
		$key = self::get_api_key();
		return ! empty( $key );
	}

	/**
	 * Source where the API key is retrieved from
	 *
	 * @return string
	 */
	public static function get_api_key_source() {
		if ( defined( 'GEMINI_API_KEY' ) && ! empty( GEMINI_API_KEY ) ) {
			return 'wp-config.php (GEMINI_API_KEY)';
		}
		if ( defined( 'GOOGLE_AI_API_KEY' ) && ! empty( GOOGLE_AI_API_KEY ) ) {
			return 'wp-config.php (GOOGLE_AI_API_KEY)';
		}
		if ( getenv( 'GEMINI_API_KEY' ) ) {
			return 'Ympäristömuuttuja (GEMINI_API_KEY)';
		}
		if ( getenv( 'GOOGLE_AI_API_KEY' ) ) {
			return 'Ympäristömuuttuja (GOOGLE_AI_API_KEY)';
		}
		$saved = self::get( 'api_key', '' );
		if ( ! empty( $saved ) ) {
			return 'Tietokanta-asetus';
		}
		return 'Ei määritetty';
	}

	/**
	 * Available text models
	 *
	 * @return array
	 */
	public static function get_text_models() {
		return array(
			'gemini-2.5-flash' => 'Gemini 2.5 Flash (Suositus - nopea & luotettava)',
			'gemini-2.0-flash' => 'Gemini 2.0 Flash (Matala viive)',
			'gemini-1.5-flash' => 'Gemini 1.5 Flash (Vakaa tuotantoversio)',
			'gemini-1.5-pro'   => 'Gemini 1.5 Pro (Monimutkainen päättely)',
		);
	}

	/**
	 * Available TTS models
	 *
	 * @return array
	 */
	public static function get_tts_models() {
		return array(
			'gemini-2.5-flash' => 'Gemini 2.5 Flash (Suositus puhesynteesille - 24kHz WAV)',
			'gemini-2.0-flash' => 'Gemini 2.0 Flash (Matala viive puhesynteesi)',
		);
	}

	/**
	 * Available prebuilt TTS voices
	 *
	 * @return array
	 */
	public static function get_tts_voices() {
		return array(
			'Puck'       => 'Puck (Selkeä, luonnollinen, suositus)',
			'Kore'       => 'Kore (Lämmin, vakaa)',
			'Fenrir'     => 'Fenrir (Matala, rauhallinen)',
			'Aoede'      => 'Aoede (Eloisa, kirkas)',
			'Zephyr'     => 'Zephyr (Kirkas, reipas)',
			'Orus'       => 'Orus (Vakaa, asiallinen)',
			'Autonoe'    => 'Autonoe (Ystävällinen)',
			'Umbriel'    => 'Umbriel (Rento)',
			'Erinome'    => 'Erinome (Kuulas)',
			'Laomedeia'  => 'Laomedeia (Innostava)',
			'Schedar'    => 'Schedar (Tasainen)',
		);
	}
}
