<?php
/**
 * Information about the currently active currency rates provider.
 *
 * @since 1.20.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Rate;

use WOOMC\DAO\Factory;
use WOOMC\Rate\Provider\FixedRates;

/**
 * Class Rates\CurrentProvider
 *
 * @package WOOMC\Rate
 */
class CurrentProvider {

	/**
	 * Returns true if the current provider is "FixedRates"
	 *
	 * @return bool
	 */
	public static function isFixedRates() {
		return ( FixedRates::id() === Factory::getDao()->getRatesProviderID() );
	}
}
