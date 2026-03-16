<?php
/**
 * Admin UI for opening hours.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Admin {

	/**
	 * Option name for weekly hours.
	 *
	 * @var string
	 */
	const OPTION_HOURS = 'igw_wp_open_zeit_hours';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_options_page(
			__( 'IGW Öffnungszeiten', 'igw_wp_open_zeit' ),
			__( 'IGW Öffnungszeiten', 'igw_wp_open_zeit' ),
			'manage_options',
			'igw-wp-open-zeit',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'igw_wp_open_zeit',
			self::OPTION_HOURS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_hours_option' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize opening hours textarea format.
	 *
	 * Input per day: 09:00-13:00,15:00-18:00
	 *
	 * @param mixed $value Raw value.
	 * @return array<int,array<int,array{start:string,end:string}>>
	 */
	public function sanitize_hours_option( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		for ( $day = 1; $day <= 7; $day++ ) {
			$raw = isset( $value[ $day ] ) ? (string) $value[ $day ] : '';
			$raw = trim( $raw );
			if ( '' === $raw ) {
				continue;
			}

			$intervals = array();
			$parts     = array_map( 'trim', explode( ',', $raw ) );
			foreach ( $parts as $part ) {
				if ( ! preg_match( '/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $part, $matches ) ) {
					continue;
				}

				$start = $matches[1];
				$end   = $matches[2];
				if ( $start > $end ) {
					continue;
				}

				$intervals[] = array(
					'start' => $start,
					'end'   => $end,
				);
			}

			if ( ! empty( $intervals ) ) {
				$sanitized[ $day ] = $intervals;
			}
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$hours     = get_option( self::OPTION_HOURS, array() );
		$day_names = array(
			1 => __( 'Montag', 'igw_wp_open_zeit' ),
			2 => __( 'Dienstag', 'igw_wp_open_zeit' ),
			3 => __( 'Mittwoch', 'igw_wp_open_zeit' ),
			4 => __( 'Donnerstag', 'igw_wp_open_zeit' ),
			5 => __( 'Freitag', 'igw_wp_open_zeit' ),
			6 => __( 'Samstag', 'igw_wp_open_zeit' ),
			7 => __( 'Sonntag', 'igw_wp_open_zeit' ),
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'IGW WP Öffnungszeiten', 'igw_wp_open_zeit' ); ?></h1>
			<p><?php echo esc_html__( 'Tragen Sie pro Tag Zeitintervalle im Format 09:00-13:00,15:00-18:00 ein. Leer = geschlossen.', 'igw_wp_open_zeit' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'igw_wp_open_zeit' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
					<?php for ( $day = 1; $day <= 7; $day++ ) : ?>
						<?php
						$value = '';
						if ( isset( $hours[ $day ] ) && is_array( $hours[ $day ] ) ) {
							$pieces = array();
							foreach ( $hours[ $day ] as $interval ) {
								if ( empty( $interval['start'] ) || empty( $interval['end'] ) ) {
									continue;
								}
								$pieces[] = $interval['start'] . '-' . $interval['end'];
							}
							$value = implode( ',', $pieces );
						}
						?>
						<tr>
							<th scope="row"><label for="igw-openzeit-day-<?php echo esc_attr( (string) $day ); ?>"><?php echo esc_html( $day_names[ $day ] ); ?></label></th>
							<td>
								<input
									type="text"
									id="igw-openzeit-day-<?php echo esc_attr( (string) $day ); ?>"
									name="<?php echo esc_attr( self::OPTION_HOURS ); ?>[<?php echo esc_attr( (string) $day ); ?>]"
									value="<?php echo esc_attr( $value ); ?>"
									class="regular-text"
									placeholder="09:00-13:00,15:00-18:00"
								/>
							</td>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr />
			<h2><?php echo esc_html__( 'Verfügbare Shortcodes', 'igw_wp_open_zeit' ); ?></h2>
			<ul>
				<li><code>[igw_wp_open_zeit_text]</code>, <code>[open_zeit_text]</code></li>
				<li><code>[igw_wp_open_zeit_tage]</code>, <code>[open_zeit_tage]</code></li>
				<li><code>[igw_wp_open_zeit_short]</code>, <code>[open_zeit_short]</code></li>
			</ul>
		</div>
		<?php
	}
}
