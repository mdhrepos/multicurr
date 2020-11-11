<?php
/**
 * Integration with WP Rocket cache.
 *
 * @since 2.7.1
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration\Cache;

use WOOMC\Abstracts\Hookable;
use WOOMC\App;
use WOOMC\Currency\Detector;
use WOOMC\Settings\Fields;

/**
 * Class WPRocket
 */
class WPRocket extends Hookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		$this->set_caching_options();

		$this->hook_flush_rocket_to_save_woomc_settings();

		\add_filter( 'woocommerce_multicurrency_settings_fields', function ( $all_fields ) {
			$this->section_wp_rocket( $all_fields );

			return $all_fields;
		} );

	}

	/**
	 * Tell WP Rocket that:
	 * - pages vary by our cookie
	 * - there must be no rewrite rules in .htaccess
	 *
	 * @return void
	 */
	protected function set_caching_options() {

		\add_filter(
			'rocket_htaccess_mod_rewrite',
			'__return_empty_string',
			App::HOOK_PRIORITY_LATE
		);

		\add_filter(
			'rocket_cache_mandatory_cookies',
			array( $this, 'filter__rocket_cache_cookies' ),
			App::HOOK_PRIORITY_LATE
		);

		\add_filter(
			'rocket_cache_dynamic_cookies',
			array( $this, 'filter__rocket_cache_cookies' ),
			App::HOOK_PRIORITY_LATE
		);
	}

	/**
	 * Clean WP Rocket cache when we save our settings.
	 *
	 * @return void
	 */
	protected function hook_flush_rocket_to_save_woomc_settings() {
		\add_action(
			'woocommerce_multicurrency_after_save_settings',
			array( $this, 'action__flush_rocket' )
		);
	}

	/**
	 * Add our cookie to the "special treatment" by WPRocket.
	 *
	 * As per WPRocket support:
	 * - The mandatory one means that cache files are not served for the current visitor
	 *   until this specific cookie has a value.
	 * - The dynamic one means to create different cache files depending on the cookie value.
	 *
	 * @param string[] $cookies Cookies list.
	 *
	 * @return string[]
	 * @internal Filter.
	 */
	public function filter__rocket_cache_cookies( $cookies ) {

		$cookies[] = Detector::COOKIE_FORCED_CURRENCY;

		return $cookies;
	}

	/**
	 * When we save our settings, regenerate WP Rocket configs and clear the cache.
	 *
	 * @return void
	 * @internal Action.
	 */
	public function action__flush_rocket() {

		// Update the WP Rocket .htaccess rules.
		\flush_rocket_htaccess();

		// Update the WP Rocket config file.
		\rocket_generate_config_file();

		// Clear WP Rocket cache.
		\rocket_clean_domain();
	}

	/**
	 * Section "WP Rocket" in our settings panel.
	 *
	 * @param array $fields Reference to the All Fields array.
	 *
	 * @return void
	 */
	protected function section_wp_rocket( array &$fields ) {

		$section_id    = Fields::SECTION_ID_PREFIX . 'wp_rocket';
		$section_title = __( 'WP Rocket', 'woocommerce-multicurrency' );
		$section_desc  = '<i class="dashicons dashicons-info"></i>' .
						 __( 'Note: saving changes clears the WP Rocket file cache.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);

	}
}
