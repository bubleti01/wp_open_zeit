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

	/** @var IGW_Openzeit_Repository */
	protected $repository;

	/** @var IGW_Openzeit_Validator */
	protected $validator;

	public function __construct( IGW_Openzeit_Repository $repository, IGW_Openzeit_Validator $validator ) {
		$this->repository = $repository;
		$this->validator  = $validator;
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_options_page(
			__( 'IGW Öffnungszeiten', 'igw_wp_open_zeit' ),
			__( 'IGW Öffnungszeiten', 'igw_wp_open_zeit' ),
			'manage_options',
			'igw-wp-open-zeit',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'igw_wp_open_zeit',
			IGW_Openzeit_Repository::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_data' ),
				'default'           => $this->repository->get_default_data(),
			)
		);
	}

	/**
	 * @param mixed $value
	 * @return array<string,mixed>
	 */
	public function sanitize_data( $value ) {
		return $this->validator->validate_data( $value );
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_igw-wp-open-zeit' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'igw-openzeit-admin', IGW_WP_OPEN_ZEIT_URL . 'admin/assets/admin.css', array(), IGW_WP_OPEN_ZEIT_VERSION );
		wp_enqueue_script( 'igw-openzeit-admin', IGW_WP_OPEN_ZEIT_URL . 'admin/assets/admin.js', array(), IGW_WP_OPEN_ZEIT_VERSION, true );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$data      = $this->repository->get_data();
		$weekly    = isset( $data['weekly'] ) && is_array( $data['weekly'] ) ? $data['weekly'] : array();
		$holidays  = isset( $data['holidays'] ) && is_array( $data['holidays'] ) ? $data['holidays'] : array();
		$day_names = array( 1 => __( 'Montag', 'igw_wp_open_zeit' ), 2 => __( 'Dienstag', 'igw_wp_open_zeit' ), 3 => __( 'Mittwoch', 'igw_wp_open_zeit' ), 4 => __( 'Donnerstag', 'igw_wp_open_zeit' ), 5 => __( 'Freitag', 'igw_wp_open_zeit' ), 6 => __( 'Samstag', 'igw_wp_open_zeit' ), 7 => __( 'Sonntag', 'igw_wp_open_zeit' ) );

		if ( empty( $holidays ) ) {
			$holidays = array(
				array(
					'date' => '',
					'text' => '',
				),
			);
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'IGW WP Öffnungszeiten', 'igw_wp_open_zeit' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'igw_wp_open_zeit' ); ?>
				<table class="form-table igw-openzeit-admin-table" role="presentation">
					<tbody>
					<?php for ( $day = 1; $day <= 7; $day++ ) : ?>
						<?php
						$day_data  = isset( $weekly[ $day ] ) && is_array( $weekly[ $day ] ) ? $weekly[ $day ] : array();
						$closed    = ! empty( $day_data['closed'] );
						$intervals = isset( $day_data['intervals'] ) && is_array( $day_data['intervals'] ) ? $day_data['intervals'] : array();
						if ( empty( $intervals ) ) {
							$intervals = array( array( 'start' => '', 'end' => '' ) );
						}
						?>
						<tr class="igw-openzeit-day-row" data-day="<?php echo esc_attr( (string) $day ); ?>">
							<th scope="row"><?php echo esc_html( $day_names[ $day ] ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( IGW_Openzeit_Repository::OPTION_KEY ); ?>[weekly][<?php echo esc_attr( (string) $day ); ?>][closed]" value="1" <?php checked( $closed ); ?> />
									<?php echo esc_html__( 'Geschlossen', 'igw_wp_open_zeit' ); ?>
								</label>
								<div class="igw-openzeit-intervals" <?php echo $closed ? 'style="display:none"' : ''; ?>>
									<?php foreach ( $intervals as $index => $interval ) : ?>
										<div class="igw-openzeit-interval-row">
											<input type="time" name="<?php echo esc_attr( IGW_Openzeit_Repository::OPTION_KEY ); ?>[weekly][<?php echo esc_attr( (string) $day ); ?>][intervals][<?php echo esc_attr( (string) $index ); ?>][start]" value="<?php echo esc_attr( isset( $interval['start'] ) ? (string) $interval['start'] : '' ); ?>" />
											<span>–</span>
											<input type="time" name="<?php echo esc_attr( IGW_Openzeit_Repository::OPTION_KEY ); ?>[weekly][<?php echo esc_attr( (string) $day ); ?>][intervals][<?php echo esc_attr( (string) $index ); ?>][end]" value="<?php echo esc_attr( isset( $interval['end'] ) ? (string) $interval['end'] : '' ); ?>" />
											<button type="button" class="button-link-delete igw-remove-interval">×</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="button igw-add-interval"><?php echo esc_html__( 'Intervall hinzufügen', 'igw_wp_open_zeit' ); ?></button>
							</td>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>

				<h2><?php echo esc_html__( 'Feiertage', 'igw_wp_open_zeit' ); ?></h2>
				<p><?php echo esc_html__( 'Nutze folgende Eingabe nur für Feiertage. Gebe hierzu das Datum und einen Text wie z.B. Karfreitag, Ostermontag, Neujahr oder Geschlossen, ein. Nicht für Urlaubszeiten. (für Urlaub nutze das Plugin "IGW WP Urlaub Post", dessen Urlaube in den Öffnungszeiten berücksichtigt werden).', 'igw_wp_open_zeit' ); ?></p>
				<table class="widefat striped igw-openzeit-holidays-table" role="presentation">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Datum', 'igw_wp_open_zeit' ); ?></th>
							<th><?php echo esc_html__( 'Text', 'igw_wp_open_zeit' ); ?></th>
							<th><?php echo esc_html__( 'Aktion', 'igw_wp_open_zeit' ); ?></th>
						</tr>
					</thead>
					<tbody class="igw-openzeit-holidays-body">
						<?php foreach ( $holidays as $index => $holiday ) : ?>
							<tr class="igw-openzeit-holiday-row">
								<td>
									<input type="text" class="regular-text" placeholder="03.10.2026" name="<?php echo esc_attr( IGW_Openzeit_Repository::OPTION_KEY ); ?>[holidays][<?php echo esc_attr( (string) $index ); ?>][date]" value="<?php echo esc_attr( isset( $holiday['date'] ) ? (string) $holiday['date'] : '' ); ?>" />
								</td>
								<td>
									<input type="text" class="regular-text" name="<?php echo esc_attr( IGW_Openzeit_Repository::OPTION_KEY ); ?>[holidays][<?php echo esc_attr( (string) $index ); ?>][text]" value="<?php echo esc_attr( isset( $holiday['text'] ) ? (string) $holiday['text'] : '' ); ?>" />
								</td>
								<td>
									<button type="button" class="button-link-delete igw-remove-holiday"><?php echo esc_html__( 'Entfernen', 'igw_wp_open_zeit' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<button type="button" class="button igw-add-holiday"><?php echo esc_html__( 'Feiertag hinzufügen', 'igw_wp_open_zeit' ); ?></button>
				</p>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
