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
  const SCHEMA_VERSION = 2;

  public function __construct() {
    register_activation_hook( __FILE__, [ $this, 'on_activate' ] );
    add_action( 'admin_init', [ $this, 'register_settings' ] );
    add_action( 'init', [ $this, 'maybe_upgrade_schema' ] );
    add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    add_action( 'init', [ $this, 'register_shortcode' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    add_filter( 'rest_authentication_errors', [ $this, 'allow_public_rest' ], 20 );
    add_action( 'admin_post_jaf_download', [ $this, 'download_document' ] );
    add_action( 'admin_post_jaf_delete_all', [ $this, 'delete_all_applications' ] );
    add_action( 'admin_post_jaf_delete_old', [ $this, 'delete_old_applications' ] );
    add_action( 'admin_post_jaf_export', [ $this, 'export_applicant_data' ] );
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
    $this->maybe_upgrade_schema();
    $this->ensure_private_upload_dir();
  }

  public function maybe_upgrade_schema() {
    $current = (int) get_option( 'jaf_schema_version', 0 );
    if ( $current >= self::SCHEMA_VERSION ) {
      return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $this->get_schema_sql() );
    $this->ensure_documents_columns();
    update_option( 'jaf_schema_version', self::SCHEMA_VERSION );
  }

  private function get_schema_sql() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $docs = $wpdb->prefix . 'mh_ats_documents';
    $scores = $wpdb->prefix . 'mh_ats_scores';

    return "
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
      attachment_id BIGINT UNSIGNED NULL,
      file_path VARCHAR(255) NULL,
      file_name VARCHAR(190) NULL,
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
  }

  private function ensure_documents_columns() {
    global $wpdb;
    $docs = $wpdb->prefix . 'mh_ats_documents';
    $columns = $wpdb->get_col( "SHOW COLUMNS FROM $docs", 0 );

    if ( empty( $columns ) ) {
      return;
    }

    if ( ! in_array( 'file_path', $columns, true ) ) {
      $wpdb->query( "ALTER TABLE $docs ADD COLUMN file_path VARCHAR(255) NULL" );
    }
    if ( ! in_array( 'file_name', $columns, true ) ) {
      $wpdb->query( "ALTER TABLE $docs ADD COLUMN file_name VARCHAR(190) NULL" );
    }

    if ( in_array( 'attachment_id', $columns, true ) ) {
      $wpdb->query( "ALTER TABLE $docs MODIFY attachment_id BIGINT UNSIGNED NULL" );
    }
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

    if ( isset( $_GET['jaf_notice'] ) ) {
      $notice = sanitize_key( $_GET['jaf_notice'] );
      if ( $notice === 'deleted_all' ) {
        echo '<div class="notice notice-success"><p>All applications deleted.</p></div>';
      } elseif ( $notice === 'deleted_old' ) {
        $count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
        echo '<div class="notice notice-success"><p>Deleted ' . esc_html( $count ) . ' old applications.</p></div>';
      } elseif ( $notice === 'export_failed' ) {
        echo '<div class="notice notice-error"><p>Export failed.</p></div>';
      }
    }

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
        "SELECT type, attachment_id, file_path FROM $docs WHERE applicant_id = %d",
        $applicant_id
      ));

      $docs_by_type = [];
      foreach ($doc_rows as $doc_row) {
        $docs_by_type[$doc_row->type] = [
          'attachment_id' => $doc_row->attachment_id,
          'file_path' => $doc_row->file_path,
        ];
      }

      $back_url = admin_url('admin.php?page=jaf-applications');
      echo '<p><a class="button" href="' . esc_url($back_url) . '">Back to list</a></p>';

      $export_url = wp_nonce_url(
        admin_url('admin-post.php?action=jaf_export&applicant_id=' . (int)$applicant_id),
        'jaf_export_' . $applicant_id
      );

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
      $education_html = $this->format_multiline_field($row->education);
      $qualifications_html = $this->format_multiline_field($row->qualifications);
      echo '<tr><th>Education</th><td>' . $education_html . '</td></tr>';
      echo '<tr><th>Qualifications</th><td>' . $qualifications_html . '</td></tr>';
      echo '<tr><th>Skills</th><td>' . wp_kses_post($row->skills) . '</td></tr>';
      $workexp_html = $this->format_multiline_field($row->workexp);
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
        $doc_meta = $docs_by_type[$type] ?? null;
        if ($doc_meta && (!empty($doc_meta['file_path']) || !empty($doc_meta['attachment_id']))) {
          $download_url = wp_nonce_url(
            admin_url('admin-post.php?action=jaf_download&applicant_id=' . (int)$applicant_id . '&type=' . urlencode($type)),
            'jaf_download_' . $applicant_id . '_' . $type
          );
          $doc_links[] = '<a href="' . esc_url($download_url) . '">' . esc_html($label) . '</a>';
        }
      }
      echo $doc_links ? implode(' | ', $doc_links) : 'No documents';
      echo '</td></tr>';

      echo '<tr><th>GDPR Export</th><td><a class="button" href="' . esc_url( $export_url ) . '">Export applicant data</a></td></tr>';

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

    $delete_all_url = wp_nonce_url(
      admin_url('admin-post.php?action=jaf_delete_all'),
      'jaf_delete_all'
    );
    $delete_old_url = wp_nonce_url(
      admin_url('admin-post.php?action=jaf_delete_old'),
      'jaf_delete_old'
    );

    echo '<div style="margin: 12px 0 18px; display: flex; gap: 10px;">';
    echo '<form method="post" action="' . esc_url( $delete_all_url ) . '" onsubmit="return confirm(\'Delete ALL applications? This cannot be undone.\');">';
    echo '<button type="submit" class="button button-secondary">Delete all applications</button>';
    echo '</form>';
    echo '<form method="post" action="' . esc_url( $delete_old_url ) . '" onsubmit="return confirm(\'Delete applications older than 6 months?\');">';
    echo '<button type="submit" class="button">Delete old applications (6 months)</button>';
    echo '</form>';
    echo '</div>';

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>Date</th><th>Name</th><th>Email</th><th>Score</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
      echo '<tr><td colspan="6">No applications found.</td></tr>';
    } else {
      foreach ($rows as $row) {
        $view_url = admin_url('admin.php?page=jaf-applications&applicant_id=' . (int)$row->id);
        $export_url = wp_nonce_url(
          admin_url('admin-post.php?action=jaf_export&applicant_id=' . (int)$row->id),
          'jaf_export_' . (int)$row->id
        );
        echo '<tr>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '<td>' . esc_html($row->firstname . ' ' . $row->lastname) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->score) . '</td>';
        echo '<td>' . esc_html($row->status) . '</td>';
        echo '<td><a class="button" href="' . esc_url($view_url) . '">View</a> <a class="button" href="' . esc_url($export_url) . '">Export</a></td>';
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

  private function format_multiline_field( $text ) {
    $value = trim( (string) $text );
    $lines = preg_split( "/\r\n|\r|\n/", $value );

    if ( count( $lines ) <= 1 ) {
      if ( strpos( $value, ';' ) !== false ) {
        $lines = explode( ';', $value );
      } else {
        $lines = preg_split( '/(?<=\.)\s+/', $value );
      }
    }

    $lines = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
    if ( $lines ) {
      return '<div>' . implode( '<br>', array_map( 'esc_html', $lines ) ) . '</div>';
    }

    return esc_html( $value );
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

  public function delete_all_applications() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Forbidden', 403 );
    }
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'jaf_delete_all' ) ) {
      wp_die( 'Invalid nonce', 403 );
    }

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $ids = $wpdb->get_col( "SELECT id FROM $applicants" );
    foreach ( $ids as $id ) {
      $this->delete_applicant_and_files( (int) $id );
    }

    wp_safe_redirect( admin_url( 'admin.php?page=jaf-applications&jaf_notice=deleted_all' ) );
    exit;
  }

  public function delete_old_applications() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Forbidden', 403 );
    }
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'jaf_delete_old' ) ) {
      wp_die( 'Invalid nonce', 403 );
    }

    $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-6 months' ) );

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $ids = $wpdb->get_col( $wpdb->prepare(
      "SELECT id FROM $applicants WHERE created_at < %s",
      $cutoff
    ) );

    $count = 0;
    foreach ( $ids as $id ) {
      $this->delete_applicant_and_files( (int) $id );
      $count++;
    }

    wp_safe_redirect( admin_url( 'admin.php?page=jaf-applications&jaf_notice=deleted_old&count=' . (int) $count ) );
    exit;
  }

  public function export_applicant_data() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Forbidden', 403 );
    }

    $applicant_id = isset( $_GET['applicant_id'] ) ? absint( $_GET['applicant_id'] ) : 0;
    if ( ! $applicant_id ) {
      wp_die( 'Not found', 404 );
    }

    $nonce_action = 'jaf_export_' . $applicant_id;
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
      wp_die( 'Invalid nonce', 403 );
    }

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $scores = $wpdb->prefix . 'mh_ats_scores';
    $docs = $wpdb->prefix . 'mh_ats_documents';

    $applicant = $wpdb->get_row( $wpdb->prepare(
      "SELECT * FROM $applicants WHERE id = %d",
      $applicant_id
    ), ARRAY_A );

    if ( ! $applicant ) {
      wp_die( 'Not found', 404 );
    }

    $score = $wpdb->get_row( $wpdb->prepare(
      "SELECT score, status, reason FROM $scores WHERE applicant_id = %d",
      $applicant_id
    ), ARRAY_A );

    $doc_rows = $wpdb->get_results( $wpdb->prepare(
      "SELECT type, attachment_id, file_path, file_name FROM $docs WHERE applicant_id = %d",
      $applicant_id
    ), ARRAY_A );

    $export = [
      'applicant' => $applicant,
      'score' => $score,
      'documents' => []
    ];

    foreach ( $doc_rows as $doc_row ) {
      $export['documents'][] = [
        'type' => $doc_row['type'],
        'file_name' => $doc_row['file_name'],
        'file_path' => $doc_row['file_path'],
        'attachment_id' => $doc_row['attachment_id']
      ];
    }

    if ( ! class_exists( 'ZipArchive' ) ) {
      wp_safe_redirect( admin_url( 'admin.php?page=jaf-applications&jaf_notice=export_failed' ) );
      exit;
    }

    $zip_path = wp_tempnam( 'jaf-export' );
    $zip = new ZipArchive();
    if ( $zip->open( $zip_path, ZipArchive::OVERWRITE ) !== true ) {
      wp_safe_redirect( admin_url( 'admin.php?page=jaf-applications&jaf_notice=export_failed' ) );
      exit;
    }

    $json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    $zip->addFromString( 'applicant.json', $json );

    foreach ( $doc_rows as $doc_row ) {
      $file_path = $doc_row['file_path'];
      if ( ! $file_path && ! empty( $doc_row['attachment_id'] ) ) {
        $file_path = get_attached_file( (int) $doc_row['attachment_id'] );
      }

      if ( $file_path && file_exists( $file_path ) ) {
        $safe_name = $doc_row['file_name'] ? $doc_row['file_name'] : basename( $file_path );
        $safe_name = sanitize_file_name( $safe_name );
        $zip->addFile( $file_path, $doc_row['type'] . '-' . $safe_name );
      }
    }

    $zip->close();

    $download_name = 'applicant-' . $applicant_id . '-export.zip';
    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
    header( 'Content-Length: ' . filesize( $zip_path ) );
    readfile( $zip_path );
    @unlink( $zip_path );
    exit;
  }

  private function delete_applicant_and_files( $applicant_id ) {
    global $wpdb;
    $docs_table = $wpdb->prefix . 'mh_ats_documents';
    $applicants = $wpdb->prefix . 'mh_ats_applicants';

    $doc_rows = $wpdb->get_results( $wpdb->prepare(
      "SELECT attachment_id, file_path FROM $docs_table WHERE applicant_id = %d",
      $applicant_id
    ) );

    foreach ( $doc_rows as $doc_row ) {
      if ( ! empty( $doc_row->file_path ) && file_exists( $doc_row->file_path ) ) {
        @unlink( $doc_row->file_path );
      }
      if ( ! empty( $doc_row->attachment_id ) ) {
        wp_delete_attachment( (int) $doc_row->attachment_id, true );
      }
    }

    $wpdb->delete( $applicants, [ 'id' => $applicant_id ], [ '%d' ] );
  }

  public function download_document() {
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( 'Forbidden', 403 );
    }

    $applicant_id = isset( $_GET['applicant_id'] ) ? absint( $_GET['applicant_id'] ) : 0;
    $type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';

    if ( ! $applicant_id || ! in_array( $type, [ 'cv', 'motivation', 'application' ], true ) ) {
      wp_die( 'Not found', 404 );
    }

    $nonce_action = 'jaf_download_' . $applicant_id . '_' . $type;
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
      wp_die( 'Invalid nonce', 403 );
    }

    global $wpdb;
    $docs_table = $wpdb->prefix . 'mh_ats_documents';
    $row = $wpdb->get_row( $wpdb->prepare(
      "SELECT attachment_id, file_path, file_name FROM $docs_table WHERE applicant_id = %d AND type = %s",
      $applicant_id,
      $type
    ) );

    if ( ! $row ) {
      wp_die( 'Not found', 404 );
    }

    $file_path = $row->file_path;
    if ( ! $file_path && ! empty( $row->attachment_id ) ) {
      $file_path = get_attached_file( (int) $row->attachment_id );
    }

    if ( ! $file_path || ! file_exists( $file_path ) ) {
      wp_die( 'Not found', 404 );
    }

    $uploads = wp_upload_dir();
    $private_dir = trailingslashit( $uploads['basedir'] ) . 'jaf-private';
    $real_private = realpath( $private_dir );
    $real_file = realpath( $file_path );

    if ( ! $real_private || ! $real_file || strpos( $real_file, $real_private ) !== 0 ) {
      wp_die( 'Forbidden', 403 );
    }

    $filename = $row->file_name ? $row->file_name : basename( $file_path );
    $filename = sanitize_file_name( $filename );

    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . filesize( $file_path ) );
    readfile( $file_path );
    exit;
  }

  private function ensure_private_upload_dir() {
    $uploads = wp_upload_dir();
    $private_dir = trailingslashit( $uploads['basedir'] ) . 'jaf-private';

    if ( ! file_exists( $private_dir ) ) {
      wp_mkdir_p( $private_dir );
    }

    $htaccess = $private_dir . '/.htaccess';
    if ( ! file_exists( $htaccess ) ) {
      file_put_contents( $htaccess, "Deny from all\n" );
    }

    $index = $private_dir . '/index.php';
    if ( ! file_exists( $index ) ) {
      file_put_contents( $index, "<?php\n// Silence is golden.\n" );
    }
  }

  /**
   * Extract text from PDF file for OpenAI processing
   * @param string $file_path Full path to PDF file
   * @return string|WP_Error Extracted text or error
   */
  private function extract_pdf_text( $file_path ) {
    if ( ! file_exists( $file_path ) ) {
      return new WP_Error( 'file_not_found', 'PDF file not found' );
    }

    // Try using pdftotext command line tool if available
    $pdftotext = 'pdftotext';
    $test = shell_exec( "where $pdftotext 2>NUL" ); // Windows
    if ( ! $test ) {
      $test = shell_exec( "which $pdftotext 2>/dev/null" ); // Linux/Mac
    }

    if ( $test ) {
      $temp_txt = tempnam( sys_get_temp_dir(), 'pdf_' );
      $escaped_pdf = escapeshellarg( $file_path );
      $escaped_txt = escapeshellarg( $temp_txt );
      exec( "$pdftotext -layout $escaped_pdf $escaped_txt 2>&1", $output, $return_code );
      
      if ( $return_code === 0 && file_exists( $temp_txt ) ) {
        $text = file_get_contents( $temp_txt );
        @unlink( $temp_txt );
        return $text;
      }
    }

    // Fallback: Read first 50KB of PDF as-is (useful for simple PDFs)
    $content = file_get_contents( $file_path, false, null, 0, 51200 );
    
    // Try to extract readable text from PDF content
    if ( preg_match_all( '/\(([^\)]+)\)/', $content, $matches ) ) {
      $extracted = implode( ' ', $matches[1] );
      if ( strlen( trim( $extracted ) ) > 100 ) {
        return $extracted;
      }
    }

    // If we get here, return what we have (may be binary)
    return substr( $content, 0, 2000 ); // Limit size
  }

  private function handle_upload($file_array) {
    require_once ABSPATH.'wp-admin/includes/file.php';
    $this->ensure_private_upload_dir();

    $original_name = sanitize_file_name($file_array['name']);
    $timestamp = gmdate('Ymd_His');
    $file_array['name'] = $timestamp . '_' . $original_name;

    $upload_filter = function( $dirs ) {
      $private_dir = trailingslashit( $dirs['basedir'] ) . 'jaf-private';
      $dirs['path'] = $private_dir;
      $dirs['url'] = $dirs['baseurl'] . '/jaf-private';
      $dirs['subdir'] = '';
      return $dirs;
    };

    add_filter( 'upload_dir', $upload_filter );
    $overrides = [ 'test_form' => false, 'mimes' => ['pdf' => 'application/pdf'] ];
    $movefile = wp_handle_upload($file_array, $overrides);
    remove_filter( 'upload_dir', $upload_filter );
    if ($movefile && !isset($movefile['error'])) {
      return [
        'file_path' => $movefile['file'],
        'file_name' => $original_name
      ];
    }
    return new WP_Error('upload_error', $movefile['error'] ?? 'Upload failed');
  }

  public function handle_submit(WP_REST_Request $req) {
    try {
      $this->ensure_documents_columns();
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
          $upload = $this->handle_upload($_FILES[$type]);
          if (is_wp_error($upload)) return new WP_REST_Response(['message'=> $upload->get_error_message()], 400);
          $inserted = $wpdb->insert($docs_table, [
            'applicant_id' => $applicant_id,
            'type' => $type,
            'attachment_id' => 0,
            'file_path' => $upload['file_path'],
            'file_name' => $upload['file_name'],
          ]);
          if ( $inserted === false ) {
            return new WP_REST_Response([
              'message' => 'Failed to store document metadata',
              'error' => $wpdb->last_error
            ], 500);
          }
        }
      }

      // ATS-sääntöjä (esimerkki) ennen OpenAI:ta
      $rule_score = 0; $reasons = [];
      if (stripos($payload['skills'], 'react') !== false) $rule_score += 20; else $reasons[] = 'React puuttuu lomakkeesta';
      if (preg_match('/(php|laravel)/i', $payload['skills'])) $rule_score += 20; else $reasons[] = 'PHP/Laravel puuttuu lomakkeesta';
      if (preg_match('/(sql|mysql|postgres)/i', $payload['skills'])) $rule_score += 15; else $reasons[] = 'SQL puuttuu lomakkeesta';
      
      // Työkokemus tarkistetaan OpenAI:n kautta CV:stä, ei lomakkeesta
      if (!preg_match('/\b(5|6|7|8|9|10)\+?\s*(years|v|vuotta)/i', $payload['workexp'])) {
        $reasons[] = 'Työkokemus lomakkeessa puutteellinen (tarkistetaan CV:stä)';
      }
      
      if (preg_match('/(finland|suomi|tampere|helsinki)/i', $payload['country'].' '.$payload['city'])) $rule_score += 10; else $reasons[] = 'Sijainti ei Suomessa';
      if (!empty($payload['www'])) $rule_score += 5;
      if (!empty($payload['phone'])) $rule_score += 5;

      // OpenAI PDF-parsaus + pisteytys kaikille dokumenteille
      $openai_key = get_option(self::OPTION_API_KEY);
      $ai_score = 0; $ai_reason = '';
      if ($openai_key) {
        // Hae kaikki dokumentit
        $doc_rows = $wpdb->get_results($wpdb->prepare(
          "SELECT type, file_path, attachment_id FROM $docs_table WHERE applicant_id=%d",
          $applicant_id
        ));

        $documents_content = [];
        foreach ($doc_rows as $doc) {
          $file_path = $doc->file_path;
          if (!$file_path && !empty($doc->attachment_id)) {
            $file_path = get_attached_file((int)$doc->attachment_id);
          }
          
          if ($file_path && file_exists($file_path)) {
            $text = $this->extract_pdf_text($file_path);
            if (!is_wp_error($text)) {
              // Sanitize text: remove null bytes and ensure valid UTF-8
              $text = str_replace("\0", '', $text);
              $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
              $documents_content[$doc->type] = substr($text, 0, 4000); // Limit per document
            }
          }
        }

        if (!empty($documents_content)) {
          // Rakenna prompt kaikilla dokumenteilla
          $content_parts = [];
          $content_parts[] = "Applicant form data:";
          $content_parts[] = "Skills: {$payload['skills']}";
          $content_parts[] = "Work experience: {$payload['workexp']}";
          $content_parts[] = "Education: {$payload['education']}";
          $content_parts[] = "Qualifications: {$payload['qualifications']}";
          $content_parts[] = "Additional info: {$payload['additional']}";
          $content_parts[] = "\n--- DOCUMENTS ---";
          
          foreach ($documents_content as $type => $content) {
            $label = strtoupper($type);
            $content_parts[] = "\n=== $label ===\n" . $content;
          }
          
          $full_content = implode("\n", $content_parts);
          
          $prompt = [
            'role' => 'user',
            'content' => "You are an ATS (Applicant Tracking System) expert. Analyze the job application including CV, motivation letter, and application form.\n\nJob requirements: React, PHP/Laravel, SQL, 5+ years experience, Finland-based.\n\nIMPORTANT: Analyze work experience PRIMARILY from the CV PDF document, not from the form fields. The applicant may not have filled the form fields properly. Calculate total years of work experience from CV positions/dates.\n\nCompare CV content vs form fields and note if applicant provided insufficient information in form fields.\n\nReturn ONLY a valid JSON object (no markdown, no extra text) with these fields:\n{\n  \"skills\": [\"skill1\", \"skill2\"],\n  \"total_experience_years\": number (from CV),\n  \"cv_experience_summary\": \"brief summary of work experience from CV\",\n  \"form_vs_cv_quality\": \"excellent\" or \"good\" or \"poor\" (did applicant fill form properly compared to CV?),\n  \"seniority\": \"junior\" or \"mid\" or \"senior\",\n  \"score0to100\": number,\n  \"reasons\": [\"reason1\", \"reason2\"],\n  \"motivation_quality\": \"poor\" or \"average\" or \"good\" or \"excellent\",\n  \"overall_fit\": \"poor\" or \"moderate\" or \"good\" or \"excellent\"\n}\n\n" . $full_content
          ];
          
          $body = json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [$prompt],
            'temperature' => 0.2,
            'max_tokens' => 1000
          ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
          
          // Check if json_encode succeeded
          if ($body === false) {
            error_log('JAF: JSON encode failed: ' . json_last_error_msg());
            // Skip OpenAI processing but continue with rule-based scoring
          } else {
            
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
              
              // Yritä irrottaa JSON (poista mahdolliset markdown-merkit)
              $txt = preg_replace('/```json\s*/', '', $txt);
              $txt = preg_replace('/```\s*$/', '', $txt);
              $txt = trim($txt);
              
              if (preg_match('/\{[\s\S]*\}/', $txt, $m)) {
                $parsed = json_decode($m[0], true);
                if (is_array($parsed)) {
                  $ai_score = (float)($parsed['score0to100'] ?? 0);
                  $ai_reasons = (array)($parsed['reasons'] ?? []);
                  
                  // Lisää CV:stä laskettu työkokemus
                  $cv_experience = $parsed['total_experience_years'] ?? null;
                  $cv_summary = $parsed['cv_experience_summary'] ?? '';
                  if ($cv_experience !== null) {
                    $ai_reasons[] = "CV work experience: {$cv_experience} years";
                    if ($cv_summary) {
                      $ai_reasons[] = "CV summary: {$cv_summary}";
                    }
                    // Anna työkokemuspisteet CV:n perusteella
                    if ($cv_experience >= 5) {
                      $rule_score += 20; // Lisää säännölliseen pisteeseen CV:n mukaan
                    }
                  }
                  
                  // Huomautus jos lomake täytetty huonosti
                  $form_quality = $parsed['form_vs_cv_quality'] ?? '';
                  if ($form_quality === 'poor') {
                    $ai_reasons[] = "WARNING: Applicant did not fill form fields properly (form data incomplete vs CV)";
                  } elseif ($form_quality === 'good' || $form_quality === 'excellent') {
                    $ai_reasons[] = "Form filled properly";
                  }
                  
                  // Lisää motivaation laatu ja yleinen soveltuvuus
                  $motivation_quality = $parsed['motivation_quality'] ?? '';
                  $overall_fit = $parsed['overall_fit'] ?? '';
                  
                  if ($motivation_quality) {
                    $ai_reasons[] = "Motivation: $motivation_quality";
                  }
                  if ($overall_fit) {
                    $ai_reasons[] = "Overall fit: $overall_fit";
                  }
                  
                  $ai_reason = 'AI: ' . implode('; ', $ai_reasons);
                }
              }
            } else {
              error_log('JAF: OpenAI API error: ' . $response->get_error_message());
            }
          } // End of json_encode check
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

      $admin_email = get_option('admin_email');
      if ($admin_email) {
        $subject = 'New job application received';
        $message = "A new job application has been received.\n\n";
        $message .= "Name: {$payload['firstname']} {$payload['lastname']}\n";
        $message .= "Email: {$payload['email']}\n";
        $message .= "Submitted at: " . gmdate('Y-m-d H:i:s') . " UTC\n\n";
        $message .= "View in admin: " . admin_url('admin.php?page=jaf-applications&applicant_id=' . $applicant_id);
        wp_mail($admin_email, $subject, $message);
      }

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
