<?php
if (! defined('ABSPATH')) {
    exit;
}

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

    <?php settings_errors('igw_openzeit_weekly_group'); ?>

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
</div>
