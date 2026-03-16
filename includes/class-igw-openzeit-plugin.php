<?php
/**
 * Main plugin bootstrap.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Plugin {

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * @var IGW_Openzeit_Service
	 */
	protected $service;

	/**
	 * @var IGW_Openzeit_Shortcodes
	 */
	protected $shortcodes;

	/**
	 * @var IGW_Openzeit_Admin|null
	 */
	protected $admin = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init plugin components.
	 *
	 * @return void
	 */
	public function init() {
		load_plugin_textdomain( 'igw_wp_open_zeit', false, dirname( plugin_basename( IGW_WP_OPEN_ZEIT_FILE ) ) . '/languages' );

		$weekly_hours = get_option( 'igw_wp_open_zeit_hours', array() );
		if ( ! is_array( $weekly_hours ) ) {
			$weekly_hours = array();
		}

		$this->service    = new IGW_Openzeit_Service( $weekly_hours );
		$this->shortcodes = new IGW_Openzeit_Shortcodes( $this->service );

		if ( is_admin() ) {
			$this->admin = new IGW_Openzeit_Admin();
			$this->admin->hooks();
		}

		$this->register_shortcodes();
	}

	/**
	 * Register shortcode aliases.
	 *
	 * @return void
	 */
	protected function register_shortcodes() {
		add_shortcode( 'igw_wp_open_zeit_text', array( $this->shortcodes, 'render_text' ) );
		add_shortcode( 'open_zeit_text', array( $this->shortcodes, 'render_text' ) );

		add_shortcode( 'igw_wp_open_zeit_tage', array( $this->shortcodes, 'render_days' ) );
		add_shortcode( 'open_zeit_tage', array( $this->shortcodes, 'render_days' ) );

		add_shortcode( 'igw_wp_open_zeit_short', array( $this->shortcodes, 'render_short' ) );
		add_shortcode( 'open_zeit_short', array( $this->shortcodes, 'render_short' ) );
	}
}
