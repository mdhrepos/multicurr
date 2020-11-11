<?php
/**
 * Add currency selector to WooCommerce Analytics
 *
 * @since 2.5.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin\Analytics;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use TIVWP_140\InterfaceHookable;
use WOOMC\API;
use WOOMC\App;
use WOOMC\Log;

/**
 * Class Admin\Analytics\Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * Name of the JS script.
	 *
	 * @var string
	 */
	const JS_NAME = 'analytics';

	/**
	 * WC-Admin pages that need the currency filter.
	 *
	 * @var string[]
	 */
	const ANALYTICS_PAGES = array(
		'orders',
		'revenue',
		'products',
		'categories',
		'coupons',
		'taxes',
	);

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( \apply_filters( 'woocommerce_admin_disabled', false ) ) {
			return;
		}

		\add_action( 'init', array( $this, 'add_currency_settings' ) );

		foreach ( self::ANALYTICS_PAGES as $analytics_page ) {
			\add_filter(
				"woocommerce_analytics_{$analytics_page}_query_args",
				array( $this, 'apply_currency_arg' )
			);
			\add_filter( "woocommerce_analytics_{$analytics_page}_stats_query_args",
				array( $this, 'apply_currency_arg' )
			);
		}

		\add_filter(
			'woocommerce_analytics_clauses_join',
			array( $this, 'filter__clauses_join' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_analytics_clauses_where',
			array( $this, 'filter__clauses_where' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_analytics_clauses_select',
			array( $this, 'filter__clauses_select' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_action(
			'admin_enqueue_scripts',
			array( $this, 'add_extension_register_script' )
		);

	}

	/**
	 * Add currency settings to the AssetDataRegistry.
	 */
	public function add_currency_settings() {

		$enabled_currencies = API::enabled_currencies();

		$currency_names = API::currency_names();

		$currencies = array();
		foreach ( $enabled_currencies as $enabled_currency ) {
			$currencies[] = array(
				'label' => $enabled_currency . ': ' . trim( $currency_names[ $enabled_currency ] ),
				'value' => $enabled_currency,
			);
		}

		$WooMC = array(
			'i18n'       => array(
				'Currency' => \__( 'Currency', 'woocommerce' ),
			),
			'currencies' => $currencies,
		);

		try {

			$depend_on = array(
				'\Automattic\WooCommerce\Blocks\Package',
				'\Automattic\WooCommerce\Admin\Loader',
			);
			foreach ( $depend_on as $class_name ) {
				if ( ! class_exists( $class_name, false ) ) {
					throw new \Exception( 'Class not loaded: ' . $class_name );
				}
			}

			/**
			 * AssetDataRegistry.
			 *
			 * @var AssetDataRegistry $data_registry
			 */
			$data_registry = Package::container()->get( AssetDataRegistry::class );

			$data_registry->add( 'WooMC', $WooMC );

		} catch ( \Exception $e ) {
			Log::error( $e );
		}

	}

	/**
	 * Return the active currency: from _GET or store default.
	 *
	 * @return string
	 */
	protected function get_active_currency() {

		// No nonce in wc-admin package.
		0 && \wp_verify_nonce( '' );

		if ( ! empty( $_GET['currency'] ) ) {
			$currency = \sanitize_text_field( \wp_unslash( $_GET['currency'] ) );
		} else {
			$currency = API::default_currency();
		}

		return $currency;
	}

	/**
	 * Add currency to the admin GET query arguments.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function apply_currency_arg( $args ) {

		$args['currency'] = $this->get_active_currency();

		return $args;
	}

	/**
	 * Add currency to the JOIN clause.
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Unused.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__clauses_join( $clauses, $context ) {

		/**
		 * WPDB.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$clauses[] = "JOIN {$wpdb->postmeta} currency_postmeta ON {$wpdb->prefix}wc_order_stats.order_id = currency_postmeta.post_id";

		return $clauses;
	}

	/**
	 * Add currency to the WHERE clause.
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Unused.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__clauses_where( $clauses, $context ) {

		$currency = $this->get_active_currency();

		$clauses[] = "AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = '{$currency}'";

		return $clauses;
	}

	/**
	 * Add currency to the SELECT clause.
	 *
	 * @param string[] $clauses The array of clauses.
	 * @param string   $context Unused.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function filter__clauses_select( $clauses, $context ) {

		$clauses[] = ', currency_postmeta.meta_value AS currency';

		return $clauses;
	}

	/**
	 * Register the JS.
	 */
	public function add_extension_register_script() {

		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! Loader::is_admin_page() ) {
			return;
		}

		$script_url = \plugin_dir_url( __FILE__ ) . 'dist/' . self::JS_NAME . '.js';

		\wp_enqueue_script(
			'woomc-' . self::JS_NAME,
			$script_url,
			array( 'wp-hooks' ),
			WOOCOMMERCE_MULTICURRENCY_VERSION,
			true
		);
	}
}
