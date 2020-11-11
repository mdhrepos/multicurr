<?php
/**
 * Plugin Name: WooCommerce Multi-currency
 * Plugin URI: https://woocommerce.com/products/multi-currency/
 * Description: Multi-currency support for WooCommerce
 * Version: 2.7.2
 * Author: TIV.NET INC
 * Author URI: https://profiles.wordpress.org/tivnetinc/
 * Developer: Gregory K.
 * Developer URI: https://profiles.wordpress.org/tivnet/
 * Text Domain: woocommerce-multicurrency
 * Domain Path: /languages/
 *
 * WC requires at least: 3.9.0
 * WC tested up to: 4.6.1
 *
 * Woo: 3202901:9b5d903ce4283ced8ede8522c606324b
 *
 * Copyright: © 2020 TIV.NET INC.
 * License: GPL-3.0-or-later
 * License URI: https://spdx.org/licenses/GPL-3.0-or-later.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Silently refuse to work below PHP 5.3.
if ( ! defined( 'PHP_VERSION_ID' ) || PHP_VERSION_ID < 50300 ) {
	return;
}

define( 'WOOCOMMERCE_MULTICURRENCY_VERSION', '2.7.2' );

// Continue with the 53+ loader.
/* @noinspection dirnameCallOnFileConstantInspection */
require_once dirname( __FILE__ ) . '/src/loader.php';
