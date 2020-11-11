<?php
/**
 * Class AbstractGateways
 *
 * @since 1.18.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Gateways;

use TIVWP_140\InterfaceHookable;

/**
 * Class AbstractGateways
 *
 * @package WOOMC\Integration\Gateways
 */
abstract class AbstractGateways implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	abstract public function setup_hooks();

	/**
	 * Return the currently selected currency as array.
	 *
	 * @return array
	 */
	public function active_currency_as_array() {
		return (array) get_woocommerce_currency();
	}

}
