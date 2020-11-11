<?php
/**
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Log;

/**
 * Class Admin\Tools
 */
class Tools implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'tools' ), App::HOOK_PRIORITY_LATE );
	}

	/**
	 * Button(s) on the WooCommerce > Status > Tools page.
	 *
	 * @param array $tools All tools array.
	 *
	 * @return array
	 * @uses \WOOMC\Admin\Tools::reset_all_settings
	 */
	public function tools( $tools ) {

		$add_tools = array(
			'reset_multicurrency_settings' => array(
				'name'     => __( 'Reset Multi-currency settings', 'woocommerce-multicurrency' ),
				'button'   => __( 'Reset', 'woocommerce-multicurrency' ),
				'desc'     => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will reset all Multi-currency settings to default. This action cannot be reversed.', 'woocommerce-multicurrency' )
				),
				'callback' => array( __CLASS__, 'reset_all_settings' ),
			),
		);

		if ( defined( 'WOOMC_EXPORT_ENABLED' ) && WOOMC_EXPORT_ENABLED ) {
			$add_tools = array_merge( $add_tools, array(
				'export_multicurrency_settings' => array(
					'name'     => __( 'Export Multi-currency settings', 'woocommerce-multicurrency' ),
					'button'   => __( 'Export', 'woocommerce-multicurrency' ),
					'desc'     => __( 'This tool will export all Multi-currency settings to a file.', 'woocommerce-multicurrency' ),
					'callback' => array( $this, 'export_all_settings' ),
				),
				'import_multicurrency_settings' => array(
					'name'     => __( 'Import Multi-currency settings', 'woocommerce-multicurrency' ),
					'button'   => __( 'Import', 'woocommerce-multicurrency' ),
					'desc'     => sprintf(
						'<strong class="red">%1$s</strong> %2$s',
						__( 'Note:', 'woocommerce' ),
						__( 'This tool will import all Multi-currency settings from the previously exported file. This action cannot be reversed.', 'woocommerce-multicurrency' )
					),
					'callback' => array( $this, 'import_all_settings' ),
				),
			) );
		}

		return array_merge( $tools, $add_tools );
	}

	/**
	 * Database cleanup.
	 *
	 * @return string
	 */
	public static function reset_all_settings() {

		Log::debug( 'Resetting all settings.' );

		// Delete all options.

		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		$like = Factory::getDao()->option_name( '%' );

		$rows_affected = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '${like}';" );

		if ( false === $rows_affected ) {
			$message = esc_html__( 'There was an error resetting Multi-currency (or it was already reset).', 'woocommerce-multicurrency' );
		} else {
			$message = sprintf( /* Translators: %d - number of records */
				esc_html__( 'Multi-currency settings have been reset. Number of records deleted: %d.', 'woocommerce-multicurrency' ),
				$rows_affected
			);
		}

		return $message;
	}

	/**
	 * Export settings.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public function export_all_settings() {
		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		// Allow us to easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		\WP_Filesystem();

		/**
		 * WPDB.
		 *
		 * @global \WP_Filesystem_Direct $wp_filesystem
		 */
		global $wp_filesystem;

		$like = Factory::getDao()->option_name( '%' );

		$results = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '${like}';" );
		if ( $results && defined( 'WC_LOG_DIR' ) ) {

			$json = \wp_json_encode( $results );

			$file = \trailingslashit( WC_LOG_DIR ) . 'WooCommerce-MultiCurrency-Settings.json';
			$wp_filesystem->put_contents( $file, $json );

			$message = \esc_html__( 'Settings exported to', 'woocommerce-multicurrency' ) . ' ' . $file;

		} else {
			$message = \esc_html__( 'There was an error exporting Multi-currency settings (or they were reset).', 'woocommerce-multicurrency' );

		}

		return $message;
	}

	/**
	 * Import settings.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public function import_all_settings() {
		/**
		 * WPDB.
		 *
		 * @global \wpdb $wpdb
		 */
		global $wpdb;

		// Allow us to easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		\WP_Filesystem();

		/**
		 * WP_Filesystem_Direct.
		 *
		 * @global \WP_Filesystem_Direct $wp_filesystem
		 */
		global $wp_filesystem;

		if ( defined( 'WC_LOG_DIR' ) ) {
			$file = \trailingslashit( WC_LOG_DIR ) . 'WooCommerce-MultiCurrency-Settings.json';
			$json = $wp_filesystem->get_contents( $file );
			if ( $json ) {
				$settings = json_decode( $json, (bool) 'ASSOC' );
				$values   = array();
				foreach ( $settings as $setting ) {
					$values[] = $wpdb->prepare( '(%s,%s)', $setting['option_name'], $setting['option_value'] );
				}
				$values_str = implode( ',', $values );

				$query = "INSERT INTO $wpdb->options (option_name, option_value) VALUES {$values_str};";

				$method = array( $wpdb, 'query' );

				self::reset_all_settings();

				/**
				 * Ignore rows affected.
				 *
				 * @noinspection PhpUnusedLocalVariableInspection
				 */
				$rows_affected = $method( $query );
			}

			$message = \esc_html__( 'Settings imported', 'woocommerce-multicurrency' );
		} else {
			$message = \esc_html__( 'There was an error importing Multi-currency settings.', 'woocommerce-multicurrency' );
		}

		return $message;
	}
}
