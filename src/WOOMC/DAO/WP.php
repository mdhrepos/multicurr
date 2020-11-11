<?php
/**
 * DAO Implementation: WP
 *
 * @since 1.0.0
 */

namespace WOOMC\DAO;

use WOOMC\App;
use WOOMC\Price\Rounder;
use WOOMC\Rate\Provider\FixedRates;
use WOOMC\Rate\UpdateScheduler;

/**
 * Class WP
 */
class WP implements IDAO {

	/**
	 * Options table keys prefix.
	 *
	 * @var string
	 */
	const OPTIONS_PREFIX = 'woocommerce_multicurrency_';

	/**
	 * Full (prefixed) key name in the Options table.
	 *
	 * @since 1.15.0
	 *
	 * @param string $key The option name without prefix.
	 *
	 * @return string
	 */
	public function option_name( $key ) {
		return self::OPTIONS_PREFIX . $key;
	}

	/**
	 * Language to currency.
	 *
	 * @var string[]
	 */
	protected $language_to_currency;

	/**
	 * Default currency.
	 *
	 * @var string
	 */
	protected $default_currency;

	/**
	 * Array of price formats per currency.
	 *
	 * @var string[]
	 */
	protected $currency_to_price_format;

	/**
	 * WP constructor.
	 */
	public function __construct() {

		// WC's default currency (from the Options, with no filtering).
		$this->setDefaultCurrency( get_option( 'woocommerce_currency', 'USD' ) );

		/**
		 * Language-currency pairs are set in WPGlobus admin panel.
		 *
		 * @see \WOOMC\Settings\Panel::_build_panel
		 */
		foreach ( App::instance()->getEnabledLanguages() as $language ) {
			$this->language_to_currency[ $language ] = get_option( $this->key_language_to_currency( $language ), '' );
		}

		foreach ( $this->getEnabledCurrencies() as $currency ) {
			$this->currency_to_price_format[ $currency ] = get_option( $this->key_price_format( $currency ), '' );
		}
	}

	/**
	 * Store any value.
	 *
	 * @see \update_option for the parameter descriptions.
	 *
	 * @param string    $key      The key.
	 * @param mixed     $value    The value.
	 * @param bool|null $autoload The autoload flag.
	 *
	 * @return bool
	 */
	public function store( $key, $value, $autoload = null ) {
		return \update_option( $key, $value, $autoload );
	}

	/**
	 * Retrieve the value by key.
	 *
	 * @see \get_option for the parameter descriptions.
	 *
	 * @param string $key           The key.
	 * @param mixed  $default_value The default value to return if not found.
	 *
	 * @return mixed
	 */
	public function retrieve( $key, $default_value = false ) {
		return get_option( $key, $default_value );
	}

	/**
	 * Getters
	 */

	/**
	 * Getter for Currency To Price Format.
	 *
	 * @return string[]
	 */
	public function getCurrencyToPriceFormat() {
		return $this->currency_to_price_format;
	}

	/**
	 * Get the Currency Symbol set in the settings tab.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function getCustomCurrencySymbol( $currency ) {
		return get_option( $this->key_currency_symbol( $currency ), '' );
	}

	/**
	 * Getter for Default Currency.
	 *
	 * @return string
	 */
	public function getDefaultCurrency() {
		return $this->default_currency;
	}

	/**
	 * Returns array of enabled currency codes.
	 *
	 * @return string[]
	 */
	public function getEnabledCurrencies() {
		return get_option( $this->key_enabled_currencies(), (array) $this->getDefaultCurrency() );
	}

	/**
	 * Getter for FeePercent.
	 *
	 * @return float
	 */
	public function getFeePercent() {
		return get_option( $this->key_fee_percent(), Rounder::DEFAULT_FEE_PERCENT );
	}

	/**
	 * Getter for FixedRate.
	 *
	 * @since 1.15.0
	 * @since 2.6.1 No rounding.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return float
	 */
	public function getFixedRate( $currency ) {

		$rate = (float) get_option( $this->key_fixed_rate( $currency ), 1.0 );

		// No negative rates!
		return ( $rate <= 0 ? 1.0 : $rate );
	}

	/**
	 * Getter for "language-to-currency".
	 *
	 * @return string[]
	 */
	public function getLanguageToCurrency() {
		return $this->language_to_currency;
	}

	/**
	 * Getter for LogLevel.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function getLogLevel() {
		return \get_option( $this->key_log_level(), \WC_Log_Levels::ERROR );
	}

	/**
	 * Getter for PriceCharm.
	 *
	 * @return float
	 */
	public function getPriceCharm() {
		return \get_option( $this->key_price_charm(), Rounder::DEFAULT_PRICE_CHARM );
	}

	/**
	 * Getter for Rates Provider Credentials.
	 * This is not loaded at constructor because we need to get it again when the provider
	 * is changed in the Panel.
	 *
	 * @return string
	 */
	public function getRatesProviderCredentials() {
		return \get_option( $this->key_rates_provider_credentials( $this->getRatesProviderID() ), '' );
	}

	/**
	 * Getter for Rates Retrieval Status.
	 *
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function getRatesRetrievalStatus() {
		return \get_option( $this->key_rates_retrieval_status(), false );
	}

	/**
	 * Get the Rates Timestamp.
	 *
	 * @return string
	 */
	public function getRatesTimestamp() {
		return \get_option( $this->key_rates_timestamp(), '' );
	}

	/**
	 * Get the Rates Provider Service ID.
	 *
	 * @since 1.15.0 Default is 'FixedRates'.
	 *
	 * @return string
	 */
	public function getRatesProviderID() {
		return \get_option( $this->key_rates_provider_id(), FixedRates::id() );
	}

	/**
	 * Getter for RoundTo.
	 *
	 * @return float
	 */
	public function getRoundTo() {
		return \get_option( $this->key_round_to(), Rounder::DEFAULT_ROUND_TO );
	}

	/**
	 * Getter for VersionInDB.
	 *
	 * @return string
	 */
	public function getVersionInDB() {
		return \get_option( $this->key_version_in_db(), '' );
	}

	/**
	 * Is price per product allowed?
	 *
	 * @since 1.19.1
	 *
	 * @return bool
	 */
	public function isAllowPricePerProduct() {
		return \wc_string_to_bool( \get_option( $this->key_allow_price_per_product(), true ) );
	}

	/**
	 * Getter.
	 *
	 * @since 1.20.0
	 *
	 * @return string
	 */
	public function getRatesUpdateSchedule() {
		return \get_option( $this->key_rates_update_schedule(), UpdateScheduler::DEFAULT_SCHEDULE );
	}

	/**
	 * Is client-side cookies?
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @return bool
	 */
	public function isClientSideCookies() {
		return \wc_string_to_bool( \get_option( $this->key_client_side_cookies(), false ) );
	}

	/**
	 * Setters
	 */

	/**
	 * Setter for Default Currency.
	 *
	 * @param string $default_currency The currency code.
	 */
	public function setDefaultCurrency( $default_currency ) {
		$this->default_currency = $default_currency;
	}

	/**
	 * Setter for Enabled Currencies.
	 *
	 * @since 1.15.0
	 *
	 * @param string[] $currencies The array of currencies.
	 */
	public function setEnabledCurrencies( array $currencies ) {
		\update_option( $this->key_enabled_currencies(), $currencies );
	}

	/**
	 * Setter for FixedRate.
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 * @param float  $rate     The exchange rate.
	 */
	public function setFixedRate( $currency, $rate ) {
		\update_option( $this->key_fixed_rate( $currency ), $rate );
	}

	/**
	 * Setter for LogLevel.
	 *
	 * @since 1.15.0
	 *
	 * @param string $log_level The log level.
	 */
	public function setLogLevel( $log_level ) {
		\update_option( $this->key_log_level(), $log_level );
	}

	/**
	 * Setter for RatesRetrievalStatus.
	 *
	 * @since 1.15.0
	 *
	 * @param bool $rates_retrieval_status Rates retrieval status.
	 */
	public function setRatesRetrievalStatus( $rates_retrieval_status ) {
		\update_option( $this->key_rates_retrieval_status(), $rates_retrieval_status );
	}

	/**
	 * Setter for VersionInDB.
	 *
	 * @param string $version Version to set.
	 */
	public function setVersionInDB( $version ) {
		\update_option( $this->key_version_in_db(), $version );
	}

	/**
	 * Setter.
	 *
	 * @since 1.20.0
	 *
	 * @param string $schedule Schedule ID.
	 *
	 * @return void
	 */
	public function setRatesUpdateSchedule( $schedule ) {
		\update_option( $this->key_rates_update_schedule(), $schedule );
	}

	/**
	 * Option keys
	 */

	/**
	 * Options table keys for the Currency Symbol.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function key_currency_symbol( $currency ) {
		return self::OPTIONS_PREFIX . 'currency_symbol_' . $currency;
	}

	/**
	 * Options table key for the Enabled Currencies list.
	 *
	 * @return string
	 */
	public function key_enabled_currencies() {
		return self::OPTIONS_PREFIX . 'enabled_currencies';
	}

	/**
	 * Options table key for Fee Percent.
	 *
	 * @return string
	 */
	public function key_fee_percent() {
		return self::OPTIONS_PREFIX . 'fee_percent';
	}

	/**
	 * Options table key for Currency.
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function key_fixed_rate( $currency ) {
		return self::OPTIONS_PREFIX . 'fixed_rate_' . $currency;
	}

	/**
	 * Option table key for Language-to-currency.
	 *
	 * @param string $language The language code.
	 *
	 * @return string
	 */
	public function key_language_to_currency( $language ) {
		return self::OPTIONS_PREFIX . 'currency_' . $language;
	}

	/**
	 * Options table key for Log level.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function key_log_level() {
		return self::OPTIONS_PREFIX . 'log_level';
	}

	/**
	 * Options table key for Price charm.
	 *
	 * @return string
	 */
	public function key_price_charm() {
		return self::OPTIONS_PREFIX . 'price_charm';
	}

	/**
	 * Options table keys for the Price Format.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function key_price_format( $currency ) {
		return self::OPTIONS_PREFIX . 'price_format_' . $currency;
	}

	/**
	 * Options table key for the Rates Provider Service.
	 *
	 * @return string
	 */
	public function key_rates_provider_id() {
		return self::OPTIONS_PREFIX . 'rates_provider_id';
	}

	/**
	 * Options table key for the Rates Provider Credentials.
	 *
	 * @param string $provider_id The Provider ID.
	 *
	 * @return string
	 */
	public function key_rates_provider_credentials( $provider_id ) {
		return self::OPTIONS_PREFIX . 'rates_credentials_' . $provider_id;
	}

	/**
	 * Options table key for the Rates Retrieval Status.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function key_rates_retrieval_status() {
		return self::OPTIONS_PREFIX . 'rates_retrieval_status';
	}

	/**
	 * Options table key for the Rates Timestamp.
	 *
	 * @return string
	 */
	public function key_rates_timestamp() {
		return self::OPTIONS_PREFIX . 'rates_timestamp';
	}

	/**
	 * Options table key for Round To.
	 *
	 * @return string
	 */
	public function key_round_to() {
		return self::OPTIONS_PREFIX . 'round_to';
	}

	/**
	 * Options table key for Version in DB.
	 *
	 * @return string
	 */
	public function key_version_in_db() {
		return self::OPTIONS_PREFIX . 'version';
	}

	/**
	 * Options table key.
	 *
	 * @since 1.19.1
	 *
	 * @return string
	 */
	public function key_allow_price_per_product() {
		return self::OPTIONS_PREFIX . 'allow_price_per_product';
	}

	/**
	 * Options table key.
	 *
	 * @since 1.20.0
	 *
	 * @return string
	 */
	public function key_rates_update_schedule() {
		return self::OPTIONS_PREFIX . 'rates_update_schedule';
	}

	/**
	 * Options table key.
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @return string
	 */
	public function key_client_side_cookies() {
		return self::OPTIONS_PREFIX . 'client_side_cookies';
	}

	/**
	 * Misc. methods.
	 */

	/**
	 * Add a currency to the list of enabled currencies,
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 *
	 * @return void
	 */
	public function add_enabled_currency( $currency ) {
		$enabled_currencies = $this->getEnabledCurrencies();
		if ( ! in_array( $currency, $enabled_currencies, true ) ) {
			$enabled_currencies[] = $currency;
			$this->setEnabledCurrencies( $enabled_currencies );
		}
	}

	/**
	 * Save the Rates Timestamp.
	 *
	 * @param string $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function saveRatesTimestamp( $timestamp ) {
		\update_option( $this->key_rates_timestamp(), $timestamp );
	}

}
