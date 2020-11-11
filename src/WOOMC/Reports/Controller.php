<?php
/**
 * Reports Controller.
 *
 * @since 1.7.0
 * Copyright (c) 2018. TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Reports;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;

/**
 * Class Reports\Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * The selected currency is transferred between page loads via this cookie.
	 *
	 * @var string
	 */
	const COOKIE_REPORTS_CURRENCY = 'woocommerce_multicurrency_reports_currency';

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG_CURRENCY_SELECTOR = 'woocommerce_multicurrency_reports_currency_selector';

	/**
	 * The selected currency.
	 *
	 * @var string
	 */
	protected $selected_currency = '';

	/**
	 * The selected currency symbol.
	 *
	 * @var string
	 */
	protected $selected_currency_symbol = '';

	/**
	 * Dispatcher.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		if ( ! Env::in_wp_admin() ) {
			return;
		}

		/**
		 * Run the Reports Controller on the 'wc-reports' page.
		 *
		 * @since 1.7.0
		 * @since 1.16.0   Do not run on the Product Vendors tab.
		 *                 The values there are always shown in the Store currency.
		 */
		if ( ! Env::is_http_get( 'tab', 'vendors' ) ) {
			$page_hook = 'woocommerce_page_wc-reports';
			\add_action( "load-{$page_hook}", array( $this, 'reports_controller' ) );
		}

		/**
		 * Only hook in admin parts if the user has admin access.
		 *
		 * @see \WC_Admin_Dashboard::__construct
		 */
		if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ) {
			$pagenow = 'index.php';
			add_action( "load-{$pagenow}", array( $this, 'dashboard_controller' ) );
		}
	}

	/**
	 * Handle dashboard widget.
	 *
	 * @internal
	 */
	public function dashboard_controller() {

		$this->init_selected_currency();

		$dashboard = new Dashboard( $this->selected_currency );
		$dashboard->setup_hooks();
	}

	/**
	 * Handle reports page.
	 *
	 * @internal
	 */
	public function reports_controller() {

		$this->init_selected_currency();

		$page = new Page( $this->selected_currency );
		$page->setup_hooks();
	}

	/**
	 * Initialize the currency settings.
	 */
	protected function init_selected_currency() {
		if ( ! empty( $_COOKIE[ self::COOKIE_REPORTS_CURRENCY ] ) ) {
			$this->selected_currency = \sanitize_text_field( $_COOKIE[ self::COOKIE_REPORTS_CURRENCY ] );
		} else {
			$this->selected_currency = \get_woocommerce_currency();
		}
		$this->selected_currency_symbol = \get_woocommerce_currency_symbol( $this->selected_currency );
		// Force the currency symbols.
		\add_filter( 'woocommerce_currency_symbol', array( $this, 'set_currency_symbol' ), App::HOOK_PRIORITY_LATE, 0 );
	}

	/**
	 * Force the currency symbol to match the selected currency.
	 *
	 * @return string
	 * @internal
	 */
	public function set_currency_symbol() {
		return '(' . $this->selected_currency_symbol . ') ';
	}
}
