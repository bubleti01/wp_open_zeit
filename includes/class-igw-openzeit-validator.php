<?php
/**
 * Validation for opening times payload.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Validator {

	/**
	 * Validate and normalize full data structure.
	 *
	 * @param mixed $value Raw input.
	 * @return array<string,mixed>
	 */
	public function validate_data( $value ) {
		$weekly = array();
		$input  = is_array( $value ) && isset( $value['weekly'] ) && is_array( $value['weekly'] ) ? $value['weekly'] : array();

		for ( $day = 1; $day <= 7; $day++ ) {
			$raw_day = isset( $input[ $day ] ) && is_array( $input[ $day ] ) ? $input[ $day ] : array();
			$closed  = ! empty( $raw_day['closed'] );
			$intervals = $this->validate_intervals( isset( $raw_day['intervals'] ) ? $raw_day['intervals'] : array() );

			if ( $closed ) {
				$intervals = array();
			}

			$weekly[ $day ] = array(
				'closed'    => $closed || empty( $intervals ),
				'intervals' => $closed ? array() : $intervals,
			);
		}

		return array( 'weekly' => $weekly );
	}

	/**
	 * Validate intervals list.
	 *
	 * @param mixed $intervals Raw intervals.
	 * @return array<int,array{start:string,end:string}>
	 */
	public function validate_intervals( $intervals ) {
		if ( ! is_array( $intervals ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $intervals as $interval ) {
			if ( ! is_array( $interval ) ) {
				continue;
			}

			$start = $this->normalize_time( isset( $interval['start'] ) ? $interval['start'] : '' );
			$end   = $this->normalize_time( isset( $interval['end'] ) ? $interval['end'] : '' );
			if ( '' === $start || '' === $end || $start >= $end ) {
				continue;
			}

			$normalized[] = array(
				'start' => $start,
				'end'   => $end,
			);
		}

		usort(
			$normalized,
			static function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);

		return $normalized;
	}

	/**
	 * Normalize time value to HH:MM.
	 *
	 * @param mixed $value Time value.
	 * @return string
	 */
	protected function normalize_time( $value ) {
		$raw = trim( (string) $value );
		if ( '' === $raw ) {
			return '';
		}

		$raw = str_replace( '.', ':', $raw );
		if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $raw, $m ) ) {
			$h = (int) $m[1];
			$i = (int) $m[2];
			if ( $h >= 0 && $h <= 23 && $i >= 0 && $i <= 59 ) {
				return sprintf( '%02d:%02d', $h, $i );
			}
		}

		return '';
	}
}
