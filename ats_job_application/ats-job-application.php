<?php
/**
 * Plugin Name: MH ATS Job Application
 * Description: Avoin työhakemuslomake WordPressille + ATS-karsinta ja CV:n tietojen tallennus OpenAI:lla.
 * Version: 1.0.0
 * Author: i4ware Software
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) exit;

class MH_ATS_Job_Application {
	const OPTION_GROUP = 'mh_ats_settings';
	const OPTION_API_KEY = 'mh_ats_openai_api_key';
	const REST_NS = 'mh-ats/v1';
	const NONCE_ACTION = 'mh_ats_submit';

	public function __construct() {
		register_activation_hook(__FILE__, [$this, 'on_activate']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_menu', [$this, 'settings_page']);
		add_action('init', [$this, 'register_shortcode']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function on_activate() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$applicants = $wpdb->prefix . 'mh_ats_applicants';
		$docs = $wpdb->prefix . 'mh_ats_documents';
		$scores = $wpdb->prefix . 'mh_ats_scores';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = "
		CREATE TABLE IF NOT EXISTS $applicants (
			id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			firstname VARCHAR(100) NOT NULL,
			lastname VARCHAR(100) NOT NULL,
			email VARCHAR(190) NOT NULL,
			country VARCHAR(120) NOT NULL,
			address1 VARCHAR(190) NOT NULL,
			address2 VARCHAR(190) NULL,
			zip VARCHAR(20) NOT NULL,
			city VARCHAR(120) NOT NULL,
			phone VARCHAR(60) NULL,
			mobile VARCHAR(60) NOT NULL,
			www VARCHAR(190) NULL,
			additional LONGTEXT NOT NULL,
			education LONGTEXT NOT NULL,
			qualifications LONGTEXT NOT NULL,
			skills LONGTEXT NOT NULL,
			workexp LONGTEXT NOT NULL
		) $charset;

		CREATE TABLE IF NOT EXISTS $docs (
			id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
			applicant_id BIGINT UNSIGNED NOT NULL,
			type ENUM('cv','motivation','application') NOT NULL,
			attachment_id BIGINT UNSIGNED NOT NULL,
			FOREIGN KEY (applicant_id) REFERENCES $applicants(id) ON DELETE CASCADE
		) $charset;

		CREATE TABLE IF NOT EXISTS $scores (
			id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
			applicant_id BIGINT UNSIGNED NOT NULL,
			score DECIMAL(5,2) NOT NULL,
			reason LONGTEXT NULL,
			status ENUM('accepted','maybe','rejected') NOT NULL,
			FOREIGN KEY (applicant_id) REFERENCES $applicants(id) ON DELETE CASCADE
		) $charset;";
		dbDelta($sql);
	}

	public function register_settings() {
		register_setting(self::OPTION_GROUP, self::OPTION_API_KEY);
		add_settings_section('mh_ats_sec', 'MH ATS asetukset', function(){
			echo '<p>Syötä OpenAI API -avain (tallennetaan WordPressin asetuksiin). Suosittelemme ympäristömuuttujaa tai Secret Manageria tuotannossa.</p>';
		}, self::OPTION_GROUP);
		add_settings_field(self::OPTION_API_KEY, 'OpenAI API Key', function(){
			$key = esc_attr(get_option(self::OPTION_API_KEY));
			echo '<input type="password" style="width:420px" name="'.self::OPTION_API_KEY.'" value="'.$key.'" placeholder="sk-..." />';
		}, self::OPTION_GROUP, 'mh_ats_sec');
	}

	public function settings_page() {
		add_options_page('MH ATS', 'MH ATS', 'manage_options', 'mh-ats', function(){
			echo '<div class="wrap"><h1>MH ATS</h1><form method="post" action="options.php">';
			settings_fields(self::OPTION_GROUP);
			do_settings_sections(self::OPTION_GROUP);
			submit_button();
			echo '</form></div>';
		});
	}

	public function register_shortcode() {
		add_shortcode('job_application', function(){
			$nonce = wp_create_nonce(self::NONCE_ACTION);
			$rest = esc_url_raw( rest_url(self::REST_NS . '/applications') );
			ob_start();
			echo '<div id="mh-ats-root" data-endpoint="'.$rest.'" data-nonce="'.$nonce.'"></div>';
			return ob_get_clean();
		});
	}

	public function enqueue_assets() {
		// Oleta että buildattu React-appi on /assets/app.js ja /assets/app.css tämän pluginin kansiossa
		$base = plugin_dir_url(__FILE__) . 'assets/';
		wp_enqueue_style('mh-ats-style', $base.'app.css', [], filemtime(plugin_dir_path(__FILE__).'assets/app.css'));
		wp_enqueue_script('mh-ats-app', $base.'app.js', ['wp-element'], filemtime(plugin_dir_path(__FILE__).'assets/app.js'), true);
	}

	public function register_routes() {
		register_rest_route(self::REST_NS, '/applications', [
			'methods' => 'POST',
			'callback' => [$this, 'handle_submit'],
			'permission_callback' => '__return_true',
		]);
	}

	private function handle_upload($file_array) {
		require_once ABSPATH.'wp-admin/includes/file.php';
		$overrides = [ 'test_form' => false, 'mimes' => ['pdf' => 'application/pdf'] ];
		$movefile = wp_handle_upload($file_array, $overrides);
		if ($movefile && !isset($movefile['error'])) {
			$attachment = [
				'post_mime_type' => 'application/pdf',
				'post_title' => sanitize_file_name($file_array['name']),
				'post_content' => '',
				'post_status' => 'private'
			];
			$attach_id = wp_insert_attachment($attachment, $movefile['file']);
			require_once ABSPATH.'wp-admin/includes/image.php';
			wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $movefile['file']));
			return $attach_id;
		}
		return new WP_Error('upload_error', $movefile['error'] ?? 'Upload failed');
	}

	public function handle_submit(WP_REST_Request $req) {
		try {
			// CSRF
			$nonce = $req->get_header('x-wp-nonce');
			if (!$nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
				return new WP_REST_Response(['message' => 'Invalid nonce'], 403);
			}

			// Validate size & type quickly
			foreach (['cv','motivation','application'] as $key) {
				if (!empty($_FILES[$key]['name'])) {
					if ($_FILES[$key]['type'] !== 'application/pdf') return new WP_REST_Response(['message'=> 'Only PDF allowed for '.$key], 400);
					if ($_FILES[$key]['size'] > 8192*1024) return new WP_REST_Response(['message'=> 'File too large for '.$key], 400);
				}
			}

			$payload = [
				'firstname' => sanitize_text_field($req['firstname'] ?? ''),
				'lastname' => sanitize_text_field($req['lastname'] ?? ''),
				'email' => sanitize_email($req['email'] ?? ''),
				'country' => sanitize_text_field($req['country'] ?? ''),
				'address1' => sanitize_text_field($req['address1'] ?? ''),
				'address2' => sanitize_text_field($req['address2'] ?? ''),
				'zip' => sanitize_text_field($req['zip'] ?? ''),
				'city' => sanitize_text_field($req['city'] ?? ''),
				'phone' => sanitize_text_field($req['phone'] ?? ''),
				'mobile' => sanitize_text_field($req['mobile'] ?? ''),
				'www' => esc_url_raw($req['www'] ?? ''),
				'additional' => wp_kses_post($req['additional'] ?? ''),
				'education' => wp_kses_post($req['education'] ?? ''),
				'qualifications' => wp_kses_post($req['qualifications'] ?? ''),
				'skills' => wp_kses_post($req['skills'] ?? ''),
				'workexp' => wp_kses_post($req['workexp'] ?? '')
			];

			// Required fields check
			foreach (['firstname','lastname','email','country','address1','zip','city','mobile','additional','education','qualifications','skills','workexp'] as $reqf) {
				if (empty($payload[$reqf])) return new WP_REST_Response(['message'=> 'Missing field: '.$reqf], 400);
			}

			// Store applicant
			global $wpdb; 
			$applicants = $wpdb->prefix.'mh_ats_applicants';
			$wpdb->insert($applicants, $payload);
			$applicant_id = (int)$wpdb->insert_id;

			// Upload files
			$docs_table = $wpdb->prefix.'mh_ats_documents';
			foreach (['cv','motivation','application'] as $type) {
				if (!empty($_FILES[$type]['name'])) {
					$attach_id = $this->handle_upload($_FILES[$type]);
					if (is_wp_error($attach_id)) return new WP_REST_Response(['message'=> $attach_id->get_error_message()], 400);
					$wpdb->insert($docs_table, [
						'applicant_id' => $applicant_id,
						'type' => $type,
						'attachment_id' => $attach_id,
					]);
				}
			}

			// ATS-sääntöjä (esimerkki) ennen OpenAI:ta
			$rule_score = 0; $reasons = [];
			if (stripos($payload['skills'], 'react') !== false) $rule_score += 20; else $reasons[] = 'React puuttuu';
			if (preg_match('/(php|laravel)/i', $payload['skills'])) $rule_score += 20; else $reasons[] = 'PHP/Laravel puuttuu';
			if (preg_match('/(sql|mysql|postgres)/i', $payload['skills'])) $rule_score += 15; else $reasons[] = 'SQL puuttuu';
			if (preg_match('/\b(5|6|7|8|9|10)\+?\s*(years|v|vuotta)/i', $payload['workexp'])) $rule_score += 20; else $reasons[] = 'Alle 5v kokemus?';
			if (preg_match('/(finland|suomi|tampere|helsinki)/i', $payload['country'].' '.$payload['city'])) $rule_score += 10; else $reasons[] = 'Sijainti ei Suomessa';
			if (!empty($payload['www'])) $rule_score += 5;
			if (!empty($payload['phone'])) $rule_score += 5;

			// OpenAI CV-parsaus + pisteytys
			$openai_key = get_option(self::OPTION_API_KEY);
			$ai_score = 0; $ai_reason = '';
			if ($openai_key) {
				$cv_attachment_id = $wpdb->get_var($wpdb->prepare("SELECT attachment_id FROM $docs_table WHERE applicant_id=%d AND type='cv'", $applicant_id));
				if ($cv_attachment_id) {
					$cv_url = wp_get_attachment_url($cv_attachment_id);
					$prompt = [
						'role' => 'user',
						'content' => "You are an ATS expert. Read the applicant data and the CV at the provided URL and return a strict JSON with fields: skills: string[], total_experience_years: number, seniority: 'junior'|'mid'|'senior', score0to100: number, reasons: string[]. Job requirements: React, PHP/Laravel, SQL, 5+ years, Finland-based. CV URL: $cv_url. Applicant provided skills: {$payload['skills']}. Work experience: {$payload['workexp']}."
					];
					$body = json_encode([
						'model' => 'gpt-4o-mini',
						'messages' => [$prompt],
						'temperature' => 0.2
					]);
					$response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
						'headers' => [
							'Authorization' => 'Bearer ' . $openai_key,
							'Content-Type' => 'application/json'
						],
						'body' => $body,
						'timeout' => 60
					]);
					if (!is_wp_error($response)) {
						$js = json_decode(wp_remote_retrieve_body($response), true);
						$txt = $js['choices'][0]['message']['content'] ?? '';
						// yritä irrottaa JSON
						if (preg_match('/\{[\s\S]*\}/', $txt, $m)) {
							$parsed = json_decode($m[0], true);
							if (is_array($parsed)) {
								$ai_score = (float)($parsed['score0to100'] ?? 0);
								$ai_reason = 'AI: '.implode('; ', (array)($parsed['reasons'] ?? []));
							}
						}
					}
				}
			}

			$total = min(100, $rule_score + (int)round($ai_score * 0.5));
			$status = $total >= 70 ? 'accepted' : ($total >= 50 ? 'maybe' : 'rejected');
			$reason = trim(implode('; ', $reasons).($ai_reason? '; '.$ai_reason : ''));

			$scores_table = $wpdb->prefix.'mh_ats_scores';
			$wpdb->insert($scores_table, [
				'applicant_id' => $applicant_id,
				'score' => $total,
				'reason' => $reason,
				'status' => $status
			]);

			return new WP_REST_Response([
				'message' => 'Application stored',
				'applicant_id' => $applicant_id,
				'status' => $status,
				'score' => $total
			], 200);
		} catch (Throwable $e) {
			return new WP_REST_Response(['message' => 'Server error', 'error' => $e->getMessage()], 500);
		}
	}
}
