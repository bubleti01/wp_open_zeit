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
        ], (array) $atts, 'igw_wp_open_zeit_text');

        $dt = null;
        if (! empty($atts['datetime'])) {
            $tz = wp_timezone();
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $atts['datetime'], $tz) ?: null;
        }

        $is_open      = $this->service->is_open_now($dt);
        $status_text  = $is_open ? $atts['open_text'] : $atts['closed_text'];
        $status_class = $is_open ? 'igw-open' : 'igw-closed';

        $h2_classes = 'igw-openzeit-text';
        $extra_classes = $this->sanitize_classes($atts['class']);
        if ($extra_classes !== '') {
            $h2_classes .= ' ' . $extra_classes;
        }

        return sprintf(
            '<h2 class="%s"><span class="igw-openzeit-status %s">%s</span></h2>',
            esc_attr($h2_classes),
            esc_attr($status_class),
            esc_html($status_text)
        );
    }

    public function render_short($atts)
    {
        $this->enqueue_public_assets();

        $groups = $this->service->get_grouped_weekly_lines();

        $rows = '';
        foreach ($groups as $group) {
            $start_name = $this->weekday_name($group['start']);
            $end_name   = $this->weekday_name($group['end']);
            $day_label  = $group['start'] === $group['end'] ? $start_name : $start_name . ' – ' . $end_name;

            $rows .= sprintf(
                '<tr><td class="igw-openzeit-day">%s</td><td class="igw-openzeit-time">%s</td></tr>',
                esc_html($day_label),
                esc_html($group['repr'])
            );
        }

        return sprintf(
            '<table class="igw-openzeit-table igw-openzeit-short"><thead><tr><th scope="col">%s</th><th scope="col">%s</th></tr></thead><tbody>%s</tbody></table>',
            esc_html__('Tag', 'igw_wp_open_zeit'),
            esc_html__('Zeiten', 'igw_wp_open_zeit'),
            $rows
        );
    }

    public function render_week($atts)
    {
        $this->enqueue_public_assets();

        $days = $this->service->get_current_week_days();
        $rows = '';

        foreach ($days as $day) {
            $display = $this->service->get_day_display($day);
            $rows .= sprintf(
                '<tr><td class="igw-openzeit-day">%s</td><td class="igw-openzeit-time">%s</td></tr>',
                esc_html($this->weekday_name((int) $day->format('w'))),
                esc_html($display['label'])
            );
        }

        return sprintf(
            '<table class="igw-openzeit-table igw-openzeit-tage"><thead><tr><th scope="col">%s</th><th scope="col">%s</th></tr></thead><tbody>%s</tbody></table>',
            esc_html__('Tag', 'igw_wp_open_zeit'),
            esc_html__('Zeiten', 'igw_wp_open_zeit'),
            $rows
        );
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

    private function sanitize_classes($classes)
    {
        $classes = is_string($classes) ? $classes : '';
        $parts   = preg_split('/\s+/', trim($classes));
        $clean   = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $cleaned = sanitize_html_class($part);
            if ($cleaned !== '') {
                $clean[] = $cleaned;
            }
        }

        return implode(' ', array_unique($clean));
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
