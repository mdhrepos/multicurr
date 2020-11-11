<?php
/**
 * Admin menu setup.
 *
 * @since 1.16.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Admin;

use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\Settings\Panel;

/**
 * Class Menu
 *
 * @package WOOMC\Admin
 */
class Menu implements InterfaceHookable {

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	const SLUG_MAIN = 'woomc';

	/**
	 * Permissions.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Menu position.
	 *
	 * @var string
	 */
	const POSITION = 56;

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_action( 'admin_menu', array( $this, 'action__setup_main_menu' ) );
		\add_action( 'admin_menu', array( $this, 'action__add_settings_menu' ), App::HOOK_PRIORITY_LATER );
		\add_action( 'admin_head', array( $this, 'action__admin_head' ) );
	}

	/**
	 * Create main menu.
	 */
	public function action__setup_main_menu() {

		\add_menu_page(
			'',
			\__( 'Multi-currency', 'woocommerce-multicurrency' ),
			self::CAPABILITY,
			self::SLUG_MAIN,
			'',
			'dashicons-admin-site',
			self::POSITION
		);

	}

	/**
	 * Add pointer to the Woo Settings.
	 */
	public function action__add_settings_menu() {

		\add_submenu_page(
			self::SLUG_MAIN,
			'',
			self::icon_tag( 'dashicons-admin-settings' ) . \__( 'Settings', 'woocommerce' ),
			self::CAPABILITY,
			\add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab'  => Panel::TAB_SLUG,
				),
				'admin.php'
			)
		);
	}

	/**
	 * Remove the non-existent main menu page.
	 */
	public function action__admin_head() {

		/**
		 * Global array of submenus.
		 *
		 * @var array $submenu
		 */
		global $submenu;

		if ( isset( $submenu[ self::SLUG_MAIN ] ) ) {
			unset( $submenu[ self::SLUG_MAIN ][0] );
		}
	}

	/**
	 * Generate HTML tag for submenu icons.
	 *
	 * @param string $dashicons_id Dashicon ID.
	 *
	 * @return string
	 */
	public static function icon_tag( $dashicons_id ) {
		return '<span class="wp-menu-image dashicons-before ' . $dashicons_id . '"><span> ';
	}

}
