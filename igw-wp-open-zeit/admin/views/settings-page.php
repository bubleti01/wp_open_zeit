<?php
if (! defined('ABSPATH')) {
    exit;
}

$notice      = isset($_GET['igw_notice']) ? sanitize_text_field(wp_unslash($_GET['igw_notice'])) : '';
$notice_type = isset($_GET['igw_notice_type']) ? sanitize_key(wp_unslash($_GET['igw_notice_type'])) : 'updated';

function igw_openzeit_weekday_name($day)
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

    return $names[$day];
}
?>
<div class="wrap">
    <h1><?php esc_html_e('WP Plugin Öffnungszeiten', 'igw_wp_open_zeit'); ?></h1>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice_type === 'error' ? 'error' : 'success'); ?> is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
    <?php endif; ?>

    <?php settings_errors('igw_openzeit_weekly_group'); ?>

    <nav class="nav-tab-wrapper">
        <a class="nav-tab <?php echo $tab === 'weekly' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'igw-wp-open-zeit', 'tab' => 'weekly'], admin_url('options-general.php'))); ?>"><?php esc_html_e('Wochentage', 'igw_wp_open_zeit'); ?></a>
        <a class="nav-tab <?php echo $tab === 'holidays' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'igw-wp-open-zeit', 'tab' => 'holidays'], admin_url('options-general.php'))); ?>"><?php esc_html_e('Ferien', 'igw_wp_open_zeit'); ?></a>
        <a class="nav-tab <?php echo $tab === 'exceptions' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'igw-wp-open-zeit', 'tab' => 'exceptions'], admin_url('options-general.php'))); ?>"><?php esc_html_e('Ausnahmen', 'igw_wp_open_zeit'); ?></a>
    </nav>

    <?php if ($tab === 'weekly') : ?>
        <form method="post" action="options.php">
            <?php settings_fields('igw_openzeit_weekly_group'); ?>
            <table class="form-table">
                <?php for ($day = 0; $day <= 6; $day++) :
                    $day_data = isset($data['weekly'][$day]) ? $data['weekly'][$day] : ['closed' => false, 'intervals' => []];
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(igw_openzeit_weekday_name($day)); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" class="igw-closed-toggle" name="<?php echo esc_attr(IGW_WP_OPEN_ZEIT_OPTION_KEY); ?>[weekly][<?php echo esc_attr($day); ?>][closed]" value="1" <?php checked(! empty($day_data['closed'])); ?> />
                                <?php esc_html_e('Geschlossen', 'igw_wp_open_zeit'); ?>
                            </label>
                            <div class="igw-intervals" data-day="<?php echo esc_attr($day); ?>">
                                <?php foreach ($day_data['intervals'] as $idx => $interval) : ?>
                                    <div class="igw-interval-row">
                                        <input type="time" name="<?php echo esc_attr(IGW_WP_OPEN_ZEIT_OPTION_KEY); ?>[weekly][<?php echo esc_attr($day); ?>][intervals][<?php echo esc_attr($idx); ?>][start]" value="<?php echo esc_attr($interval['start']); ?>" />
                                        <input type="time" name="<?php echo esc_attr(IGW_WP_OPEN_ZEIT_OPTION_KEY); ?>[weekly][<?php echo esc_attr($day); ?>][intervals][<?php echo esc_attr($idx); ?>][end]" value="<?php echo esc_attr($interval['end']); ?>" />
                                        <button type="button" class="button igw-remove-interval"><?php esc_html_e('Zeitraum löschen', 'igw_wp_open_zeit'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button igw-add-interval" data-day="<?php echo esc_attr($day); ?>"><?php esc_html_e('+ Zeitraum', 'igw_wp_open_zeit'); ?></button>
                        </td>
                    </tr>
                <?php endfor; ?>
            </table>
            <?php submit_button(__('Wochentage speichern', 'igw_wp_open_zeit')); ?>
        </form>
    <?php elseif ($tab === 'holidays') : ?>
        <h2><?php esc_html_e('Ferien', 'igw_wp_open_zeit'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Name', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Start', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Ende', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Aktionen', 'igw_wp_open_zeit'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($data['holidays'] as $holiday) : ?>
                    <tr>
                        <td><?php echo esc_html($holiday['name']); ?></td><td><?php echo esc_html($holiday['start_date']); ?></td><td><?php echo esc_html($holiday['end_date']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('igw_openzeit_holiday_delete'); ?>
                                <input type="hidden" name="action" value="igw_openzeit_holiday_delete" />
                                <input type="hidden" name="id" value="<?php echo esc_attr($holiday['id']); ?>" />
                                <button type="submit" class="button button-link-delete"><?php esc_html_e('Löschen', 'igw_wp_open_zeit'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3><?php esc_html_e('Neue Ferien', 'igw_wp_open_zeit'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('igw_openzeit_holiday_save'); ?>
            <input type="hidden" name="action" value="igw_openzeit_holiday_save" />
            <input type="text" name="name" required maxlength="80" placeholder="<?php esc_attr_e('Name', 'igw_wp_open_zeit'); ?>" />
            <input type="date" name="start_date" required />
            <input type="date" name="end_date" required />
            <?php submit_button(__('Ferien speichern', 'igw_wp_open_zeit'), 'primary', 'submit', false); ?>
        </form>
    <?php else : ?>
        <h2><?php esc_html_e('Ausnahmen', 'igw_wp_open_zeit'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Name', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Datum', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Zeiten', 'igw_wp_open_zeit'); ?></th><th><?php esc_html_e('Aktionen', 'igw_wp_open_zeit'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($data['exceptions'] as $exception) : ?>
                    <tr>
                        <td><?php echo esc_html($exception['name']); ?></td>
                        <td><?php echo esc_html($exception['date']); ?></td>
                        <td><?php echo ! empty($exception['closed']) ? esc_html__('Geschlossen', 'igw_wp_open_zeit') : esc_html(implode(', ', array_map(static function ($interval) { return $interval['start'] . ' - ' . $interval['end']; }, $exception['intervals']))); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('igw_openzeit_exception_delete'); ?>
                                <input type="hidden" name="action" value="igw_openzeit_exception_delete" />
                                <input type="hidden" name="id" value="<?php echo esc_attr($exception['id']); ?>" />
                                <button type="submit" class="button button-link-delete"><?php esc_html_e('Löschen', 'igw_wp_open_zeit'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3><?php esc_html_e('Neue Ausnahme', 'igw_wp_open_zeit'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="igw-exception-form">
            <?php wp_nonce_field('igw_openzeit_exception_save'); ?>
            <input type="hidden" name="action" value="igw_openzeit_exception_save" />
            <input type="text" name="name" required maxlength="80" placeholder="<?php esc_attr_e('Name', 'igw_wp_open_zeit'); ?>" />
            <input type="date" name="date" required />
            <label><input type="checkbox" class="igw-closed-toggle" name="closed" value="1" /> <?php esc_html_e('Geschlossen', 'igw_wp_open_zeit'); ?></label>
            <div class="igw-intervals" data-day="exception">
                <div class="igw-interval-row">
                    <input type="time" name="intervals[0][start]" />
                    <input type="time" name="intervals[0][end]" />
                    <button type="button" class="button igw-remove-interval"><?php esc_html_e('Zeitraum löschen', 'igw_wp_open_zeit'); ?></button>
                </div>
            </div>
            <button type="button" class="button igw-add-interval" data-day="exception"><?php esc_html_e('+ Zeitraum', 'igw_wp_open_zeit'); ?></button>
            <?php submit_button(__('Ausnahme speichern', 'igw_wp_open_zeit'), 'primary', 'submit', false); ?>
        </form>
    <?php endif; ?>
</div>
