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

	/** @var IGW_Openzeit_Service */
	protected $service;

	public function __construct( IGW_Openzeit_Service $service ) {
		$this->service = $service;
	}

	public function render_text( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'open_text'   => __( 'Geöffnet', 'igw_wp_open_zeit' ),
				'closed_text' => __( 'Geschlossen', 'igw_wp_open_zeit' ),
				'datetime'    => '',
				'class'       => '',
			),
			$atts,
			'igw_wp_open_zeit_text'
		);

		$datetime = is_string( $atts['datetime'] ) && '' !== $atts['datetime'] ? $atts['datetime'] : null;
		$is_open  = $this->service->is_open_now( $datetime );
		$status   = $is_open ? 'igw-open' : 'igw-closed';
		$text     = $is_open ? $atts['open_text'] : $atts['closed_text'];
		$class    = 'igw-openzeit-text ' . $status . ( $atts['class'] ? ' ' . sanitize_html_class( $atts['class'] ) : '' );

		return sprintf(
			'<h2 class="%s"><span class="igw-openzeit-status %s">%s</span></h2>',
			esc_attr( trim( $class ) ),
			esc_attr( $status ),
			esc_html( $text )
		);
	}

	public function render_days() {
		$today      = $this->get_site_now()->setTime( 0, 0, 0 );
		$week_dates = $this->get_week_dates( $today );
		$day_names  = $this->day_names();
		$html       = '<table class="igw-openzeit-table"><tbody>';

		foreach ( $week_dates as $day ) {
			$weekday = (int) $day->format( 'N' );
			$display = $this->service->get_day_display( $day );
			$classes = array( 'igw-openzeit-row' );
			if ( $day->format( 'Y-m-d' ) === $today->format( 'Y-m-d' ) ) {
				$classes[] = 'igw-openzeit-today';
				if ( $this->service->is_open_now( $day->format( 'Y-m-d H:i:s' ) ) ) {
					$classes[] = 'igw-openzeit-today-open';
				}
			}

			$html .= sprintf(
				'<tr class="%s"><th class="igw-openzeit-day">%s</th><td class="igw-openzeit-value">%s</td></tr>',
				esc_attr( implode( ' ', $classes ) ),
				esc_html( $day_names[ $weekday ] ),
				esc_html( $display )
			);
		}

		$html .= '</tbody></table>';
		return $html;
	}

	public function render_short() {
		$today      = $this->get_site_now()->setTime( 0, 0, 0 );
		$week_dates = $this->get_week_dates( $today );
		$day_names  = $this->day_names();

		$rows = array();
		foreach ( $week_dates as $day ) {
			$effective = $this->service->get_effective_day_resolution( $day );
			if ( 'closed' === $effective['state'] ) {
				continue;
			}
			$rows[] = array('day'=>(int)$day->format('N'),'label'=>$effective['label']);
		}
		if ( empty( $rows ) ) {
			return '';
		}

		$groups = array();
		foreach ( $rows as $row ) {
			$i = count( $groups ) - 1;
			if ( $i >= 0 && $groups[ $i ]['label'] === $row['label'] && $groups[ $i ]['to'] + 1 === $row['day'] ) {
				$groups[ $i ]['to'] = $row['day'];
			} else {
				$groups[] = array( 'from' => $row['day'], 'to' => $row['day'], 'label' => $row['label'] );
			}
		}

		$out = '<div class="igw-openzeit-short">';
		foreach ( $groups as $g ) {
			$range = $day_names[ $g['from'] ] . ( $g['from'] !== $g['to'] ? ' - ' . $day_names[ $g['to'] ] : '' );
			$out  .= sprintf( '<div class="igw-openzeit-short-row"><span class="igw-openzeit-short-days">%s</span><span class="igw-openzeit-short-value">%s</span></div>', esc_html( $range ), esc_html( $g['label'] ) );
		}
		$out .= '</div>';

		return $out;
	}

	/** @return array<int,DateTimeImmutable> */
	protected function get_week_dates( DateTimeImmutable $now ) {
		$wp_start = (int) get_option( 'start_of_week', 1 ); // 0=Sunday.
		$start_n  = 0 === $wp_start ? 7 : $wp_start;
		$current_n = (int) $now->format( 'N' );
		$delta = $current_n - $start_n;
		if ( $delta < 0 ) {
			$delta += 7;
		}
		$start = $now->modify( '-' . $delta . ' days' );
		$dates = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$dates[] = $start->modify( '+' . $i . ' days' );
		}
		return $dates;
	}

	protected function day_names() {
		return array( 1 => __( 'Montag', 'igw_wp_open_zeit' ), 2 => __( 'Dienstag', 'igw_wp_open_zeit' ), 3 => __( 'Mittwoch', 'igw_wp_open_zeit' ), 4 => __( 'Donnerstag', 'igw_wp_open_zeit' ), 5 => __( 'Freitag', 'igw_wp_open_zeit' ), 6 => __( 'Samstag', 'igw_wp_open_zeit' ), 7 => __( 'Sonntag', 'igw_wp_open_zeit' ) );
	}

	protected function get_site_now() {
		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );
		return function_exists( 'current_datetime' ) ? DateTimeImmutable::createFromInterface( current_datetime() )->setTimezone( $tz ) : new DateTimeImmutable( 'now', $tz );
	}
}
