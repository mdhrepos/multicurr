<?php
/**
 * Currency controller.
 *
 * @since 1.0.0
 */

namespace WOOMC\Currency;

use TIVWP_140\Env;
use TIVWP_140\InterfaceHookable;
use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\Settings\Panel;

/**
 * Class Currency Controller
 */
class Controller implements InterfaceHookable {

	/**
	 * The Currency Detector instance.
	 *
	 * @var Detector
	 */
	protected $currency_detector;

	/**
	 * True if currency filtering is enabled
	 *
	 * @since 2.6.0
	 * @var bool
	 */
	protected $currency_filtering_enabled = true;

	/**
	 * Getter: $currency_filtering_enabled
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function isCurrencyFilteringEnabled() {
		return $this->currency_filtering_enabled;
	}

	/**
	 * Setter: $currency_filtering_enabled
	 *
	 * @since 2.6.0
	 *
	 * @param bool $true_false True/False.
	 *
	 * @return bool
	 */
	protected function setCurrencyFilteringEnabled( $true_false ) {
		$previous = $this->currency_filtering_enabled;

		$this->currency_filtering_enabled = (bool) $true_false;

		return $previous;
	}

	/**
	 * Enable currency filtering.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function enable_currency_filtering() {
		return $this->setCurrencyFilteringEnabled( true );
	}

	/**
	 * Disable currency filtering.
	 *
	 * @since 2.6.0
	 * @return bool
	 */
	public function disable_currency_filtering() {
		return $this->setCurrencyFilteringEnabled( false );
	}

	/**
	 * Currency Controller constructor.
	 *
	 * @param Detector $currency_detector The Currency Detector instance.
	 */
	public function __construct( Detector $currency_detector ) {
		$this->currency_detector = $currency_detector;
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	public function setup_hooks() {

		\add_filter(
			'woocommerce_currency_symbol',
			array( $this, 'filter__woocommerce_currency_symbol' ),
			App::HOOK_PRIORITY_EARLY,
			2
		);

		\add_filter(
			'woocommerce_currency',
			array( $this, 'filter__woocommerce_currency' ),
			App::HOOK_PRIORITY_EARLY,
			1
		);

		\add_filter( 'body_class', array( $this, 'filter__body_class' ) );

	}

	/**
	 * Change the currency symbol to the one from our settings panel.
	 *
	 * @param string $currency_symbol The currency symbol.
	 * @param string $currency        The currency code.
	 *
	 * @return string
	 */
	public function filter__woocommerce_currency_symbol( $currency_symbol, $currency ) {

		// Do not use this filter on our own settings tab.
		if (
			Env::is_http_get( 'page', 'wc-settings' )
			&& Env::is_http_get( 'tab', Panel::TAB_SLUG )
			&& \is_admin()
		) {
			return $currency_symbol;
		}

		// If the symbol is set in our Settings tab, return it.
		$custom_symbol = Factory::getDao()->getCustomCurrencySymbol( $currency );
		if ( $custom_symbol ) {
			$currency_symbol = $custom_symbol;
		}

		return $currency_symbol;
	}

	/**
	 * Filter the {@see get_woocommerce_currency()} to return the active currency instead of the Store currency.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function filter__woocommerce_currency( $currency ) {

		if ( $this->isCurrencyFilteringEnabled() ) {
			$currency = $this->currency_detector->currency();
		}

		return $currency;
	}

	/**
	 * Add HTML body classes for possible use in CSS rules.
	 *
	 * @since 1.15.0
	 * @since 1.20.1 Added `woocommerce-multicurrency-reloaded` class.
	 * @since 2.0.0 Do not use body class for "reloaded" check.
	 *
	 * @param array $classes Body Classes.
	 *
	 * @return array
	 */
	public function filter__body_class( $classes ) {
		$classes[] = 'woocommerce-multicurrency-' . \get_woocommerce_currency();

		return $classes;
	}
}
