<?php
/**
 * Plugin Name: IGW WP Öffnungszeiten
 * Plugin URI: https://igo2web.com/de/wordpress-plugins-von-igw-design/wp_open_zeit
 * Description: Erstellen/Verwalten Sie Öffnungszeiten in WordPress und zeigen Sie diese in vielen verschiedenen Widgets und Shortcodes an.
 * Version: 1.0.11
 * Requires at least: 6.0
 * Author: IGW Design
 * Author URI: https://igo2web.com
 * Text Domain: igw_wp_open_zeit
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IGW_WP_OPEN_ZEIT_VERSION', '1.0.11' );
define( 'IGW_WP_OPEN_ZEIT_FILE', __FILE__ );
define( 'IGW_WP_OPEN_ZEIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'IGW_WP_OPEN_ZEIT_URL', plugin_dir_url( __FILE__ ) );

require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-repository.php';
require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-validator.php';
require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-service.php';
require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-shortcodes.php';
require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-admin.php';
require_once IGW_WP_OPEN_ZEIT_PATH . 'includes/class-igw-openzeit-plugin.php';

function igw_wp_open_zeit() {
	return IGW_Openzeit_Plugin::instance();
}

igw_wp_open_zeit();
