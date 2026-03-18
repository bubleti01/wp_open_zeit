<?php
/**
 * Data repository for opening times.
 *
 * @package IGW_Open_Zeit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IGW_Openzeit_Repository {

	/**
	 * Primary option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'igw_wp_open_zeit_data';

	/**
	 * Legacy option key from previous broken rewrite.
	 *
	 * @var string
	 */
	const LEGACY_OPTION_KEY = 'igw_wp_open_zeit_hours';

	/**
	 * Return normalized plugin data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_data() {
		$data = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $data ) ) {
			$data = $this->migrate_from_legacy_hours();
		}

		if ( ! is_array( $data ) ) {
			$data = $this->get_default_data();
		}

		if ( ! isset( $data['weekly'] ) || ! is_array( $data['weekly'] ) ) {
			$data['weekly'] = array();
		}

		for ( $day = 1; $day <= 7; $day++ ) {
			if ( ! isset( $data['weekly'][ $day ] ) || ! is_array( $data['weekly'][ $day ] ) ) {
				$data['weekly'][ $day ] = array(
					'closed'    => true,
					'intervals' => array(),
				);
			}
		}

		if ( ! isset( $data['holidays'] ) || ! is_array( $data['holidays'] ) ) {
			$data['holidays'] = array();
		}

		return $data;
	}

	/**
	 * Persist data.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return bool
	 */
	public function save_data( array $data ) {
		return (bool) update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Default data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_default_data() {
		$weekly = array();
		for ( $day = 1; $day <= 7; $day++ ) {
			$weekly[ $day ] = array(
				'closed'    => true,
				'intervals' => array(),
			);
		}

		return array(
			'weekly'   => $weekly,
			'holidays' => array(),
		);
	}

	/**
	 * Migrate legacy flat option to original structure.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function migrate_from_legacy_hours() {
		$legacy = get_option( self::LEGACY_OPTION_KEY, null );
		if ( ! is_array( $legacy ) ) {
			return null;
		}

		$data = $this->get_default_data();
		for ( $day = 1; $day <= 7; $day++ ) {
			$intervals  = isset( $legacy[ $day ] ) && is_array( $legacy[ $day ] ) ? $legacy[ $day ] : array();
			$normalized = array();
			foreach ( $intervals as $interval ) {
				if ( empty( $interval['start'] ) || empty( $interval['end'] ) ) {
					continue;
				}
				$normalized[] = array(
					'start' => (string) $interval['start'],
					'end'   => (string) $interval['end'],
				);
			}

			$data['weekly'][ $day ] = array(
				'closed'    => empty( $normalized ),
				'intervals' => $normalized,
			);
		}

		update_option( self::OPTION_KEY, $data );
		return $data;
	}
}
