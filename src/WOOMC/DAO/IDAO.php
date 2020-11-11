<?php
/**
 * DAO Interface.
 *
 * @since 1.0.0
 */

namespace WOOMC\DAO;

/**
 * Interface IDAO
 */
interface IDAO {

	/**
	 * Full (prefixed) key name in the Options table.
	 *
	 * @since 1.15.0
	 *
	 * @param string $key The option name without prefix.
	 *
	 * @return string
	 */
	public function option_name( $key );

	/**
	 * Store any value.
	 *
	 * @param string $key   The key.
	 * @param mixed  $value The value.
	 *
	 * @return bool
	 * @see \update_option for the parameter descriptions.
	 */
	public function store( $key, $value );

	/**
	 * Retrieve the value by key.
	 *
	 * @param string $key           The key.
	 * @param mixed  $default_value The default value to return if not found.
	 *
	 * @return mixed
	 * @see \get_option for the parameter descriptions.
	 */
	public function retrieve( $key, $default_value = false );

	/**
	 * Getters
	 */

	/**
	 * Getter for Currency To Price Format.
	 *
	 * @return string[]
	 */
	public function getCurrencyToPriceFormat();

	/**
	 * Get the Currency Symbol set in the settings tab.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function getCustomCurrencySymbol( $currency );

	/**
	 * Getter for Default Currency.
	 *
	 * @return string
	 */
	public function getDefaultCurrency();

	/**
	 * Returns array of enabled currency codes.
	 *
	 * @return string[]
	 */
	public function getEnabledCurrencies();

	/**
	 * Getter for FeePercent.
	 *
	 * @return float
	 */
	public function getFeePercent();

	/**
	 * Getter for FixedRate.
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 *
	 * @return float
	 */
	public function getFixedRate( $currency );

	/**
	 * Getter for "language-to-currency".
	 *
	 * @return string[]
	 */
	public function getLanguageToCurrency();

	/**
	 * Getter for LogLevel.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function getLogLevel();

	/**
	 * Getter for PriceCharm.
	 *
	 * @return float
	 */
	public function getPriceCharm();

	/**
	 * Getter for Rates Provider Credentials.
	 * This is not loaded at constructor because we need to get it again when the provider
	 * is changed in the Panel.
	 *
	 * @return string
	 */
	public function getRatesProviderCredentials();

	/**
	 * Getter for Rates Retrieval Status.
	 *
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function getRatesRetrievalStatus();

	/**
	 * Get the Rates Timestamp.
	 *
	 * @return string
	 */
	public function getRatesTimestamp();

	/**
	 * Get the Rates Provider Service ID.
	 *
	 * @return string
	 */
	public function getRatesProviderID();

	/**
	 * Getter for RoundTo.
	 *
	 * @return float
	 */
	public function getRoundTo();

	/**
	 * Getter for VersionInDB.
	 *
	 * @return string
	 */
	public function getVersionInDB();

	/**
	 * Is price per product allowed?
	 *
	 * @since 1.19.1
	 *
	 * @return bool
	 */
	public function isAllowPricePerProduct();

	/**
	 * Getter.
	 *
	 * @since 1.20.0
	 *
	 * @return string
	 */
	public function getRatesUpdateSchedule();

	/**
	 * Is client-side cookies?
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @return bool
	 */
	public function isClientSideCookies();

	/**
	 * Setters
	 */

	/**
	 * Setter for Default Currency.
	 *
	 * @param string $default_currency The currency code.
	 */
	public function setDefaultCurrency( $default_currency );

	/**
	 * Setter for Enabled Currencies.
	 *
	 * @since 1.15.0
	 *
	 * @param string[] $currencies The array of currencies.
	 */
	public function setEnabledCurrencies( array $currencies );

	/**
	 * Setter for FixedRate.
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 * @param float  $rate     The exchange rate.
	 */
	public function setFixedRate( $currency, $rate );

	/**
	 * Setter for LogLevel.
	 *
	 * @since 1.15.0
	 *
	 * @param string $log_level The log level.
	 */
	public function setLogLevel( $log_level );

	/**
	 * Setter for RatesRetrievalStatus.
	 *
	 * @since 1.15.0
	 *
	 * @param bool $rates_retrieval_status Rates retrieval status.
	 */
	public function setRatesRetrievalStatus( $rates_retrieval_status );

	/**
	 * Setter for VersionInDB.
	 *
	 * @param string $version Version to set.
	 */
	public function setVersionInDB( $version );

	/**
	 * Setter.
	 *
	 * @since 1.20.0
	 *
	 * @param string $schedule Schedule ID.
	 *
	 * @return void
	 */
	public function setRatesUpdateSchedule( $schedule );

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
	public function key_currency_symbol( $currency );

	/**
	 * Options table key for the Enabled Currencies list.
	 *
	 * @return string
	 */
	public function key_enabled_currencies();

	/**
	 * Options table key for Fee Percent.
	 *
	 * @return string
	 */
	public function key_fee_percent();

	/**
	 * Options table key for Currency.
	 *
	 * @since 1.15.0
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function key_fixed_rate( $currency );

	/**
	 * Option table key for Language-to-currency.
	 *
	 * @param string $language The language code.
	 *
	 * @return string
	 */
	public function key_language_to_currency( $language );

	/**
	 * Options table key for Log level.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function key_log_level();

	/**
	 * Options table key for Price charm.
	 *
	 * @return string
	 */
	public function key_price_charm();

	/**
	 * Options table keys for the Price Format.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return string
	 */
	public function key_price_format( $currency );

	/**
	 * Options table key for the Rates Provider Service.
	 *
	 * @return string
	 */
	public function key_rates_provider_id();

	/**
	 * Options table key for the Rates Provider Credentials.
	 *
	 * @param string $provider_id The Provider ID.
	 *
	 * @return string
	 */
	public function key_rates_provider_credentials( $provider_id );

	/**
	 * Options table key for the Rates Retrieval Status.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function key_rates_retrieval_status();

	/**
	 * Options table key for the Rates Timestamp.
	 *
	 * @return string
	 */
	public function key_rates_timestamp();

	/**
	 * Options table key for Round To.
	 *
	 * @return string
	 */
	public function key_round_to();

	/**
	 * Options table key for Version in DB.
	 *
	 * @return string
	 */
	public function key_version_in_db();

	/**
	 * Options table key.
	 *
	 * @since 1.19.1
	 *
	 * @return string
	 */
	public function key_allow_price_per_product();

	/**
	 * Options table key.
	 *
	 * @since 1.20.0
	 *
	 * @return string
	 */
	public function key_rates_update_schedule();

	/**
	 * Options table key.
	 *
	 * @since 2.6.3-rc.2
	 *
	 * @return string
	 */
	public function key_client_side_cookies();

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
	public function add_enabled_currency( $currency );

	/**
	 * Save the Rates Timestamp.
	 *
	 * @param string $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function saveRatesTimestamp( $timestamp );

}
