<?php

if (! defined('ABSPATH')) {
    exit;
}

class IGW_Openzeit_Shortcodes
{
    private $service;
    private $assets_enqueued = false;

    public function __construct(IGW_Openzeit_Service $service)
    {
        $this->service = $service;
    }

    public function register()
    {
        add_shortcode('igw_wp_open_zeit_text', [$this, 'render_status']);
        add_shortcode('open_zeit_text', [$this, 'render_status']);

        add_shortcode('igw_wp_open_zeit_short', [$this, 'render_short']);
        add_shortcode('open_zeit_short', [$this, 'render_short']);

        add_shortcode('igw_wp_open_zeit_tage', [$this, 'render_week']);
        add_shortcode('open_zeit_tage', [$this, 'render_week']);
    }

    public function render_status($atts)
    {
        $this->enqueue_public_assets();

        $atts = shortcode_atts([
            'datetime'    => '',
            'open_text'   => __('Geöffnet', 'igw_wp_open_zeit'),
            'closed_text' => __('Geschlossen', 'igw_wp_open_zeit'),
            'class'       => '',
        ], $atts, 'igw_wp_open_zeit_text');

        $dt = null;
        if (! empty($atts['datetime'])) {
            $tz = wp_timezone();
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $atts['datetime'], $tz) ?: null;
        }

        $is_open = $this->service->is_open_now($dt);
        $text    = $is_open ? $atts['open_text'] : $atts['closed_text'];
        $class   = $is_open ? 'igw-open' : 'igw-closed';

        if (! empty($atts['class'])) {
            $class .= ' ' . sanitize_html_class($atts['class']);
        }

        return sprintf('<span class="%s">%s</span>', esc_attr($class), esc_html($text));
    }

    public function render_short($atts)
    {
        $groups = $this->service->get_grouped_weekly_lines();
        if (empty($groups)) {
            return '';
        }

        $start_of_week = (int) get_option('start_of_week', 1);
        $ordered_days = [];
        for ($i = 0; $i < 7; $i++) {
            $ordered_days[] = ($start_of_week + $i) % 7;
        }

        $lines = [];
        foreach ($groups as $group) {
            $start_name = $this->weekday_name($group['start']);
            $end_name   = $this->weekday_name($group['end']);
            $label = $group['start'] === $group['end'] ? $start_name : $start_name . ' – ' . $end_name;
            $lines[] = sprintf('%s | %s', $label, $group['repr']);
        }

        return '<div class="igw-openzeit-short">' . esc_html(implode("\n", $lines)) . '</div>';
    }

    public function render_week($atts)
    {
        $days  = $this->service->get_current_week_days();
        $lines = [];

        foreach ($days as $day) {
            $display = $this->service->get_day_display($day);
            $lines[] = sprintf('%s %s', $this->weekday_name((int) $day->format('w')), $display['label']);
        }

        return '<div class="igw-openzeit-week">' . esc_html(implode("\n", $lines)) . '</div>';
    }

    private function weekday_name($weekday)
    {
        $names = [
            __('Sonntag', 'igw_wp_open_zeit'),
            __('Montag', 'igw_wp_open_zeit'),
            __('Dienstag', 'igw_wp_open_zeit'),
            __('Mittwoch', 'igw_wp_open_zeit'),
            __('Donnerstag', 'igw_wp_open_zeit'),
            __('Freitag', 'igw_wp_open_zeit'),
            __('Samstag', 'igw_wp_open_zeit'),
        ];

        return isset($names[$weekday]) ? $names[$weekday] : '';
    }

    private function enqueue_public_assets()
    {
        if ($this->assets_enqueued) {
            return;
        }

        wp_enqueue_style(
            'igw-wp-open-zeit-public',
            IGW_WP_OPEN_ZEIT_URL . 'public/assets/public.css',
            [],
            IGW_WP_OPEN_ZEIT_VERSION
        );
        $this->assets_enqueued = true;
    }
}
