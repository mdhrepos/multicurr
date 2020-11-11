<?php
/**
 * Abstract Integration Class.
 *
 * @since   1.18.0
 * @package WOOMC\Integration
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

use TIVWP_140\InterfaceHookable;
use WOOMC\Price;

/**
 * Class AbstractIntegration
 *
 * @package WOOMC\Integration
 */
abstract class AbstractIntegration implements InterfaceHookable {

	/**
	 * DI: Price Controller.
	 *
	 * @var Price\Controller
	 */
	protected $price_controller;

	/**
	 * Constructor.
	 *
	 * @param Price\Controller $price_controller The price controller.
	 */
	public function __construct( Price\Controller $price_controller ) {
		$this->price_controller = $price_controller;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	abstract public function setup_hooks();
}
