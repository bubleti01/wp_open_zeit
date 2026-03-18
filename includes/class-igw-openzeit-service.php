<?php
/**
 * Openzeit service.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Service {

	/** @var array<string,mixed> */
	protected $data = array();

	/** @var array<string,string> */
	protected $holiday_map = array();

	/** @var array<int,array{start:DateTimeImmutable,end:DateTimeImmutable}>|null */
	protected $vacation_intervals = null;

	/**
	 * @param array<string,mixed> $data plugin data.
	 */
	public function __construct( array $data ) {
		$this->data        = $data;
		$this->holiday_map = $this->build_holiday_map();
	}

	/**
	 * @param DateTimeInterface|string|null $date
	 * @return bool
	 */
	public function is_open_now( $date = null ) {
		$datetime  = $this->normalize_datetime( $date );
		$effective = $this->get_effective_day_resolution( $datetime );
		if ( 'hours' !== $effective['state'] ) {
			return false;
		}

		$time = $datetime->format( 'H:i' );
		foreach ( $effective['intervals'] as $interval ) {
			if ( $time >= $interval['start'] && $time <= $interval['end'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param DateTimeInterface|string|null $date
	 * @return array{state:string,label:string,intervals:array<int,array{start:string,end:string}>}
	 */
	public function get_effective_day_resolution( $date = null ) {
		$datetime = $this->normalize_datetime( $date );
		if ( $this->is_vacation_day( $datetime ) ) {
			return array(
				'state'     => 'vacation',
				'label'     => __( 'Betriebsurlaub', 'igw_wp_open_zeit' ),
				'intervals' => array(),
			);
		}

		$holiday_label = $this->get_holiday_label( $datetime );
		if ( null !== $holiday_label ) {
			return array(
				'state'     => 'holiday',
				'label'     => $holiday_label,
				'intervals' => array(),
			);
		}

		$day_data = $this->get_day_data( (int) $datetime->format( 'N' ) );
		if ( ! empty( $day_data['closed'] ) || empty( $day_data['intervals'] ) ) {
			return array(
				'state'     => 'closed',
				'label'     => __( 'Geschlossen', 'igw_wp_open_zeit' ),
				'intervals' => array(),
			);
		}

		return array(
			'state'     => 'hours',
			'label'     => $this->format_intervals( $day_data['intervals'] ),
			'intervals' => $day_data['intervals'],
		);
	}

	/** @return array{closed:bool,intervals:array<int,array{start:string,end:string}>} */
	public function get_day_data( $weekday ) {
		$weekly    = isset( $this->data['weekly'] ) && is_array( $this->data['weekly'] ) ? $this->data['weekly'] : array();
		$day_data  = isset( $weekly[ $weekday ] ) && is_array( $weekly[ $weekday ] ) ? $weekly[ $weekday ] : array();
		$intervals = isset( $day_data['intervals'] ) && is_array( $day_data['intervals'] ) ? $day_data['intervals'] : array();
		return array(
			'closed'    => ! empty( $day_data['closed'] ) || empty( $intervals ),
			'intervals' => $intervals,
		);
	}

	/**
	 * @param DateTimeInterface|string|null $date Date.
	 * @return string
	 */
	public function get_day_display( $date = null ) {
		$effective = $this->get_effective_day_resolution( $date );
		return $effective['label'];
	}

	/**
	 * @param DateTimeInterface|string|null $date Date.
	 * @return array{label_day:string,label_date:string,value:string}
	 */
	public function get_day_display_data( $date ) {
		$datetime = $this->normalize_datetime( $date );
		return array(
			'label_day'  => wp_date( 'l', $datetime->getTimestamp(), $datetime->getTimezone() ),
			'label_date' => wp_date( 'd.m.Y', $datetime->getTimestamp(), $datetime->getTimezone() ),
			'value'      => $this->get_day_display( $datetime ),
		);
	}

	/**
	 * @param DateTimeInterface $date Date.
	 * @return string|null
	 */
	public function get_holiday_label( DateTimeInterface $date ) {
		$key = DateTimeImmutable::createFromInterface( $date )->setTime( 0, 0, 0 )->format( 'Y-m-d' );
		return isset( $this->holiday_map[ $key ] ) ? $this->holiday_map[ $key ] : null;
	}

	public function is_vacation_day( DateTimeInterface $date ) {
		$day = DateTimeImmutable::createFromInterface( $date )->setTime( 0, 0, 0 );
		foreach ( $this->get_active_vacation_intervals() as $interval ) {
			if ( $day >= $interval['start'] && $day <= $interval['end'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function has_configured_opening_times() {
		$weekly = isset( $this->data['weekly'] ) && is_array( $this->data['weekly'] ) ? $this->data['weekly'] : array();
		foreach ( $weekly as $day_data ) {
			if ( is_array( $day_data ) && ! empty( $day_data['intervals'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array<int,array{start:DateTimeImmutable,end:DateTimeImmutable}>
	 */
	protected function get_active_vacation_intervals() {
		if ( null !== $this->vacation_intervals ) {
			return $this->vacation_intervals;
		}
		$this->vacation_intervals = array();
		if ( ! post_type_exists( 'urlaub_post' ) ) {
			return $this->vacation_intervals;
		}
		$posts = get_posts(
			array(
				'post_type'      => 'urlaub_post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'active',
						'value' => '1',
					),
				),
			)
		);
		foreach ( (array) $posts as $post_id ) {
			$start = $this->parse_site_date( get_post_meta( $post_id, 'von_datum', true ) );
			$end   = $this->parse_site_date( get_post_meta( $post_id, 'bis_datum', true ) );
			if ( $start && $end && $end >= $start ) {
				$this->vacation_intervals[] = array(
					'start' => $start,
					'end'   => $end,
				);
			}
		}
		return $this->vacation_intervals;
	}

	/**
	 * @return array<string,string>
	 */
	protected function build_holiday_map() {
		$map      = array();
		$holidays = isset( $this->data['holidays'] ) && is_array( $this->data['holidays'] ) ? $this->data['holidays'] : array();
		foreach ( $holidays as $holiday ) {
			if ( ! is_array( $holiday ) || empty( $holiday['date'] ) || empty( $holiday['text'] ) ) {
				continue;
			}
			$date = DateTimeImmutable::createFromFormat( 'd.m.Y', (string) $holiday['date'] );
			if ( false === $date ) {
				continue;
			}
			$map[ $date->format( 'Y-m-d' ) ] = (string) $holiday['text'];
		}
		return $map;
	}

	protected function normalize_datetime( $date ) {
		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );
		if ( $date instanceof DateTimeInterface ) {
			return DateTimeImmutable::createFromInterface( $date )->setTimezone( $tz );
		}
		if ( is_string( $date ) && '' !== $date ) {
			return new DateTimeImmutable( $date, $tz );
		}
		if ( function_exists( 'current_datetime' ) ) {
			return DateTimeImmutable::createFromInterface( current_datetime() )->setTimezone( $tz );
		}
		return new DateTimeImmutable( 'now', $tz );
	}

	protected function parse_site_date( $raw ) {
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return null;
		}
		$tz  = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );
		$raw = trim( $raw );
		foreach ( array( 'Y-m-d', 'd.m.Y', 'Ymd' ) as $format ) {
			$date = DateTimeImmutable::createFromFormat( $format, $raw, $tz );
			if ( false !== $date ) {
				return $date->setTime( 0, 0, 0 );
			}
		}
		try {
			return ( new DateTimeImmutable( $raw, $tz ) )->setTime( 0, 0, 0 );
		} catch ( Exception $e ) {
			return null;
		}
	}

	protected function format_intervals( array $intervals ) {
		$parts = array();
		foreach ( $intervals as $interval ) {
			if ( empty( $interval['start'] ) || empty( $interval['end'] ) ) {
				continue;
			}
			$parts[] = $interval['start'] . ' - ' . $interval['end'];
		}
		return implode( ', ', $parts );
	}
}
