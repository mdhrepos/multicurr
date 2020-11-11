<?php
/**
 * WooCommerce Tools abstract class.
 *
 * @since 1.2.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\WC;

use TIVWP_140\InterfaceHookable;

/**
 * Class AbstractTools
 *
 * @package TIVWP_140\WC
 */
abstract class AbstractTools implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		\add_filter( 'woocommerce_debug_tools', array( $this, 'tools' ) );
	}

	/**
	 * Button(s) on the WooCommerce > Status > Tools page.
	 *
	 * @param array $tools All tools array.
	 *
	 * @return array
	 */
	abstract public function tools( $tools );
}
