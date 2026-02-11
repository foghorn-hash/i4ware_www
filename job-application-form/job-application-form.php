<?php
/**
 * Plugin Name: Job Application Form (React)
 * Description: Shortcode [job_application_form] renders a React job application form.
 * Version:     1.0.0
 * Author:      i4ware Software
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'JAF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JAF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'JAF_PLUGIN_VER', '1.0.0' );

class JAF_Plugin {
  const OPTION_GROUP = 'mh_ats_settings';
  const OPTION_API_KEY = 'mh_ats_openai_api_key';
  const REST_NS = 'mh-ats/v1';
  const NONCE_ACTION = 'wp_rest';

  public function __construct() {
    register_activation_hook( __FILE__, [ $this, 'on_activate' ] );
    add_action( 'admin_init', [ $this, 'register_settings' ] );
    add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    add_action( 'init', [ $this, 'register_shortcode' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    add_filter( 'rest_authentication_errors', [ $this, 'allow_public_rest' ], 20 );
  }

  /**
   * Register built assets (React bundle + CSS).
   */
  public function register_assets() {
    // Päivitä polut vastaamaan buildiasi
    $css_rel = 'css/main.1d366fb0.css';
    $js_rel  = 'js/main.dcd359f5.js';
    $plugin_url = plugin_dir_url( __FILE__ ) . 'static/';
    $css_path = JAF_PLUGIN_PATH . $css_rel;
    $js_path  = JAF_PLUGIN_PATH . $js_rel;

    $css_ver = file_exists( $css_path ) ? filemtime( $css_path ) : JAF_PLUGIN_VER;
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path )  : JAF_PLUGIN_VER;

    wp_register_style(
      'jaf-app',
      $plugin_url . $css_rel,
      [],
      $css_ver
    );

    // Oletus: React on bundlattu tähän tiedostoon
    wp_register_script(
      'jaf-app',
      $plugin_url . $js_rel,
      [],
      $js_ver,
      true
    );
  }

  /**
   * Register shortcode on init.
   */
  public function register_shortcode() {
    add_shortcode( 'job_application_form', [ $this, 'shortcode' ] );
  }

  /**
   * Shortcode handler.
   * Usage: [job_application_form]
   */
  public function shortcode() {
    // Enqueue assets
    wp_enqueue_style( 'jaf-app' );
    wp_enqueue_script( 'jaf-app' );

    $id = 'jafroot';
    $nonce = wp_create_nonce( self::NONCE_ACTION );
    $rest = esc_url_raw( rest_url( self::REST_NS . '/applications' ) );

    ob_start();
    ?>
      <div id="<?php echo esc_attr( $id ); ?>" class="job-app-form-root" data-endpoint="<?php echo esc_attr( $rest ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>"></div>
    <?php
    return ob_get_clean();
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
    add_settings_section('mh_ats_sec', 'ATS asetukset', function(){
      echo '<p>Syötä OpenAI API -avain (tallennetaan WordPressin asetuksiin). Suosittelemme ympäristömuuttujaa tai Secret Manageria tuotannossa.</p>';
    }, self::OPTION_GROUP);
    add_settings_field(self::OPTION_API_KEY, 'OpenAI API Key', function(){
      $key = esc_attr(get_option(self::OPTION_API_KEY));
      echo '<input type="password" style="width:420px" name="'.self::OPTION_API_KEY.'" value="'.$key.'" placeholder="sk-..." />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
  }

  public function admin_menu() {
    add_menu_page(
      'Job Applications',
      'Job Applications',
      'manage_options',
      'jaf-applications',
      [ $this, 'render_applications_page' ],
      'dashicons-id',
      26
    );

    add_submenu_page(
      'jaf-applications',
      'Job Applications',
      'Applications',
      'manage_options',
      'jaf-applications',
      [ $this, 'render_applications_page' ]
    );

    add_submenu_page(
      'jaf-applications',
      'ATS Settings',
      'Settings',
      'manage_options',
      'mh-ats',
      [ $this, 'render_settings_page' ]
    );
  }

  public function render_settings_page() {
    echo '<div class="wrap"><h1>ATS</h1><form method="post" action="options.php">';
    settings_fields(self::OPTION_GROUP);
    do_settings_sections(self::OPTION_GROUP);
    submit_button();
    echo '</form></div>';
  }

  public function render_applications_page() {
    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $scores = $wpdb->prefix . 'mh_ats_scores';
    $docs = $wpdb->prefix . 'mh_ats_documents';

    $applicant_id = isset($_GET['applicant_id']) ? absint($_GET['applicant_id']) : 0;

    echo '<div class="wrap"><h1>Job Applications</h1>';

    if ($applicant_id) {
      $row = $wpdb->get_row($wpdb->prepare(
        "SELECT a.*, s.score, s.status, s.reason FROM $applicants a LEFT JOIN $scores s ON a.id = s.applicant_id WHERE a.id = %d",
        $applicant_id
      ));

      if (!$row) {
        echo '<p>Application not found.</p></div>';
        return;
      }

      $doc_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT type, attachment_id FROM $docs WHERE applicant_id = %d",
        $applicant_id
      ));

      $docs_by_type = [];
      foreach ($doc_rows as $doc_row) {
        $docs_by_type[$doc_row->type] = $doc_row->attachment_id;
      }

      $back_url = admin_url('admin.php?page=jaf-applications');
      echo '<p><a class="button" href="' . esc_url($back_url) . '">Back to list</a></p>';

      echo '<table class="widefat striped" style="max-width: 980px">';
      echo '<tbody>';
      echo '<tr><th>Name</th><td>' . esc_html($row->firstname . ' ' . $row->lastname) . '</td></tr>';
      echo '<tr><th>Email</th><td>' . esc_html($row->email) . '</td></tr>';
      echo '<tr><th>Country</th><td>' . esc_html($row->country) . '</td></tr>';
      echo '<tr><th>Address</th><td>' . esc_html($row->address1 . ' ' . $row->address2) . '</td></tr>';
      echo '<tr><th>Zip / City</th><td>' . esc_html($row->zip . ' ' . $row->city) . '</td></tr>';
      echo '<tr><th>Phone</th><td>' . esc_html($row->phone) . '</td></tr>';
      echo '<tr><th>Mobile</th><td>' . esc_html($row->mobile) . '</td></tr>';
      echo '<tr><th>Website</th><td>' . esc_html($row->www) . '</td></tr>';
      echo '<tr><th>Additional</th><td>' . wp_kses_post($row->additional) . '</td></tr>';
      echo '<tr><th>Education</th><td>' . wp_kses_post($row->education) . '</td></tr>';
      echo '<tr><th>Qualifications</th><td>' . wp_kses_post($row->qualifications) . '</td></tr>';
      echo '<tr><th>Skills</th><td>' . wp_kses_post($row->skills) . '</td></tr>';
      $workexp_text = trim((string)$row->workexp);
      $workexp_lines = preg_split("/\r\n|\r|\n/", $workexp_text);
      if (count($workexp_lines) <= 1) {
        if (strpos($workexp_text, ';') !== false) {
          $workexp_lines = explode(';', $workexp_text);
        } else {
          $workexp_lines = preg_split('/(?<=\.)\s+/', $workexp_text);
        }
      }
      $workexp_lines = array_values(array_filter(array_map('trim', $workexp_lines), 'strlen'));
      if ($workexp_lines) {
        $workexp_html = '<div>' . implode('<br>', array_map('esc_html', $workexp_lines)) . '</div>';
      } else {
        $workexp_html = esc_html($workexp_text);
      }
      echo '<tr><th>Work Experience</th><td>' . $workexp_html . '</td></tr>';
      echo '<tr><th>Score</th><td>' . esc_html($row->score) . '</td></tr>';
      echo '<tr><th>Status</th><td>' . esc_html($row->status) . '</td></tr>';
      $reason_items = [];
      if (!empty($row->reason)) {
        foreach (explode(';', $row->reason) as $reason_part) {
          $reason_part = trim($reason_part);
          if ($reason_part !== '') {
            $reason_items[] = $reason_part;
          }
        }
      }
      if ($reason_items) {
        $reason_html = '<ul style="margin:0; padding-left: 18px;">';
        foreach ($reason_items as $reason_item) {
          $reason_html .= '<li>' . esc_html($reason_item) . '</li>';
        }
        $reason_html .= '</ul>';
      } else {
        $reason_html = esc_html($row->reason);
      }
      echo '<tr><th>Reason</th><td>' . $reason_html . '</td></tr>';

      echo '<tr><th>Documents</th><td>';
      $doc_labels = [
        'cv' => 'CV',
        'motivation' => 'Motivation Letter',
        'application' => 'Application'
      ];
      $doc_links = [];
      foreach ($doc_labels as $type => $label) {
        if (!empty($docs_by_type[$type])) {
          $url = wp_get_attachment_url($docs_by_type[$type]);
          if ($url) {
            $doc_links[] = '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
          }
        }
      }
      echo $doc_links ? implode(' | ', $doc_links) : 'No documents';
      echo '</td></tr>';

      echo '</tbody></table>';
      echo '</div>';
      return;
    }

    $rows = $wpdb->get_results(
      "SELECT a.id, a.created_at, a.firstname, a.lastname, a.email, s.score, s.status
       FROM $applicants a
       LEFT JOIN $scores s ON a.id = s.applicant_id
       ORDER BY a.created_at DESC
       LIMIT 100"
    );

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>Date</th><th>Name</th><th>Email</th><th>Score</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
      echo '<tr><td colspan="6">No applications found.</td></tr>';
    } else {
      foreach ($rows as $row) {
        $view_url = admin_url('admin.php?page=jaf-applications&applicant_id=' . (int)$row->id);
        echo '<tr>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '<td>' . esc_html($row->firstname . ' ' . $row->lastname) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->score) . '</td>';
        echo '<td>' . esc_html($row->status) . '</td>';
        echo '<td><a class="button" href="' . esc_url($view_url) . '">View</a></td>';
        echo '</tr>';
      }
    }

    echo '</tbody></table>';
    echo '</div>';
  }

  public function register_routes() {
    register_rest_route(self::REST_NS, '/applications', [
      'methods' => 'POST',
      'callback' => [ $this, 'handle_submit' ],
      'permission_callback' => '__return_true',
    ]);
  }

  public function allow_public_rest( $result ) {
    if ( is_wp_error( $result ) && $result->get_error_code() === 'rest_cookie_invalid_nonce' ) {
      $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
      $prefix = '/' . rest_get_url_prefix() . '/' . self::REST_NS . '/applications';
      if ( $uri && strpos( $uri, $prefix ) !== false ) {
        return null;
      }

      // Support non-pretty permalinks: /?rest_route=/mh-ats/v1/applications
      $rest_route = '';
      if ( isset( $_GET['rest_route'] ) ) {
        $rest_route = wp_unslash( $_GET['rest_route'] );
      } elseif ( $uri ) {
        $parts = wp_parse_url( $uri );
        if ( ! empty( $parts['query'] ) ) {
          parse_str( $parts['query'], $query );
          $rest_route = isset( $query['rest_route'] ) ? $query['rest_route'] : '';
        }
      }

      if ( $rest_route && strpos( $rest_route, '/' . self::REST_NS . '/applications' ) !== false ) {
        return null;
      }
    }
    return $result;
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
      // CSRF (optional for public form)
      $nonce = $req->get_header('x-wp-nonce');
      if ($nonce && !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
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

new JAF_Plugin();
// EOF
