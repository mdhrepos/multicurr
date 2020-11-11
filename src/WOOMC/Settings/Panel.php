<?php
/**
 * Settings panel.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Settings;

use TIVWP_140\InterfaceHookable;
use TIVWP_140\Logger\Message;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Log;
use WOOMC\Rate\CurrentProvider;
use WOOMC\Rate\Provider\Currencylayer;
use WOOMC\Rate\Provider\FixedRates;
use WOOMC\Rate\Provider\OpenExchangeRates;
use WOOMC\Rate\Update\Manager as RateUpdateManager;

/**
 * Class Settings\Panel
 */
class Panel implements InterfaceHookable {

	/**
	 * WooCommerce settings tab slug.
	 *
	 * @var string
	 */
	const TAB_SLUG = 'multicurrency';

	/**
	 * The Fields instance.
	 *
	 * @var Fields
	 */
	protected $fields;

	/**
	 * Panel constructor.
	 *
	 * @param Fields $fields The Fields instance.
	 */
	public function __construct( Fields $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		\add_action( 'woocommerce_settings_' . self::TAB_SLUG, array( $this, 'output_fields' ) );

		if ( ! App::instance()->isReadOnlySettings() ) {
			$this->setup_hooks_on_provider_change();
			\add_action(
				'woocommerce_update_options_' . self::TAB_SLUG,
				array( $this, 'save_fields' ),
				App::HOOK_PRIORITY_LATE
			);
		}

	}

	/**
	 * Add our tab to the WooCommerce Settings.
	 *
	 * @param array $settings_tabs Existing tabs.
	 *
	 * @return array Tabs with ours added.
	 */
	public static function add_settings_tab( array $settings_tabs ) {
		$settings_tabs[ self::TAB_SLUG ] = _x( 'Multi-currency', 'Settings tab title', 'woocommerce-multicurrency' );

		return $settings_tabs;
	}

	/**
	 * Display fields on our tab.
	 */
	public function output_fields() {
		\WC_Admin_Settings::output_fields( $this->fields->get_all() );
		$this->fields->js_show_hide_credentials();
		$this->fields->js_rounding_calculator();
		$this->fields->styles();
		if ( App::instance()->isReadOnlySettings() ) {
			$this->fields->js_disable_save();
		}
	}

	/**
	 * When {@see Panel::save_fields} is invoked,
	 * if Provider ID or credentials changed, we need to update the rates.
	 *
	 * @return void
	 */
	protected function setup_hooks_on_provider_change() {

		$dao = Factory::getDao();

		$providers = \apply_filters(
			'woocommerce_multicurrency_providers',
			array(
				FixedRates::id()        => FixedRates::id(),
				OpenExchangeRates::id() => OpenExchangeRates::id(),
				Currencylayer::id()     => Currencylayer::id(),
			)
		);

		\add_action( 'update_option_' . $dao->key_rates_provider_id(), array( $this, 'force_update_rates' ) );

		foreach ( $providers as $provider_id => $provider_name ) {
			$option = $dao->key_rates_provider_credentials( $provider_id );
			\add_action( "add_option_{$option}", array( $this, 'force_update_rates' ) );
			\add_action( "update_option_{$option}", array( $this, 'force_update_rates' ) );
		}
	}

	/**
	 * Force updating rates.
	 *
	 * @since 1.15.0 this method is static.
	 * @since 1.20.0 Calls Updater directly.
	 * @since 1.20.0 Moved to Panel class. Made dynamic again.
	 *
	 * @return void
	 * @internal
	 */
	public function force_update_rates() {
		RateUpdateManager::setNeedToUpdate();
	}

	/**
	 * Update the settings.
	 *
	 * @throws \Exception Caught.
	 */
	public function save_fields() {

		\WC_Admin_Settings::save_fields( $this->fields->get_all() );

		/**
		 * The default currency must always be enabled.
		 *
		 * @since 1.15.0
		 */
		$dao = Factory::getDao();
		$dao->add_enabled_currency( $dao->getDefaultCurrency() );

		/**
		 * With FixedRates provider, force update rates on every settings save.
		 * Otherwise, the manual rates won't go to the rates array.
		 *
		 * @since 1.15.0
		 */
		if ( CurrentProvider::isFixedRates() ) {
			Log::debug( new Message(
				array( 'Saving settings with FixedRate provider', 'Have to force update rates.' ) ) );
			RateUpdateManager::setNeedToUpdate();
		}

		/**
		 * Call the update. It will only act if needed.
		 *
		 * @since 1.20.0
		 */
		RateUpdateManager::update();

		/**
		 * Act after settings saved.
		 *
		 * @since 2.7.1
		 */
		\do_action( 'woocommerce_multicurrency_after_save_settings' );
	}
}
