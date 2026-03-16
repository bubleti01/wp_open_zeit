<?php
/**
 * Plugin Name: WP Plugin Öffnungszeiten
 * Plugin URI: https://igo2web.com/de/wordpress-plugins-von-igw-design/wp_open_zeit
 * Description: Erstellen/Verwalten Sie Öffnungszeiten in WordPress und zeigen Sie diese in vielen verschiedenen Widgets und Shortcodes an.
 * Version: 1.0.6
 * Requires at least: 6.0
 * Author: IGW Design
 * Author URI: https://igo2web.com
 * Text Domain: igw_wp_open_zeit
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('IGW_WP_OPEN_ZEIT_VERSION', '1.0.6');
define('IGW_WP_OPEN_ZEIT_FILE', __FILE__);
define('IGW_WP_OPEN_ZEIT_DIR', plugin_dir_path(__FILE__));
define('IGW_WP_OPEN_ZEIT_URL', plugin_dir_url(__FILE__));
define('IGW_WP_OPEN_ZEIT_OPTION_KEY', 'igw_wp_open_zeit_data');

require_once IGW_WP_OPEN_ZEIT_DIR . 'includes/class-igw-openzeit-repository.php';
require_once IGW_WP_OPEN_ZEIT_DIR . 'includes/class-igw-openzeit-validator.php';
require_once IGW_WP_OPEN_ZEIT_DIR . 'includes/class-igw-openzeit-service.php';
require_once IGW_WP_OPEN_ZEIT_DIR . 'includes/class-igw-openzeit-shortcodes.php';
require_once IGW_WP_OPEN_ZEIT_DIR . 'includes/class-igw-openzeit-plugin.php';

register_activation_hook(__FILE__, ['IGW_Openzeit_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['IGW_Openzeit_Plugin', 'deactivate']);

function igw_wp_open_zeit_run_plugin()
{
    $plugin = new IGW_Openzeit_Plugin();
    $plugin->run();
}

igw_wp_open_zeit_run_plugin();
