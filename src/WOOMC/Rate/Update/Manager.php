<?php
/**
 * Rate update manager.
 *
 * @since 1.20.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate\Update;

use TIVWP_140\Logger\Message;
use WOOMC\Log;
use WOOMC\Rate\Updater;

/**
 * Class Rate\Update\Manager
 *
 * @package WOOMC\Rate\Update
 */
class Manager {

	/**
	 * The "need to update" flag.
	 *
	 * @var bool
	 */
	protected static $need_to_update = false;

	/**
	 * Is update needed?
	 *
	 * @return bool
	 */
	public static function isNeedToUpdate() {
		return self::$need_to_update;
	}

	/**
	 * Set update to "needed".
	 */
	public static function setNeedToUpdate() {
		self::$need_to_update = true;
	}

	/**
	 * Set update to "NOT needed".
	 */
	public static function unsetNeedToUpdate() {
		self::$need_to_update = false;
	}

	/**
	 * Run the rates update if needed.
	 *
	 * @throws \Exception Caught.
	 */
	public static function update() {
		if ( self::isNeedToUpdate() ) {
			Log::debug( new Message( 'Rates updating is required.' ) );

			$rate_updater = new Updater();
			$rate_updater->update();

			self::unsetNeedToUpdate();
		} else {
			Log::debug( new Message( 'Rates updating is NOT required.' ) );
		}
	}

	/**
	 * Force to run the rates update.
	 *
	 * @throws \Exception Caught.
	 */
	public static function forced_update() {
		self::setNeedToUpdate();
		self::update();
	}
}
