<?php
/**
 * Plugin Name: Job Application Form (React)
 * Description: Shortcode [job_application_form] renders a React job application form.
 * Version:     1.0.0
 * Author:      i4ware Software
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
  exit;
}

define('JAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JAF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JAF_PLUGIN_VER', '1.0.0');

class JAF_Plugin
{
  const OPTION_GROUP = 'mh_ats_settings';
  const OPTION_API_KEY = 'mh_ats_openai_api_key';
  const OPTION_JOB_TITLE = 'mh_ats_job_title';
  const OPTION_JOB_DESCRIPTION = 'mh_ats_job_description';
  const OPTION_REQUIRED_SKILLS = 'mh_ats_required_skills';
  const OPTION_MIN_EXPERIENCE = 'mh_ats_min_experience';
  const OPTION_PREFERRED_LOCATION = 'mh_ats_preferred_location';
  const OPTION_ADDITIONAL_CRITERIA = 'mh_ats_additional_criteria';
  const OPTION_JOB_OPENINGS = 'mh_ats_job_openings';
  const REST_NS = 'mh-ats/v1';
  const NONCE_ACTION = 'wp_rest';
  const SCHEMA_VERSION = 3;

  public function __construct()
  {
    register_activation_hook(__FILE__, [$this, 'on_activate']);
    add_action('admin_init', [$this, 'register_settings']);
    add_action('init', [$this, 'maybe_upgrade_schema']);
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('init', [$this, 'register_shortcode']);
    add_action('init', [$this, 'register_polylang_strings']);
    add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    add_action('rest_api_init', [$this, 'register_routes']);
    add_filter('rest_authentication_errors', [$this, 'allow_public_rest'], 20);
    add_action('admin_post_jaf_download', [$this, 'download_document']);
    add_action('admin_post_jaf_delete_all', [$this, 'delete_all_applications']);
    add_action('admin_post_jaf_delete_old', [$this, 'delete_old_applications']);
    add_action('admin_post_jaf_export', [$this, 'export_applicant_data']);
    add_action('admin_post_jaf_delete_single', [$this, 'delete_single_application']);
  }

  /**
   * Register built assets (React bundle + CSS).
   */
  public function register_assets()
  {
    // Etsi tuorein käännetty CSS- ja JS-tiedosto staattisesta kansiosta aikaleiman mukaan
    $css_files = glob(JAF_PLUGIN_PATH . 'static/css/main.*.css');
    $js_files = glob(JAF_PLUGIN_PATH . 'static/js/main.*.js');

    if (!empty($css_files)) {
      usort($css_files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
      });
    }
    if (!empty($js_files)) {
      usort($js_files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
      });
    }

    $css_rel = (!empty($css_files) && file_exists($css_files[0])) ? 'css/' . basename($css_files[0]) : 'css/main.2614c0c4.css';
    $js_rel = (!empty($js_files) && file_exists($js_files[0])) ? 'js/' . basename($js_files[0]) : 'js/main.80d1f1e7.js';

    $plugin_url = plugin_dir_url(__FILE__) . 'static/';
    $css_path = JAF_PLUGIN_PATH . 'static/' . $css_rel;
    $js_path = JAF_PLUGIN_PATH . 'static/' . $js_rel;

    $css_ver = file_exists($css_path) ? filemtime($css_path) : JAF_PLUGIN_VER;
    $js_ver = file_exists($js_path) ? filemtime($js_path) : JAF_PLUGIN_VER;

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
  public function register_shortcode()
  {
    add_shortcode('job_application_form', [$this, 'shortcode']);
    add_shortcode('job_openings', [$this, 'shortcode_job_openings']);
  }

  /**
   * Register strings for Polylang translation.
   */
  public function register_polylang_strings()
  {
    if (function_exists('pll_register_string')) {
      pll_register_string(
        'mh_ats_job_openings',
        get_option(self::OPTION_JOB_OPENINGS, "Full Stack Developer | #jafroot\nOhjelmistokehittäjä (Harjoittelu) | #jafroot"),
        'Job Application Form',
        true
      );
      pll_register_string(
        'mh_ats_job_title',
        get_option(self::OPTION_JOB_TITLE, "Full Stack Developer"),
        'Job Application Form',
        false
      );
      pll_register_string(
        'mh_ats_job_description',
        get_option(self::OPTION_JOB_DESCRIPTION, $this->get_default_job_description()),
        'Job Application Form',
        true
      );
    }
  }

  /**
   * Default job/internship description.
   */
  public function get_default_job_description()
  {
    return "Etsimme tiimiimme motivoituneita ohjelmistokehittäjiä sekä opintoihin liittyviä työharjoittelijoita (Full Stack Developer, React, TypeScript, PHP, WordPress, Laravel, Jira/Atlassian, REST API). Tarjoamme monipuolisia ja mielenkiintoisia asiakas- ja tuotekehitysprojekteja, joustavat etätyömahdollisuudet sekä kokeneen tiimin tuen ammatilliseen kehittymiseen. Voit hakea avointa työpaikkaa tai harjoittelupaikkaa täyttämällä alla olevan hakulomakkeen.";
  }

  /**
   * Shortcode handler for open positions list.
   * Usage: [job_openings]
   */
  public function shortcode_job_openings()
  {
    $openings_text = get_option(self::OPTION_JOB_OPENINGS, "Full Stack Developer | #jafroot\nOhjelmistokehittäjä (Harjoittelu) | #jafroot");

    if (function_exists('pll__')) {
      $openings_text = pll__($openings_text);
    }

    $lines = explode("\n", $openings_text);

    ob_start();
    echo '<ul class="mh-ats-job-openings">';
    foreach ($lines as $line) {
      $line = trim($line);
      if (!$line)
        continue;

      $parts = explode('|', $line, 2);
      $title = trim($parts[0]);
      $link = isset($parts[1]) ? trim($parts[1]) : '#jafroot';

      echo '<li><a href="' . esc_url($link) . '" data-position="' . esc_attr($title) . '" onclick="if(window.atsSelectPosition){window.atsSelectPosition(\'' . esc_js($title) . '\');}">' . esc_html($title) . '</a></li>';
    }
    echo '</ul>';

    return ob_get_clean();
  }

  /**
   * Shortcode handler.
   * Usage: [job_application_form position="Full Stack Developer" description="..."]
   */
  public function shortcode($atts = [])
  {
    $atts = shortcode_atts([
      'position' => '',
      'description' => ''
    ], $atts, 'job_application_form');

    // Enqueue assets
    wp_enqueue_style('jaf-app');
    wp_enqueue_script('jaf-app');

    $id = 'jafroot';
    $nonce = wp_create_nonce(self::NONCE_ACTION);
    $rest = esc_url_raw(rest_url(self::REST_NS . '/applications'));

    $openings_html = do_shortcode('[job_openings]');

    // Hae avoimet paikat JSON-muodossa React-valitsimelle
    $openings_text = get_option(self::OPTION_JOB_OPENINGS, "Full Stack Developer | #jafroot\nOhjelmistokehittäjä (Harjoittelu) | #jafroot");
    if (function_exists('pll__')) {
      $openings_text = pll__($openings_text);
    }

    $positions = [];
    foreach (explode("\n", $openings_text) as $line) {
      $line = trim($line);
      if (!$line)
        continue;
      $parts = explode('|', $line, 2);
      $pos_title = trim($parts[0]);
      if ($pos_title && !in_array($pos_title, $positions, true)) {
        $positions[] = $pos_title;
      }
    }

    $default_job = !empty($atts['position']) ? sanitize_text_field($atts['position']) : get_option(self::OPTION_JOB_TITLE, 'Full Stack Developer');
    if (function_exists('pll__')) {
      $default_job = pll__($default_job);
    }

    if (!in_array($default_job, $positions, true)) {
      array_unshift($positions, $default_job);
    }

    $description = !empty($atts['description']) ? $atts['description'] : get_option(self::OPTION_JOB_DESCRIPTION, $this->get_default_job_description());
    if (function_exists('pll__')) {
      $description = pll__($description);
    }

    ob_start();
    ?>
    <div class="ats-wrapper" style="max-width: 1300px; margin: 0 auto;">
      <!-- 1. Työpaikan tai harjoittelupaikan kuvaus ENNEN hakulomaketta (HTML-kortti ei katoa koskaan) -->
      <div class="ats-job-hero-card ats-server-description">
        <div class="ats-badge-row">
          <span class="ats-badge ats-badge-primary">✨ <?php esc_html_e('Työpaikka & Harjoittelu', 'job-application-form'); ?></span>
          <span class="ats-badge ats-badge-secondary">🌐 <?php esc_html_e('Joustava etätyö', 'job-application-form'); ?></span>
          <span class="ats-badge ats-badge-accent">⚡ React • PHP • WordPress • Laravel • Jira</span>
        </div>

        <h2 class="ats-job-hero-title"><?php echo esc_html($default_job); ?> – <?php esc_html_e('Tietoa tehtävästä ja harjoittelupaikasta', 'job-application-form'); ?></h2>

        <div class="ats-job-hero-desc">
          <?php foreach (explode("\n", (string) $description) as $para): ?>
            <?php if (trim($para)): ?>
              <p><?php echo esc_html(trim($para)); ?></p>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($positions)): ?>
          <div class="ats-openings-selector-box">
            <span class="ats-openings-label"><?php esc_html_e('Avoinna olevat työ- ja harjoittelupaikat (valitse klikkaamalla):', 'job-application-form'); ?></span>
            <div class="ats-position-tags">
              <?php foreach ($positions as $pos): ?>
                <a href="#jafroot" class="ats-position-tag" data-position="<?php echo esc_attr($pos); ?>" onclick="if(window.atsSelectPosition){window.atsSelectPosition('<?php echo esc_js($pos); ?>');}">
                  <span class="tag-dot"></span>
                  <?php echo esc_html($pos); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- 2. Hakulomake (React mountaa tähän pysyvän kuvauksen alapuolelle) -->
      <div id="<?php echo esc_attr($id); ?>" 
        class="job-app-form-root" 
        data-endpoint="<?php echo esc_attr($rest); ?>"
        data-nonce="<?php echo esc_attr($nonce); ?>" 
        data-openings="<?php echo esc_attr($openings_html); ?>"
        data-openings-json="<?php echo esc_attr(wp_json_encode($positions)); ?>"
        data-description="<?php echo esc_attr($description); ?>"
        data-default-job="<?php echo esc_attr($default_job); ?>">
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  public function on_activate()
  {
    $this->maybe_upgrade_schema();
    $this->ensure_private_upload_dir();
  }

  public function maybe_upgrade_schema()
  {
    $current = (int) get_option('jaf_schema_version', 0);
    if ($current >= self::SCHEMA_VERSION) {
      $this->ensure_applicant_columns();
      $this->ensure_documents_columns();
      return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($this->get_schema_sql());
    $this->ensure_applicant_columns();
    $this->ensure_documents_columns();
    update_option('jaf_schema_version', self::SCHEMA_VERSION);
  }

  private function ensure_applicant_columns()
  {
    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $applicants", 0);

    if (empty($columns)) {
      return;
    }

    if (!in_array('job_title', $columns, true)) {
      $wpdb->query("ALTER TABLE $applicants ADD COLUMN job_title VARCHAR(190) NULL AFTER email");
    }
  }

  private function get_schema_sql()
  {
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
      job_title VARCHAR(190) NULL,
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

  private function ensure_documents_columns()
  {
    global $wpdb;
    $docs = $wpdb->prefix . 'mh_ats_documents';
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $docs", 0);

    if (empty($columns)) {
      return;
    }

    if (!in_array('file_path', $columns, true)) {
      $wpdb->query("ALTER TABLE $docs ADD COLUMN file_path VARCHAR(255) NULL");
    }
    if (!in_array('file_name', $columns, true)) {
      $wpdb->query("ALTER TABLE $docs ADD COLUMN file_name VARCHAR(190) NULL");
    }

    if (in_array('attachment_id', $columns, true)) {
      $wpdb->query("ALTER TABLE $docs MODIFY attachment_id BIGINT UNSIGNED NULL");
    }
  }

  public function register_settings()
  {
    register_setting(self::OPTION_GROUP, self::OPTION_API_KEY);
    register_setting(self::OPTION_GROUP, self::OPTION_JOB_TITLE);
    register_setting(self::OPTION_GROUP, self::OPTION_JOB_DESCRIPTION);
    register_setting(self::OPTION_GROUP, self::OPTION_REQUIRED_SKILLS);
    register_setting(self::OPTION_GROUP, self::OPTION_MIN_EXPERIENCE);
    register_setting(self::OPTION_GROUP, self::OPTION_PREFERRED_LOCATION);
    register_setting(self::OPTION_GROUP, self::OPTION_ADDITIONAL_CRITERIA);
    register_setting(self::OPTION_GROUP, self::OPTION_JOB_OPENINGS);
    add_settings_section('mh_ats_sec', 'ATS asetukset', function () {
      echo '<p>Syötä OpenAI API -avain (tallennetaan WordPressin asetuksiin). Suosittelemme ympäristömuuttujaa tai Secret Manageria tuotannossa.</p>';
      echo '<p>Määritä myös ATS-parametrit työn vaatimusten mukaan sekä työ- ja harjoittelupaikkojen kuvaukset.</p>';
    }, self::OPTION_GROUP);
    add_settings_field(self::OPTION_API_KEY, 'OpenAI API Key', function () {
      $key = esc_attr(get_option(self::OPTION_API_KEY));
      echo '<input type="password" style="width:420px" name="' . self::OPTION_API_KEY . '" value="' . $key . '" placeholder="sk-..." />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_JOB_TITLE, 'Job Title (Default)', function () {
      $value = esc_attr(get_option(self::OPTION_JOB_TITLE, 'Full Stack Developer'));
      echo '<input type="text" style="width:420px" name="' . self::OPTION_JOB_TITLE . '" value="' . $value . '" placeholder="e.g. Full Stack Developer" />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_JOB_DESCRIPTION, 'Työpaikan tai harjoittelupaikan kuvaus', function () {
      $value = get_option(self::OPTION_JOB_DESCRIPTION, $this->get_default_job_description());
      echo '<textarea style="width:100%; max-width:650px; height:130px" name="' . self::OPTION_JOB_DESCRIPTION . '" placeholder="Työpaikan ja harjoittelupaikan kuvaus">' . esc_textarea($value) . '</textarea>';
      echo '<p class="description">Tämä kuvaus esitetään hakijoille suoraan hakulomakkeen yläpuolella.</p>';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_REQUIRED_SKILLS, 'Required Skills (comma-separated)', function () {
      $value = esc_attr(get_option(self::OPTION_REQUIRED_SKILLS, 'React, PHP, Laravel, SQL'));
      echo '<input type="text" style="width:420px" name="' . self::OPTION_REQUIRED_SKILLS . '" value="' . $value . '" placeholder="e.g. React, PHP, Laravel, SQL" />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_MIN_EXPERIENCE, 'Minimum Experience (years)', function () {
      $value = esc_attr(get_option(self::OPTION_MIN_EXPERIENCE, '5'));
      echo '<input type="number" style="width:100px" name="' . self::OPTION_MIN_EXPERIENCE . '" value="' . $value . '" min="0" step="0.5" />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_PREFERRED_LOCATION, 'Preferred Location', function () {
      $value = esc_attr(get_option(self::OPTION_PREFERRED_LOCATION, 'Finland'));
      echo '<input type="text" style="width:420px" name="' . self::OPTION_PREFERRED_LOCATION . '" value="' . $value . '" placeholder="e.g. Finland, Tampere" />';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_ADDITIONAL_CRITERIA, 'Additional Criteria', function () {
      $value = esc_attr(get_option(self::OPTION_ADDITIONAL_CRITERIA, ''));
      echo '<textarea style="width:420px; height:100px" name="' . self::OPTION_ADDITIONAL_CRITERIA . '" placeholder="Additional requirements or notes">' . esc_textarea($value) . '</textarea>';
    }, self::OPTION_GROUP, 'mh_ats_sec');
    add_settings_field(self::OPTION_JOB_OPENINGS, 'Open Positions (Title | Link)', function () {
      $value = get_option(self::OPTION_JOB_OPENINGS, "Full Stack Developer | #jafroot\nOhjelmistokehittäjä (Harjoittelu) | #jafroot");
      echo '<textarea style="width:420px; height:100px" name="' . self::OPTION_JOB_OPENINGS . '" placeholder="Job Title | #jafroot">' . esc_textarea($value) . '</textarea>';
      echo '<p class="description">Use the shortcode <code>[job_openings]</code> to display this list.</p>';
    }, self::OPTION_GROUP, 'mh_ats_sec');
  }

  public function admin_menu()
  {
    add_menu_page(
      'Job Applications',
      'Job Applications',
      'manage_options',
      'jaf-applications',
      [$this, 'render_applications_page'],
      'dashicons-id',
      26
    );

    add_submenu_page(
      'jaf-applications',
      'Job Applications',
      'Applications',
      'manage_options',
      'jaf-applications',
      [$this, 'render_applications_page']
    );

    add_submenu_page(
      'jaf-applications',
      'ATS Settings',
      'Settings',
      'manage_options',
      'mh-ats',
      [$this, 'render_settings_page']
    );
  }

  public function render_settings_page()
  {
    echo '<div class="wrap"><h1>ATS</h1><form method="post" action="options.php">';
    settings_fields(self::OPTION_GROUP);
    do_settings_sections(self::OPTION_GROUP);
    submit_button();
    echo '</form></div>';
  }

  public function render_applications_page()
  {
    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $scores = $wpdb->prefix . 'mh_ats_scores';
    $docs = $wpdb->prefix . 'mh_ats_documents';

    $applicant_id = isset($_GET['applicant_id']) ? absint($_GET['applicant_id']) : 0;

    echo '<div class="wrap"><h1>Job Applications</h1>';

    if (isset($_GET['jaf_notice'])) {
      $notice = sanitize_key($_GET['jaf_notice']);
      if ($notice === 'deleted_all') {
        echo '<div class="notice notice-success"><p>All applications deleted.</p></div>';
      } elseif ($notice === 'deleted_old') {
        $count = isset($_GET['count']) ? absint($_GET['count']) : 0;
        echo '<div class="notice notice-success"><p>Deleted ' . esc_html($count) . ' old applications.</p></div>';
      } elseif ($notice === 'deleted_single') {
        echo '<div class="notice notice-success"><p>Application deleted.</p></div>';
      } elseif ($notice === 'export_failed') {
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
        admin_url('admin-post.php?action=jaf_export&applicant_id=' . (int) $applicant_id),
        'jaf_export_' . $applicant_id
      );

      echo '<table class="widefat striped" style="max-width: 980px">';
      echo '<tbody>';
      echo '<tr><th>Name</th><td>' . esc_html($row->firstname . ' ' . $row->lastname) . '</td></tr>';
      echo '<tr><th>Haettu työpaikka / Position</th><td><strong style="color: #2271b1; font-size: 1.15em;">' . esc_html(!empty($row->job_title) ? $row->job_title : '–') . '</strong></td></tr>';
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
            admin_url('admin-post.php?action=jaf_download&applicant_id=' . (int) $applicant_id . '&type=' . urlencode($type)),
            'jaf_download_' . $applicant_id . '_' . $type
          );
          $doc_links[] = '<a href="' . esc_url($download_url) . '">' . esc_html($label) . '</a>';
        }
      }
      echo $doc_links ? implode(' | ', $doc_links) : 'No documents';
      echo '</td></tr>';

      echo '<tr><th>GDPR Export</th><td><a class="button" href="' . esc_url($export_url) . '">Export applicant data</a></td></tr>';

      echo '</tbody></table>';
      echo '</div>';
      return;
    }

    $rows = $wpdb->get_results(
      "SELECT a.id, a.created_at, a.firstname, a.lastname, a.email, a.job_title, s.score, s.status
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
    echo '<form method="post" action="' . esc_url($delete_all_url) . '" onsubmit="return confirm(\'Delete ALL applications? This cannot be undone.\');">';
    echo '<button type="submit" class="button button-secondary">Delete all applications</button>';
    echo '</form>';
    echo '<form method="post" action="' . esc_url($delete_old_url) . '" onsubmit="return confirm(\'Delete applications older than 6 months?\');">';
    echo '<button type="submit" class="button">Delete old applications (6 months)</button>';
    echo '</form>';
    echo '</div>';

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>Date</th><th>Name</th><th>Email</th><th>Haettu työpaikka / Position</th><th>Score</th><th>Status</th><th>Actions</th>';
    echo '</tr></thead><tbody>';

    if (!$rows) {
      echo '<tr><td colspan="7">No applications found.</td></tr>';
    } else {
      foreach ($rows as $row) {
        $view_url = admin_url('admin.php?page=jaf-applications&applicant_id=' . (int) $row->id);
        $export_url = wp_nonce_url(
          admin_url('admin-post.php?action=jaf_export&applicant_id=' . (int) $row->id),
          'jaf_export_' . (int) $row->id
        );
        $delete_url = wp_nonce_url(
          admin_url('admin-post.php?action=jaf_delete_single&applicant_id=' . (int) $row->id),
          'jaf_delete_single_' . (int) $row->id
        );
        echo '<tr>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '<td>' . esc_html($row->firstname . ' ' . $row->lastname) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td><strong style="color: #1d2327;">' . esc_html(!empty($row->job_title) ? $row->job_title : '–') . '</strong></td>';
        echo '<td>' . esc_html($row->score) . '</td>';
        echo '<td>' . esc_html($row->status) . '</td>';
        echo '<td>';
        echo '<a class="button" href="' . esc_url($view_url) . '">View</a> ';
        echo '<a class="button" href="' . esc_url($export_url) . '">Export</a> ';
        echo '<a class="button button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Delete this application? This cannot be undone.\');">Delete</a>';
        echo '</td>';
        echo '</tr>';
      }
    }

    echo '</tbody></table>';
    echo '</div>';
  }

  public function register_routes()
  {
    register_rest_route(self::REST_NS, '/applications', [
      'methods' => 'POST',
      'callback' => [$this, 'handle_submit'],
      'permission_callback' => '__return_true',
    ]);
  }

  private function format_multiline_field($text)
  {
    $value = trim((string) $text);
    $lines = preg_split("/\r\n|\r|\n/", $value);

    if (count($lines) <= 1) {
      if (strpos($value, ';') !== false) {
        $lines = explode(';', $value);
      } else {
        $lines = preg_split('/(?<=\.)\s+/', $value);
      }
    }

    $lines = array_values(array_filter(array_map('trim', $lines), 'strlen'));
    if ($lines) {
      return '<div>' . implode('<br>', array_map('esc_html', $lines)) . '</div>';
    }

    return esc_html($value);
  }

  public function allow_public_rest($result)
  {
    if (is_wp_error($result) && $result->get_error_code() === 'rest_cookie_invalid_nonce') {
      $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
      $prefix = '/' . rest_get_url_prefix() . '/' . self::REST_NS . '/applications';
      if ($uri && strpos($uri, $prefix) !== false) {
        return null;
      }

      // Support non-pretty permalinks: /?rest_route=/mh-ats/v1/applications
      $rest_route = '';
      if (isset($_GET['rest_route'])) {
        $rest_route = wp_unslash($_GET['rest_route']);
      } elseif ($uri) {
        $parts = wp_parse_url($uri);
        if (!empty($parts['query'])) {
          parse_str($parts['query'], $query);
          $rest_route = isset($query['rest_route']) ? $query['rest_route'] : '';
        }
      }

      if ($rest_route && strpos($rest_route, '/' . self::REST_NS . '/applications') !== false) {
        return null;
      }
    }
    return $result;
  }

  public function delete_all_applications()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Forbidden', 403);
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jaf_delete_all')) {
      wp_die('Invalid nonce', 403);
    }

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $ids = $wpdb->get_col("SELECT id FROM $applicants");
    foreach ($ids as $id) {
      $this->delete_applicant_and_files((int) $id);
    }

    wp_safe_redirect(admin_url('admin.php?page=jaf-applications&jaf_notice=deleted_all'));
    exit;
  }

  public function delete_old_applications()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Forbidden', 403);
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jaf_delete_old')) {
      wp_die('Invalid nonce', 403);
    }

    $cutoff = gmdate('Y-m-d H:i:s', strtotime('-6 months'));

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $ids = $wpdb->get_col($wpdb->prepare(
      "SELECT id FROM $applicants WHERE created_at < %s",
      $cutoff
    ));

    $count = 0;
    foreach ($ids as $id) {
      $this->delete_applicant_and_files((int) $id);
      $count++;
    }

    wp_safe_redirect(admin_url('admin.php?page=jaf-applications&jaf_notice=deleted_old&count=' . (int) $count));
    exit;
  }

  public function delete_single_application()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Forbidden', 403);
    }

    $applicant_id = isset($_GET['applicant_id']) ? absint($_GET['applicant_id']) : 0;
    if (!$applicant_id) {
      wp_die('Not found', 404);
    }

    $nonce_action = 'jaf_delete_single_' . $applicant_id;
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
      wp_die('Invalid nonce', 403);
    }

    $this->delete_applicant_and_files($applicant_id);

    wp_safe_redirect(admin_url('admin.php?page=jaf-applications&jaf_notice=deleted_single'));
    exit;
  }

  public function export_applicant_data()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Forbidden', 403);
    }

    $applicant_id = isset($_GET['applicant_id']) ? absint($_GET['applicant_id']) : 0;
    if (!$applicant_id) {
      wp_die('Not found', 404);
    }

    $nonce_action = 'jaf_export_' . $applicant_id;
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
      wp_die('Invalid nonce', 403);
    }

    global $wpdb;
    $applicants = $wpdb->prefix . 'mh_ats_applicants';
    $scores = $wpdb->prefix . 'mh_ats_scores';
    $docs = $wpdb->prefix . 'mh_ats_documents';

    $applicant = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $applicants WHERE id = %d",
      $applicant_id
    ), ARRAY_A);

    if (!$applicant) {
      wp_die('Not found', 404);
    }

    $score = $wpdb->get_row($wpdb->prepare(
      "SELECT score, status, reason FROM $scores WHERE applicant_id = %d",
      $applicant_id
    ), ARRAY_A);

    $doc_rows = $wpdb->get_results($wpdb->prepare(
      "SELECT type, attachment_id, file_path, file_name FROM $docs WHERE applicant_id = %d",
      $applicant_id
    ), ARRAY_A);

    $export = [
      'applicant' => $applicant,
      'score' => $score,
      'documents' => []
    ];

    foreach ($doc_rows as $doc_row) {
      $export['documents'][] = [
        'type' => $doc_row['type'],
        'file_name' => $doc_row['file_name'],
        'file_path' => $doc_row['file_path'],
        'attachment_id' => $doc_row['attachment_id']
      ];
    }

    if (!class_exists('ZipArchive')) {
      wp_safe_redirect(admin_url('admin.php?page=jaf-applications&jaf_notice=export_failed'));
      exit;
    }

    $zip_path = wp_tempnam('jaf-export');
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::OVERWRITE) !== true) {
      wp_safe_redirect(admin_url('admin.php?page=jaf-applications&jaf_notice=export_failed'));
      exit;
    }

    $json = wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $zip->addFromString('applicant.json', $json);

    foreach ($doc_rows as $doc_row) {
      $file_path = $doc_row['file_path'];
      if (!$file_path && !empty($doc_row['attachment_id'])) {
        $file_path = get_attached_file((int) $doc_row['attachment_id']);
      }

      if ($file_path && file_exists($file_path)) {
        $safe_name = $doc_row['file_name'] ? $doc_row['file_name'] : basename($file_path);
        $safe_name = sanitize_file_name($safe_name);
        $zip->addFile($file_path, $doc_row['type'] . '-' . $safe_name);
      }
    }

    $zip->close();

    $download_name = 'applicant-' . $applicant_id . '-export.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    @unlink($zip_path);
    exit;
  }

  private function delete_applicant_and_files($applicant_id)
  {
    global $wpdb;
    $docs_table = $wpdb->prefix . 'mh_ats_documents';
    $applicants = $wpdb->prefix . 'mh_ats_applicants';

    $doc_rows = $wpdb->get_results($wpdb->prepare(
      "SELECT attachment_id, file_path FROM $docs_table WHERE applicant_id = %d",
      $applicant_id
    ));

    foreach ($doc_rows as $doc_row) {
      if (!empty($doc_row->file_path) && file_exists($doc_row->file_path)) {
        @unlink($doc_row->file_path);
      }
      if (!empty($doc_row->attachment_id)) {
        wp_delete_attachment((int) $doc_row->attachment_id, true);
      }
    }

    $wpdb->delete($applicants, ['id' => $applicant_id], ['%d']);
  }

  public function download_document()
  {
    if (!current_user_can('manage_options')) {
      wp_die('Forbidden', 403);
    }

    $applicant_id = isset($_GET['applicant_id']) ? absint($_GET['applicant_id']) : 0;
    $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';

    if (!$applicant_id || !in_array($type, ['cv', 'motivation', 'application'], true)) {
      wp_die('Not found', 404);
    }

    $nonce_action = 'jaf_download_' . $applicant_id . '_' . $type;
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)) {
      wp_die('Invalid nonce', 403);
    }

    global $wpdb;
    $docs_table = $wpdb->prefix . 'mh_ats_documents';
    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT attachment_id, file_path, file_name FROM $docs_table WHERE applicant_id = %d AND type = %s",
      $applicant_id,
      $type
    ));

    if (!$row) {
      wp_die('Not found', 404);
    }

    $file_path = $row->file_path;
    if (!$file_path && !empty($row->attachment_id)) {
      $file_path = get_attached_file((int) $row->attachment_id);
    }

    if (!$file_path || !file_exists($file_path)) {
      wp_die('Not found', 404);
    }

    $uploads = wp_upload_dir();
    $private_dir = trailingslashit($uploads['basedir']) . 'jaf-private';
    $real_private = realpath($private_dir);
    $real_file = realpath($file_path);

    if (!$real_private || !$real_file || strpos($real_file, $real_private) !== 0) {
      wp_die('Forbidden', 403);
    }

    $filename = $row->file_name ? $row->file_name : basename($file_path);
    $filename = sanitize_file_name($filename);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
  }

  private function ensure_private_upload_dir()
  {
    $uploads = wp_upload_dir();
    $private_dir = trailingslashit($uploads['basedir']) . 'jaf-private';

    if (!file_exists($private_dir)) {
      wp_mkdir_p($private_dir);
    }

    $htaccess = $private_dir . '/.htaccess';
    if (!file_exists($htaccess)) {
      file_put_contents($htaccess, "Deny from all\n");
    }

    $index = $private_dir . '/index.php';
    if (!file_exists($index)) {
      file_put_contents($index, "<?php\n// Silence is golden.\n");
    }
  }

  /**
   * Extract text from PDF file for OpenAI processing
   * @param string $file_path Full path to PDF file
   * @return string|WP_Error Extracted text or error
   */
  private function extract_pdf_text($file_path)
  {
    if (!file_exists($file_path)) {
      return new WP_Error('file_not_found', 'PDF file not found');
    }

    // Try using pdftotext command line tool if available
    $pdftotext = 'pdftotext';
    $test = shell_exec("where $pdftotext 2>NUL"); // Windows
    if (!$test) {
      $test = shell_exec("which $pdftotext 2>/dev/null"); // Linux/Mac
    }

    if ($test) {
      $temp_txt = tempnam(sys_get_temp_dir(), 'pdf_');
      $escaped_pdf = escapeshellarg($file_path);
      $escaped_txt = escapeshellarg($temp_txt);
      exec("$pdftotext -layout $escaped_pdf $escaped_txt 2>&1", $output, $return_code);

      if ($return_code === 0 && file_exists($temp_txt)) {
        $text = file_get_contents($temp_txt);
        @unlink($temp_txt);
        return $text;
      }
    }

    // Fallback: Read first 50KB of PDF as-is (useful for simple PDFs)
    $content = file_get_contents($file_path, false, null, 0, 51200);

    // Try to extract readable text from PDF content
    if (preg_match_all('/\(([^\)]+)\)/', $content, $matches)) {
      $extracted = implode(' ', $matches[1]);
      if (strlen(trim($extracted)) > 100) {
        return $extracted;
      }
    }

    // If we get here, return what we have (may be binary)
    return substr($content, 0, 2000); // Limit size
  }

  private function handle_upload($file_array)
  {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $this->ensure_private_upload_dir();

    $original_name = sanitize_file_name($file_array['name']);
    $timestamp = gmdate('Ymd_His');
    $file_array['name'] = $timestamp . '_' . $original_name;

    $upload_filter = function ($dirs) {
      $private_dir = trailingslashit($dirs['basedir']) . 'jaf-private';
      $dirs['path'] = $private_dir;
      $dirs['url'] = $dirs['baseurl'] . '/jaf-private';
      $dirs['subdir'] = '';
      return $dirs;
    };

    add_filter('upload_dir', $upload_filter);
    $overrides = ['test_form' => false, 'mimes' => ['pdf' => 'application/pdf']];
    $movefile = wp_handle_upload($file_array, $overrides);
    remove_filter('upload_dir', $upload_filter);
    if ($movefile && !isset($movefile['error'])) {
      return [
        'file_path' => $movefile['file'],
        'file_name' => $original_name
      ];
    }
    return new WP_Error('upload_error', $movefile['error'] ?? 'Upload failed');
  }

  public function handle_submit(WP_REST_Request $req)
  {
    try {
      $this->ensure_applicant_columns();
      $this->ensure_documents_columns();

      // Honeypot check for bots
      if (!empty($req['_gotcha']) || !empty($_POST['_gotcha'])) {
        return new WP_REST_Response(['success' => true, 'message' => 'Application stored'], 200);
      }

      // CSRF check (for logged-in users; allow guest applicants if page cache caused nonce expiry)
      $nonce = $req->get_header('x-wp-nonce');
      if (is_user_logged_in() && $nonce && !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
        return new WP_REST_Response(['message' => 'Istunto vanhentunut (Invalid nonce). Ole hyvä ja päivitä sivu.'], 403);
      }

      // Deduplication check: estä tuplalähetykset 60 sekunnin sisällä
      $email_check = sanitize_email($req['email'] ?? '');
      $first_check = sanitize_text_field($req['firstname'] ?? '');
      $last_check = sanitize_text_field($req['lastname'] ?? '');
      global $wpdb;
      $applicants = $wpdb->prefix . 'mh_ats_applicants';

      if ($email_check && $first_check && $last_check) {
        $recent = $wpdb->get_row($wpdb->prepare(
          "SELECT id FROM $applicants WHERE email = %s AND firstname = %s AND lastname = %s AND created_at > %s ORDER BY id DESC LIMIT 1",
          $email_check,
          $first_check,
          $last_check,
          gmdate('Y-m-d H:i:s', time() - 60)
        ));
        if ($recent) {
          return new WP_REST_Response([
            'success' => true,
            'message' => 'Hakemuksesi on jo vastaanotettu onnistuneesti.',
            'applicant_id' => (int) $recent->id,
            'status' => 'received',
            'is_duplicate' => true
          ], 200);
        }
      }

      // Validate size & type quickly (hyväksy kaikki validit PDF-tiedostot)
      foreach (['cv', 'motivation', 'application'] as $key) {
        if (!empty($_FILES[$key]['name'])) {
          $filename = sanitize_file_name($_FILES[$key]['name']);
          $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          if ($ext !== 'pdf') {
            return new WP_REST_Response(['message' => 'Vain PDF-tiedostot ovat sallittuja liitteissä (' . $key . ') / Only PDF allowed'], 400);
          }
          if ($_FILES[$key]['size'] > 8192 * 1024) {
            return new WP_REST_Response(['message' => 'Tiedosto on liian suuri (max 8 Mt) (' . $key . ') / File too large'], 400);
          }
        }
      }

      // Extract job_title / position
      $job_title = sanitize_text_field($req['job_title'] ?? $req['position'] ?? '');
      if (empty($job_title)) {
        $job_title = get_option(self::OPTION_JOB_TITLE, 'Full Stack Developer');
      }

      $payload = [
        'firstname' => sanitize_text_field($req['firstname'] ?? ''),
        'lastname' => sanitize_text_field($req['lastname'] ?? ''),
        'email' => $email_check,
        'job_title' => $job_title,
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

      // Required fields check with localized Finnish & English error messages
      $field_names_fi = [
        'firstname' => 'Etunimi',
        'lastname' => 'Sukunimi',
        'email' => 'Sähköposti',
        'country' => 'Maa',
        'address1' => 'Katuosoite',
        'zip' => 'Postinumero',
        'city' => 'Kaupunki',
        'mobile' => 'Matkapuhelin',
        'additional' => 'Lisätiedot',
        'education' => 'Koulutus',
        'qualifications' => 'Pätevyydet',
        'skills' => 'Taidot ja osaaminen',
        'workexp' => 'Työkokemus'
      ];
      foreach (['firstname', 'lastname', 'email', 'country', 'address1', 'zip', 'city', 'mobile', 'additional', 'education', 'qualifications', 'skills', 'workexp'] as $reqf) {
        if (empty($payload[$reqf])) {
          $label = $field_names_fi[$reqf] ?? $reqf;
          return new WP_REST_Response(['message' => "Pakollinen kenttä puuttuu: $label (Missing field: $reqf)"], 400);
        }
      }

      // Check required CV file
      if (empty($_FILES['cv']['name'])) {
        return new WP_REST_Response(['message' => 'Pakollinen liite puuttuu: Ansioluettelo / CV (PDF)'], 400);
      }

      // Store applicant
      $wpdb->insert($applicants, $payload);
      $applicant_id = (int) $wpdb->insert_id;
      if (!$applicant_id) {
        return new WP_REST_Response(['message' => 'Tietokantavirhe hakemusta tallennettaessa: ' . $wpdb->last_error], 500);
      }

      // Upload files
      $docs_table = $wpdb->prefix . 'mh_ats_documents';
      foreach (['cv', 'motivation', 'application'] as $type) {
        if (!empty($_FILES[$type]['name'])) {
          $upload = $this->handle_upload($_FILES[$type]);
          if (is_wp_error($upload)) {
            return new WP_REST_Response(['message' => 'Tiedoston lataus epäonnistui: ' . $upload->get_error_message()], 400);
          }
          $inserted = $wpdb->insert($docs_table, [
            'applicant_id' => $applicant_id,
            'type' => $type,
            'attachment_id' => 0,
            'file_path' => $upload['file_path'],
            'file_name' => $upload['file_name'],
          ]);
          if ($inserted === false) {
            error_log('JAF: Failed to store document metadata: ' . $wpdb->last_error);
          }
        }
      }

      // Get ATS settings
      $required_skills_str = get_option(self::OPTION_REQUIRED_SKILLS, 'React, PHP, Laravel, SQL');
      $min_experience = (float) get_option(self::OPTION_MIN_EXPERIENCE, 5);
      $preferred_location = get_option(self::OPTION_PREFERRED_LOCATION, 'Finland');
      $additional_criteria = get_option(self::OPTION_ADDITIONAL_CRITERIA, '');

      // Parse required skills
      $required_skills = array_map('trim', explode(',', $required_skills_str));
      $required_skills_lower = array_map('strtolower', $required_skills);

      // ATS-sääntöpisteytys
      $rule_score = 0;
      $reasons = [];

      // Check required skills
      $found_skills = 0;
      $missing_skills = [];
      $skills_lower = strtolower($payload['skills']);
      foreach ($required_skills_lower as $skill) {
        if (stripos($skills_lower, $skill) !== false) {
          $found_skills++;
          $rule_score += 20 / max(1, count($required_skills));
        } else {
          $missing_skills[] = ucfirst($skill);
        }
      }
      if ($missing_skills) {
        $reasons[] = 'Missing skills: ' . implode(', ', $missing_skills);
      }

      if (!preg_match('/\b(' . (int) $min_experience . '|' . ((int) $min_experience + 1) . '|' . ((int) $min_experience + 2) . '|10)\+?\s*(years|v|vuotta)/i', $payload['workexp'])) {
        $reasons[] = 'Work experience in form may be insufficient (checked from CV)';
      }

      $location_match = false;
      $location_parts = array_map('trim', explode(',', $preferred_location));
      foreach ($location_parts as $loc) {
        if (stripos(strtolower($payload['country'] . ' ' . $payload['city']), strtolower($loc)) !== false) {
          $location_match = true;
          break;
        }
      }
      if ($location_match) {
        $rule_score += 10;
      } else {
        $reasons[] = 'Location not in preferred area: ' . $preferred_location;
      }

      if (!empty($payload['www']))
        $rule_score += 5;
      if (!empty($payload['phone']))
        $rule_score += 5;

      // OpenAI PDF-parsaus + pisteytys kaikille dokumenteille (turvattu try-catchillä ja maltillisella timeoutilla)
      $openai_key = get_option(self::OPTION_API_KEY);
      $ai_score = 0;
      $ai_reason = '';
      if ($openai_key) {
        try {
          $doc_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT type, file_path, attachment_id FROM $docs_table WHERE applicant_id=%d",
            $applicant_id
          ));

          $documents_content = [];
          foreach ($doc_rows as $doc) {
            $file_path = $doc->file_path;
            if (!$file_path && !empty($doc->attachment_id)) {
              $file_path = get_attached_file((int) $doc->attachment_id);
            }

            if ($file_path && file_exists($file_path)) {
              $text = $this->extract_pdf_text($file_path);
              if (!is_wp_error($text)) {
                $text = str_replace("\0", '', $text);
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                $documents_content[$doc->type] = substr($text, 0, 4000);
              }
            }
          }

          if (!empty($documents_content)) {
            $content_parts = [];
            $content_parts[] = "Applicant form data:";
            $content_parts[] = "Applied Position: {$job_title}";
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
              'content' => "You are an ATS (Applicant Tracking System) expert. Analyze the job application for position '{$job_title}'. Requirements: " . implode(', ', $required_skills) . ", {$min_experience}+ years experience, {$preferred_location}-based." . ($additional_criteria ? " Additional criteria: {$additional_criteria}." : "") . "\n\nAnalyze work experience PRIMARILY from the CV PDF. Return ONLY valid JSON:\n{\n  \"skills\": [\"skill1\"],\n  \"total_experience_years\": number,\n  \"cv_experience_summary\": \"summary\",\n  \"form_vs_cv_quality\": \"good\",\n  \"seniority\": \"mid\",\n  \"score0to100\": number,\n  \"reasons\": [\"reason1\"],\n  \"motivation_quality\": \"good\",\n  \"overall_fit\": \"good\"\n}\n\n" . $full_content
            ];

            $body = json_encode([
              'model' => 'gpt-4o-mini',
              'messages' => [$prompt],
              'temperature' => 0.2,
              'max_tokens' => 1000
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

            if ($body !== false) {
              $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                  'Authorization' => 'Bearer ' . $openai_key,
                  'Content-Type' => 'application/json'
                ],
                'body' => $body,
                'timeout' => 20
              ]);

              if (!is_wp_error($response)) {
                $js = json_decode(wp_remote_retrieve_body($response), true);
                $txt = $js['choices'][0]['message']['content'] ?? '';
                $txt = preg_replace('/```json\s*/', '', $txt);
                $txt = preg_replace('/```\s*$/', '', $txt);
                $txt = trim($txt);

                if (preg_match('/\{[\s\S]*\}/', $txt, $m)) {
                  $parsed = json_decode($m[0], true);
                  if (is_array($parsed)) {
                    $ai_score = (float) ($parsed['score0to100'] ?? 0);
                    $ai_reasons = (array) ($parsed['reasons'] ?? []);
                    $cv_experience = $parsed['total_experience_years'] ?? null;
                    if ($cv_experience !== null) {
                      $ai_reasons[] = "CV work experience: {$cv_experience} years";
                      if ($cv_experience >= $min_experience) {
                        $rule_score += 20;
                      }
                    }
                    $ai_reason = 'AI: ' . implode('; ', $ai_reasons);
                  }
                }
              }
            }
          }
        } catch (Throwable $oai_e) {
          error_log('JAF: OpenAI evaluation exception: ' . $oai_e->getMessage());
        }
      }

      $total = min(100, (int) round($rule_score + ($ai_score * 0.5)));
      $status = $total >= 70 ? 'accepted' : ($total >= 50 ? 'maybe' : 'rejected');
      $reason = trim(implode('; ', $reasons) . ($ai_reason ? '; ' . $ai_reason : ''));

      $scores_table = $wpdb->prefix . 'mh_ats_scores';
      $wpdb->insert($scores_table, [
        'applicant_id' => $applicant_id,
        'score' => $total,
        'reason' => $reason,
        'status' => $status
      ]);

      $admin_email = get_option('admin_email');
      if ($admin_email) {
        $subject = 'Uusi työhakemus vastaanotettu: ' . $job_title . ' (' . $payload['firstname'] . ' ' . $payload['lastname'] . ')';
        $message = "Uusi työhakemus on vastaanotettu i4ware ATS -järjestelmään.\n\n";
        $message .= "Haettu tehtävä: {$job_title}\n";
        $message .= "Nimi: {$payload['firstname']} {$payload['lastname']}\n";
        $message .= "Sähköposti: {$payload['email']}\n";
        $message .= "Puhelin: {$payload['mobile']}\n";
        $message .= "Pisteet: {$total}/100 (Tila: {$status})\n";
        $message .= "Lähetysaika: " . gmdate('Y-m-d H:i:s') . " UTC\n\n";
        $message .= "Tarkastele hakemusta hallinnassa: " . admin_url('admin.php?page=jaf-applications&applicant_id=' . $applicant_id);
        wp_mail($admin_email, $subject, $message);
      }

      return new WP_REST_Response([
        'success' => true,
        'message' => 'Hakemus vastaanotettu onnistuneesti!',
        'applicant_id' => $applicant_id,
        'job_title' => $job_title,
        'status' => $status,
        'score' => $total
      ], 200);
    } catch (Throwable $e) {
      error_log('JAF handle_submit error: ' . $e->getMessage());
      return new WP_REST_Response([
        'success' => false,
        'message' => 'Palvelinvirhe hakemuksen käsittelyssä: ' . $e->getMessage()
      ], 500);
    }
  }
}

new JAF_Plugin();
// EOF
