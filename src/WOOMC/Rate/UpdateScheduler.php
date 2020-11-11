<?php
/**
 * Rates update scheduler.
 *
 * @since 1.20.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use TIVWP_140\Logger\Message;
use WOOMC\DAO\Factory;
use WOOMC\Log;

/**
 * Class UpdateScheduler
 *
 * @package WOOMC\Rate
 */
class UpdateScheduler implements InterfaceHookable {

	/**
	 * Default cron job schedule.
	 *
	 * @var string
	 */
	const DEFAULT_SCHEDULE = 'twicedaily';

	/**
	 * Custom cron job schedule.
	 *
	 * @var string
	 */
	const CUSTOM_SCHEDULE = 'woomc_rates';

	/**
	 * Action to update rates.
	 *
	 * @var string
	 */
	const ACTION_UPDATE_RATES = 'woocommerce_multicurrency_update_rates';

	/**
	 * The cron interval in seconds.
	 * To activate, define WOOMC_RATE_UPDATES_INTERVAL.
	 *
	 * @var int
	 */
	protected $interval = 0;

	/**
	 * UpdateScheduler constructor.
	 */
	public function __construct() {

		if ( defined( 'WOOMC_RATE_UPDATES_INTERVAL' ) ) {
			$this->interval = (int) WOOMC_RATE_UPDATES_INTERVAL;
		}

	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( Env::is_doing_ajax() ) {
			// Not wasting time on AJAX calls.
			return;
		}

		if ( $this->interval ) {
			// phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
			\add_filter( 'cron_schedules', array( $this, 'add_wp_cron_schedule' ) );
		}

		// If schedule setting changed, remove the schedule.
		$option = Factory::getDao()->key_rates_update_schedule();
		\add_action( "update_option_{$option}", array( $this, 'reschedule_cron_job' ) );

		$this->toggle_cron_job();

	}

	/**
	 * Decide whether to add or remove the cron job.
	 *
	 * @return void
	 */
	protected function toggle_cron_job() {
		if ( CurrentProvider::isFixedRates() ) {
			$this->remove_cron_job();
		} else {
			$this->add_cron_job();
		}
	}

	/**
	 * Remove the schedule and set it again - when selected a different one in the settings panel.
	 *
	 * @return void
	 */
	public function reschedule_cron_job() {
		Log::debug( new Message( 'Rescheduling rates update' ) );
		$this->remove_cron_job();
		$this->toggle_cron_job();
	}

	/**
	 * Setup cron schedule.
	 *
	 * @return void
	 */
	protected function add_cron_job() {

		\add_action( self::ACTION_UPDATE_RATES, array( $this, 'update_from_cron' ) );

		if ( ! $this->is_scheduled() ) {
			$schedule       = Factory::getDao()->getRatesUpdateSchedule();
			$cron_schedules = \wp_get_schedules();

			if ( empty( $cron_schedules[ $schedule ]['interval'] ) ) {
				// This should only happen if WOOMC_RATE_UPDATES_INTERVAL was set and then removed.
				Log::error( new \Exception( 'Invalid rates update schedule. Setting to `' . self::DEFAULT_SCHEDULE . '`.' ) );
				Factory::getDao()->setRatesUpdateSchedule( self::DEFAULT_SCHEDULE );

				return;
			}

			$interval = $cron_schedules[ $schedule ]['interval'];

			// Schedule the next start. Rounding is just for beauty :).
			$timestamp = time();
			\wp_schedule_event( $timestamp - ( $timestamp % 3600 ) + $interval, $schedule, self::ACTION_UPDATE_RATES );
			Log::debug( new Message( 'Rates update scheduled. Interval = ' . $interval ) );
		}
	}

	/**
	 * Shortcut to forced update.
	 *
	 * @return void
	 * @throws \Exception Caught.
	 */
	public function update_from_cron() {
		Log::debug( new Message( 'Starting scheduled rates update.' ) );
		Update\Manager::forced_update();
	}

	/**
	 * Is cron scheduled?
	 *
	 * @return bool
	 */
	protected function is_scheduled() {
		return (bool) \wp_next_scheduled( self::ACTION_UPDATE_RATES );
	}

	/**
	 * Add a custom cron schedule.
	 *
	 * @param array $schedules The schedules.
	 *
	 * @return array
	 */
	public function add_wp_cron_schedule( $schedules ) {

		$schedules[ self::CUSTOM_SCHEDULE ] = array(
			'interval' => $this->interval,
			/* translators: %s: Time duration in second or seconds. */
			'display'  => sprintf( _n( '%s second', '%s seconds', $this->interval ), $this->interval ),
		);

		return $schedules;
	}

	/**
	 * Remove the scheduled job.
	 *
	 * @return void
	 */
	public function remove_cron_job() {
		if ( $this->is_scheduled() ) {
			\wp_clear_scheduled_hook( self::ACTION_UPDATE_RATES );
			Log::debug( new Message( 'Rates update schedule removed.' ) );
		}
	}

}
