<?php
/**
 * Openzeit shortcodes.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Shortcodes {

	/**
	 * @var IGW_Openzeit_Service
	 */
	protected $service;

	/**
	 * @param IGW_Openzeit_Service $service Service instance.
	 */
	public function __construct( IGW_Openzeit_Service $service ) {
		$this->service = $service;
	}

	/**
	 * Status shortcode output.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_text( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'open_text'   => __( 'Geöffnet', 'igw_wp_open_zeit' ),
				'closed_text' => __( 'Geschlossen', 'igw_wp_open_zeit' ),
			),
			$atts
		);

		$is_open = $this->service->is_open_now();
		$text    = $is_open ? $atts['open_text'] : $atts['closed_text'];
		$class   = $is_open ? 'igw-openzeit--open' : 'igw-openzeit--closed';

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $text ) );
	}

	/**
	 * Week table output.
	 *
	 * @return string
	 */
	public function render_days() {
		$today      = $this->get_site_now();
		$week_start = $today->modify( 'monday this week' )->setTime( 0, 0, 0 );
		$day_names  = array( 1 => __( 'Montag', 'igw_wp_open_zeit' ), 2 => __( 'Dienstag', 'igw_wp_open_zeit' ), 3 => __( 'Mittwoch', 'igw_wp_open_zeit' ), 4 => __( 'Donnerstag', 'igw_wp_open_zeit' ), 5 => __( 'Freitag', 'igw_wp_open_zeit' ), 6 => __( 'Samstag', 'igw_wp_open_zeit' ), 7 => __( 'Sonntag', 'igw_wp_open_zeit' ) );
		$html       = '<table class="igw-openzeit-days"><tbody>';

		for ( $i = 0; $i < 7; $i++ ) {
			$day     = $week_start->modify( '+' . $i . ' days' );
			$weekday = (int) $day->format( 'N' );
			$display = $this->service->get_day_display( $day );
			$html   .= sprintf(
				'<tr><th>%s</th><td>%s</td></tr>',
				esc_html( $day_names[ $weekday ] ),
				esc_html( $display )
			);
		}

		$html .= '</tbody></table>';
		return $html;
	}

	/**
	 * Compact grouped output.
	 *
	 * @return string
	 */
	public function render_short() {
		$today      = $this->get_site_now();
		$week_start = $today->modify( 'monday this week' )->setTime( 0, 0, 0 );
		$day_names  = array( 1 => __( 'Montag', 'igw_wp_open_zeit' ), 2 => __( 'Dienstag', 'igw_wp_open_zeit' ), 3 => __( 'Mittwoch', 'igw_wp_open_zeit' ), 4 => __( 'Donnerstag', 'igw_wp_open_zeit' ), 5 => __( 'Freitag', 'igw_wp_open_zeit' ), 6 => __( 'Samstag', 'igw_wp_open_zeit' ), 7 => __( 'Sonntag', 'igw_wp_open_zeit' ) );

		$effective_rows = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$day       = $week_start->modify( '+' . $i . ' days' );
			$effective = $this->service->get_effective_day_resolution( $day );

			if ( 'closed' === $effective['state'] ) {
				continue;
			}

			$effective_rows[] = array(
				'day'   => (int) $day->format( 'N' ),
				'label' => $effective['label'],
			);
		}

		if ( empty( $effective_rows ) ) {
			return '';
		}

		$groups = array();
		foreach ( $effective_rows as $row ) {
			$last_index = count( $groups ) - 1;
			if ( $last_index >= 0 && $groups[ $last_index ]['label'] === $row['label'] && $groups[ $last_index ]['to'] + 1 === $row['day'] ) {
				$groups[ $last_index ]['to'] = $row['day'];
				continue;
			}

			$groups[] = array(
				'from'  => $row['day'],
				'to'    => $row['day'],
				'label' => $row['label'],
			);
		}

		$lines = array();
		foreach ( $groups as $group ) {
			$days = $day_names[ $group['from'] ];
			if ( $group['from'] !== $group['to'] ) {
				$days .= ' – ' . $day_names[ $group['to'] ];
			}
			$lines[] = sprintf( '<div class="igw-openzeit-short-row"><span>%s</span><span>%s</span></div>', esc_html( $days ), esc_html( $group['label'] ) );
		}

		return '<div class="igw-openzeit-short">' . implode( '', $lines ) . '</div>';
	}

	/**
	 * Returns current datetime in site timezone.
	 *
	 * @return DateTimeImmutable
	 */
	protected function get_site_now() {
		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );
		if ( function_exists( 'current_datetime' ) ) {
			return DateTimeImmutable::createFromInterface( current_datetime() )->setTimezone( $tz );
		}

		return new DateTimeImmutable( 'now', $tz );
	}
}
