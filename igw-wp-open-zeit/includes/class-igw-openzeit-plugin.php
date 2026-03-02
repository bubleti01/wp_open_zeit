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

        add_action('admin_post_igw_openzeit_save_holiday', [$this, 'handle_holiday_save']);
        add_action('admin_post_igw_openzeit_delete_holiday', [$this, 'handle_holiday_delete']);
        add_action('admin_post_igw_openzeit_save_exception', [$this, 'handle_exception_save']);
        add_action('admin_post_igw_openzeit_delete_exception', [$this, 'handle_exception_delete']);
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
        $tab  = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'weekly';
        if (! in_array($tab, ['weekly', 'holidays', 'exceptions'], true)) {
            $tab = 'weekly';
        }

        include IGW_WP_OPEN_ZEIT_DIR . 'admin/views/settings-page.php';
    }

    public function handle_holiday_save()
    {
        $this->guard_admin_action('igw_openzeit_save_holiday');

        $data      = $this->repository->get_data();
        $record_in = [
            'id'         => isset($_POST['holiday_id']) ? wp_unslash($_POST['holiday_id']) : '',
            'name'       => isset($_POST['name']) ? wp_unslash($_POST['name']) : '',
            'start_date' => isset($_POST['start_date']) ? wp_unslash($_POST['start_date']) : '',
            'end_date'   => isset($_POST['end_date']) ? wp_unslash($_POST['end_date']) : '',
        ];

        $validated = $this->validator->validate_holiday($record_in);
        if (! empty($validated['errors'])) {
            $this->redirect_with_notice('holidays', $validated['errors'][0], 'error');
        }

        $record  = $validated['record'];
        $updated = false;

        foreach ($data['holidays'] as $idx => $holiday) {
            if ($holiday['id'] === $record['id']) {
                $data['holidays'][$idx] = $record;
                $updated                 = true;
                break;
            }
        }

        if (! $updated) {
            $data['holidays'][] = $record;
        }

        $errors = $this->validator->validate_conflicts($data['holidays'], $data['exceptions']);
        if (! empty($errors)) {
            $this->redirect_with_notice('holidays', $errors[0], 'error');
        }

        update_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $data, false);
        $this->redirect_with_notice('holidays', __('Ferien gespeichert.', 'igw_wp_open_zeit'), 'updated');
    }

    public function handle_holiday_delete()
    {
        $this->guard_admin_action('igw_openzeit_delete_holiday');

        $id   = isset($_POST['holiday_id']) ? sanitize_text_field(wp_unslash($_POST['holiday_id'])) : '';
        $data = $this->repository->get_data();

        $data['holidays'] = array_values(array_filter($data['holidays'], static function ($item) use ($id) {
            return $item['id'] !== $id;
        }));

        update_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $data, false);
        $this->redirect_with_notice('holidays', __('Ferien gelöscht.', 'igw_wp_open_zeit'), 'updated');
    }

    public function handle_exception_save()
    {
        $this->guard_admin_action('igw_openzeit_save_exception');

        $data      = $this->repository->get_data();
        $intervals = isset($_POST['intervals']) && is_array($_POST['intervals']) ? wp_unslash($_POST['intervals']) : [];
        $record_in = [
            'id'        => isset($_POST['exception_id']) ? wp_unslash($_POST['exception_id']) : '',
            'name'      => isset($_POST['name']) ? wp_unslash($_POST['name']) : '',
            'date'      => isset($_POST['date']) ? wp_unslash($_POST['date']) : '',
            'closed'    => isset($_POST['closed']) ? wp_unslash($_POST['closed']) : '',
            'intervals' => $intervals,
        ];

        $validated = $this->validator->validate_exception($record_in);
        if (! empty($validated['errors'])) {
            $this->redirect_with_notice('exceptions', $validated['errors'][0], 'error');
        }

        $record  = $validated['record'];
        $updated = false;

        foreach ($data['exceptions'] as $idx => $exception) {
            if ($exception['id'] === $record['id']) {
                $data['exceptions'][$idx] = $record;
                $updated                   = true;
                break;
            }
        }

        if (! $updated) {
            $data['exceptions'][] = $record;
        }

        $errors = $this->validator->validate_conflicts($data['holidays'], $data['exceptions']);
        if (! empty($errors)) {
            $this->redirect_with_notice('exceptions', $errors[0], 'error');
        }

        update_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $data, false);
        $this->redirect_with_notice('exceptions', __('Ausnahme gespeichert.', 'igw_wp_open_zeit'), 'updated');
    }

    public function handle_exception_delete()
    {
        $this->guard_admin_action('igw_openzeit_delete_exception');

        $id   = isset($_POST['exception_id']) ? sanitize_text_field(wp_unslash($_POST['exception_id'])) : '';
        $data = $this->repository->get_data();

        $data['exceptions'] = array_values(array_filter($data['exceptions'], static function ($item) use ($id) {
            return $item['id'] !== $id;
        }));

        update_option(IGW_WP_OPEN_ZEIT_OPTION_KEY, $data, false);
        $this->redirect_with_notice('exceptions', __('Ausnahme gelöscht.', 'igw_wp_open_zeit'), 'updated');
    }

    private function guard_admin_action($nonce_action)
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'igw_wp_open_zeit'));
        }

        $nonce = isset($_POST['igw_openzeit_nonce']) ? sanitize_text_field(wp_unslash($_POST['igw_openzeit_nonce'])) : '';
        if (! wp_verify_nonce($nonce, $nonce_action)) {
            wp_die(esc_html__('Ungültige Anfrage (Nonce).', 'igw_wp_open_zeit'));
        }
    }

    private function redirect_with_notice($tab, $message, $type)
    {
        $url = add_query_arg([
            'page'            => 'igw-wp-open-zeit',
            'tab'             => $tab,
            'igw_notice'      => rawurlencode($message),
            'igw_notice_type' => $type,
        ], admin_url('options-general.php'));

        wp_safe_redirect($url);
        exit;
    }
}
