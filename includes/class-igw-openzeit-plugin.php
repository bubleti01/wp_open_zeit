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
			add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		}

		$this->register_shortcodes();
	}

	public function enqueue_public_assets() {
		wp_enqueue_style( 'igw-openzeit-public', IGW_WP_OPEN_ZEIT_URL . 'public/assets/public.css', array(), IGW_WP_OPEN_ZEIT_VERSION );
	}

	public function register_dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'igw_openzeit_dashboard_widget',
			__( 'Öffnungszeiten (nächste 14 Tage)', 'igw_wp_open_zeit' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	public function render_dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			echo esc_html__( 'Keine Berechtigung.', 'igw_wp_open_zeit' );
			return;
		}

		$settings_url = admin_url( 'options-general.php?page=igw-wp-open-zeit' );
		echo '<p><a class="button button-secondary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Öffnungszeiten ändern', 'igw_wp_open_zeit' ) . '</a></p>';

		if ( ! $this->service || ! $this->service->has_configured_opening_times() ) {
			echo '<p>' . esc_html__( 'Keine Öffnungszeiten konfiguriert.', 'igw_wp_open_zeit' ) . '</p>';
			return;
		}

		$now   = current_datetime();
		$today = DateTimeImmutable::createFromInterface( $now )->setTime( 0, 0, 0 );
		$dates = array();
		for ( $i = 0; $i < 14; $i++ ) {
			$dates[] = $today->modify( '+' . $i . ' days' );
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Tag', 'igw_wp_open_zeit' ) . '</th><th>' . esc_html__( 'Zeiten', 'igw_wp_open_zeit' ) . '</th></tr></thead><tbody>';
		foreach ( $dates as $date ) {
			$row = $this->service->get_day_display_data( $date );
			echo '<tr>';
			echo '<td>' . esc_html( $row['label_day'] . ', ' . $row['label_date'] ) . '</td>';
			echo '<td>' . esc_html( $row['value'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
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
