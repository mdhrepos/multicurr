<?php

namespace WOOMC;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Short-circuit special WP calls.
 *
 * @since 1.18.3
 */
if (
	// xmlrpc.php:13
	defined( 'XMLRPC_REQUEST' )
	|| (
		! empty( $_SERVER['REQUEST_URI'] )
		&& preg_match( '/\/wp-(login|signup|trackback)\.php/i', \esc_url_raw( \wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) )
) {
	return;
}

// The autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Initialize the Application.
 *
 * @note We need to pass the full path of the plugin's main file.
 *       To avoid having yet another global variable or constant,
 *       We build it here, assuming that it's one folder up and the name is known.
 */
App::instance()
   ->configure( dirname( __DIR__ ) . '/woocommerce-multicurrency.php' )
   ->setup_hooks();

// Set the Options Panel to read-only mode. Useful for admin demonstrations.
App::instance()->setReadOnlySettings( defined( 'WOOMC_READ_ONLY_SETTINGS' ) && WOOMC_READ_ONLY_SETTINGS );
