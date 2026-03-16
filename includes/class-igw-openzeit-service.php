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

	/**
	 * Weekly opening hours.
	 *
	 * @var array<string,array<int,array{start:string,end:string}>>
	 */
	protected $weekly_hours = array();

	/**
	 * Request cache for vacation intervals.
	 *
	 * @var array<int,array{start:DateTimeImmutable,end:DateTimeImmutable}>|null
	 */
	protected $vacation_intervals = null;

	/**
	 * Constructor.
	 *
	 * @param array $weekly_hours Weekly opening hours keyed by weekday number (1-7).
	 */
	public function __construct( array $weekly_hours = array() ) {
		$this->weekly_hours = $weekly_hours;
	}

	/**
	 * Checks if the business is currently open.
	 *
	 * Vacation has highest priority and always returns false.
	 *
	 * @return bool
	 */
	public function is_open_now() {
		$now = $this->normalize_datetime( null );

		$effective = $this->get_effective_day_resolution( $now );
		if ( 'hours' !== $effective['state'] ) {
			return false;
		}

		$current_time = $now->format( 'H:i' );
		foreach ( $effective['intervals'] as $interval ) {
			if ( $current_time >= $interval['start'] && $current_time <= $interval['end'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns effective day resolution.
	 *
	 * @param DateTimeInterface|string|null $date Date to evaluate.
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

		$weekday   = (int) $datetime->format( 'N' );
		$intervals = isset( $this->weekly_hours[ $weekday ] ) && is_array( $this->weekly_hours[ $weekday ] )
			? $this->weekly_hours[ $weekday ]
			: array();

		if ( empty( $intervals ) ) {
			return array(
				'state'     => 'closed',
				'label'     => __( 'Geschlossen', 'igw_wp_open_zeit' ),
				'intervals' => array(),
			);
		}

		return array(
			'state'     => 'hours',
			'label'     => $this->format_intervals( $intervals ),
			'intervals' => $intervals,
		);
	}

	/**
	 * Compatibility helper for day output.
	 *
	 * @param DateTimeInterface|string|null $date Date to evaluate.
	 * @return string
	 */
	public function get_day_display( $date = null ) {
		$effective = $this->get_effective_day_resolution( $date );
		return $effective['label'];
	}

	/**
	 * Checks whether date is inside any active vacation interval.
	 *
	 * @param DateTimeInterface $date Date to evaluate.
	 * @return bool
	 */
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
	 * Returns active vacation intervals from urlaub_post.
	 *
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

		if ( empty( $posts ) ) {
			return $this->vacation_intervals;
		}

		foreach ( $posts as $post_id ) {
			$from_raw = get_post_meta( $post_id, 'von_datum', true );
			$to_raw   = get_post_meta( $post_id, 'bis_datum', true );

			$start = $this->parse_site_date( $from_raw );
			$end   = $this->parse_site_date( $to_raw );

			if ( ! $start || ! $end ) {
				continue;
			}

			if ( $end < $start ) {
				continue;
			}

			$this->vacation_intervals[] = array(
				'start' => $start,
				'end'   => $end,
			);
		}

		return $this->vacation_intervals;
	}

	/**
	 * Normalizes input into site timezone DateTimeImmutable.
	 *
	 * @param DateTimeInterface|string|null $date Date value.
	 * @return DateTimeImmutable
	 */
	protected function normalize_datetime( $date ) {
		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );

		if ( $date instanceof DateTimeInterface ) {
			return DateTimeImmutable::createFromInterface( $date )->setTimezone( $tz );
		}

		if ( is_string( $date ) && '' !== $date ) {
			try {
				return new DateTimeImmutable( $date, $tz );
			} catch ( Exception $e ) {
				return function_exists( 'current_datetime' ) ? DateTimeImmutable::createFromInterface( current_datetime() ) : new DateTimeImmutable( 'now', $tz );
			}
		}

		if ( function_exists( 'current_datetime' ) ) {
			return DateTimeImmutable::createFromInterface( current_datetime() );
		}

		return new DateTimeImmutable( 'now', $tz );
	}

	/**
	 * Parses date value from vacation post meta.
	 *
	 * @param mixed $raw Raw date value.
	 * @return DateTimeImmutable|null
	 */
	protected function parse_site_date( $raw ) {
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return null;
		}

		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( date_default_timezone_get() );
		$raw = trim( $raw );

		$formats = array( 'Y-m-d', 'd.m.Y', 'Ymd' );
		foreach ( $formats as $format ) {
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

	/**
	 * Formats intervals for display.
	 *
	 * @param array<int,array{start:string,end:string}> $intervals Intervals.
	 * @return string
	 */
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
