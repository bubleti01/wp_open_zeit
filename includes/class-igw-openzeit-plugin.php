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
	protected static $instance = null;
	protected $service;
	protected $shortcodes;
	protected $admin = null;
	protected $repository;
	protected $validator;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	public function init() {
		load_plugin_textdomain( 'igw_wp_open_zeit', false, dirname( plugin_basename( IGW_WP_OPEN_ZEIT_FILE ) ) . '/languages' );

		$this->repository = new IGW_Openzeit_Repository();
		$this->validator  = new IGW_Openzeit_Validator();
		$this->service    = new IGW_Openzeit_Service( $this->repository->get_data() );
		$this->shortcodes = new IGW_Openzeit_Shortcodes( $this->service );

		if ( is_admin() ) {
			$this->admin = new IGW_Openzeit_Admin( $this->repository, $this->validator );
			$this->admin->hooks();
		}

		$this->register_shortcodes();
	}

	public function enqueue_public_assets() {
		wp_enqueue_style( 'igw-openzeit-public', IGW_WP_OPEN_ZEIT_URL . 'public/assets/public.css', array(), IGW_WP_OPEN_ZEIT_VERSION );
	}

	protected function register_shortcodes() {
		add_shortcode( 'igw_wp_open_zeit_text', array( $this->shortcodes, 'render_text' ) );
		add_shortcode( 'open_zeit_text', array( $this->shortcodes, 'render_text' ) );
		add_shortcode( 'igw_wp_open_zeit_tage', array( $this->shortcodes, 'render_days' ) );
		add_shortcode( 'open_zeit_tage', array( $this->shortcodes, 'render_days' ) );
		add_shortcode( 'igw_wp_open_zeit_short', array( $this->shortcodes, 'render_short' ) );
		add_shortcode( 'open_zeit_short', array( $this->shortcodes, 'render_short' ) );
	}
}
