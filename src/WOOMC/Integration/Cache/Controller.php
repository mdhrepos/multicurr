<?php
/**
 * Integration Cache Controller.
 *
 * @since 2.7.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Cache;

use WOOMC\Abstracts\Hookable;

/**
 * Integration Cache Controller class.
 */
class Controller extends Hookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Integration.
		 * Plugin Name: WP Super Cache
		 * Version: 1.6.3+
		 * Plugin URI: https://wordpress.org/plugins/wp-super-cache/
		 *
		 * @since 1.8.0
		 */
		if ( function_exists( 'wpsc_add_cookie' ) ) {
			$wp_super_cache = new WPSuperCache();
			$wp_super_cache->setup_hooks();
		}

		if ( class_exists( '\WP_Rocket\Plugin', false ) ) {
			$wp_rocket = new WPRocket();
			$wp_rocket->setup_hooks();
		}

	}
}
