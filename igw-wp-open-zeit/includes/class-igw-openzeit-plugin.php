<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Plugin
{
    private $repository;
    private $validator;
    private $service;
    private $shortcodes;
    private $settings_page_hook = '';

    public static function activate()
    {
        $repo = new IGW_Openzeit_Repository();
        if (! get_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, false)) {
            add_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $repo->get_default_data(), '', false);
        }
    }

    public static function deactivate()
    {
        // Keep data on deactivation.
    }

    public function __construct()
    {
        $this->repository = new IGW_Openzeit_Repository();
        $this->validator  = new IGW_Openzeit_Validator();
        $this->service    = new IGW_Openzeit_Service($this->repository);
        $this->shortcodes = new IGW_Openzeit_Shortcodes($this->service);
    }

    public function run()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this->shortcodes, 'register']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('igw_wp_open_zeit', false, dirname(plugin_basename(IGW_WP_OPEN_ZEIT_FILE)) . '/languages');
    }

    public function register_admin_menu()
    {
        $this->settings_page_hook = add_options_page(
            __('WP Öffnungszeiten', 'igw_wp_open_zeit'),
            __('WP Öffnungszeiten', 'igw_wp_open_zeit'),
            'manage_options',
            'igw-wp-open-zeit',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('igw_openzeit_weekly_group', IGW_WP_OPEN_ZEIT_OPTION_KEY, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input)
    {
        $current       = $this->repository->get_data();
        $weekly_input  = isset($input['weekly']) && is_array($input['weekly']) ? $input['weekly'] : [];
        $weekly_result = $this->validator->validate_weekly($weekly_input);

        if (! empty($weekly_result['errors'])) {
            foreach ($weekly_result['errors'] as $error) {
                add_settings_error('igw_openzeit_weekly_group', 'igw_openzeit_weekly_error', $error, 'error');
            }
            return $current;
        }

        $current['weekly']         = $weekly_result['weekly'];
        $current['schema_version'] = 1;

        add_settings_error('igw_openzeit_weekly_group', 'igw_openzeit_weekly_saved', __('Wochentage gespeichert.', 'igw_wp_open_zeit'), 'updated');

        return $current;
    }

    public function enqueue_admin_assets($hook)
    {
        if ($hook !== $this->settings_page_hook) {
            return;
        }

        wp_enqueue_style('igw-wp-open-zeit-admin', IGW_WP_OPEN_ZEIT_URL . 'admin/assets/admin.css', [], IGW_WP_OPEN_ZEIT_VERSION);
        wp_enqueue_script('igw-wp-open-zeit-admin', IGW_WP_OPEN_ZEIT_URL . 'admin/assets/admin.js', ['jquery'], IGW_WP_OPEN_ZEIT_VERSION, true);
    }

    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'igw_wp_open_zeit'));
        }

        $data = $this->repository->get_data();
        include IGW_WP_OPEN_ZEIT_DIR . 'admin/views/settings-page.php';
    }
}
