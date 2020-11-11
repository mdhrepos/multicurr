<?php
/**
 * Check the compatibility issues.
 *
 * @since 1.16.0
 * Copyright (c) 2019. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use TIVWP_140\InterfaceHookable;

/**
 * Class Compatibility
 *
 * @package WOOMC\Admin
 */
class Compatibility implements InterfaceHookable {

	/**
	 * Setup actions and filters.
	 *
	 * @see   Env::in_wp_admin
	 * @since 1.18.3 Assuming that we run within `in_wp_admin` block, can skip checking for user existence.
	 * @return void
	 */
	public function setup_hooks() {

		/**
		 * Hook on `admin_init` so 1) {@see \WC_Admin_Notices} already loaded and 2) we are in admin.
		 */
		\add_action( 'admin_init', array( $this, 'action__admin_init' ) );
	}

	/**
	 * Check incompatible plugins.
	 */
	public function action__admin_init() {

		$this->show_hide(
			'WC_Deposits',
			\esc_html__( 'WooCommerce Deposits', 'woocommerce-deposits' )
		);

		$this->show_hide(
			'WC_Points_Rewards',
			\esc_html__( 'WooCommerce Points and Rewards', 'woocommerce-points-and-rewards' )
		);

		$this->show_hide(
			'WC_Purolator',
			\esc_html__( 'WooCommerce Purolator', 'woocommerce-shipping-purolator' )
		);

		/**
		 * WPML.
		 *
		 * @since 1.19.0
		 */
		$this->show_hide(
			'woocommerce_wpml',
			\esc_html__( 'WooCommerce Multilingual', 'woocommerce-multilingual' )
		);
	}

	/**
	 * Show or hide the notice.
	 *
	 * @param string $class_name  Plugin's class name.
	 * @param string $plugin_name Plugin's name.
	 */
	protected function show_hide( $class_name, $plugin_name ) {

		$notice_id = 'woomc_compat_' . strtolower( $class_name );

		if ( class_exists( $class_name, false ) ) {

			if ( ! \get_user_meta( \get_current_user_id(), 'dismissed_' . $notice_id . '_notice', true ) ) {
				\WC_Admin_Notices::add_custom_notice( $notice_id, $this->notice_incompatible( $plugin_name ) );
			}
		} else {
			// Not active - remove notice.
			\WC_Admin_Notices::remove_notice( $notice_id );
			// Also remove the notice dismissal flag to show again if reactivated.
			\delete_user_meta( \get_current_user_id(), 'dismissed_' . $notice_id . '_notice' );
		}
	}

	/**
	 * Notice: Incompatible with ...
	 *
	 * @param string $with The name of the incompatible plugin.
	 *
	 * @return string
	 */
	protected function notice_incompatible( $with ) {

		$my_name = \esc_html__( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' );

		/* Translators: %1$s - WooCommerce Multi-currency, %2$s - the incompatible plugin name */
		$message = \esc_html( __( '%1$s is incompatible with %2$s.', 'woocommerce-multicurrency' ) );

		return '<p><span class="wp-ui-notification" style="padding: 2px 3px"><strong>' . sprintf( $message, $my_name, $with ) . '</strong></span></p>';
	}

}
