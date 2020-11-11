<?php
/**
 * Field definitions for the settings tab.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Settings;

use WOOMC\App;
use WOOMC\DAO\Factory;
use WOOMC\DAO\IDAO;
use WOOMC\DAO\WP;
use WOOMC\Integration\Multilingual;
use WOOMC\Log;
use WOOMC\Price\Rounder;
use WOOMC\Rate\CurrentProvider;
use WOOMC\Rate\Provider\Currencylayer;
use WOOMC\Rate\Provider\FixedRates;
use WOOMC\Rate\Provider\OpenExchangeRates;
use WOOMC\Rate\Storage;
use WOOMC\Rate\UpdateScheduler;

/**
 * Class Settings\Fields
 */
class Fields {

	/**
	 * Field sections prefix.
	 *
	 * @var string
	 */
	const SECTION_ID_PREFIX = 'woocommerce-multicurrency_';

	/**
	 * CSS class for credentials input fields. Needed for the show/hide JS.
	 *
	 * @var string
	 */
	const CSS_CLASS_CREDENTIALS_INPUT = 'rates_credentials_input';

	/**
	 * Currencies with rates.
	 *
	 * @var  array
	 */
	protected $currencies_with_rates;

	/**
	 * DAO.
	 *
	 * @var  IDAO
	 */
	protected $dao;

	/**
	 * Rate Storage instance.
	 *
	 * @var Storage
	 */
	protected $rate_storage;

	/**
	 * Fields constructor.
	 *
	 * @param Storage $rate_storage Rate Storage instance.
	 */
	public function __construct( Storage $rate_storage ) {
		$this->rate_storage = $rate_storage;
		$this->dao          = Factory::getDao();
	}

	/**
	 * Build all panel fields.
	 *
	 * @return array
	 */
	public function get_all() {

		/**
		 * This is not done in the constructor
		 * because we need the information updated after saving the settings.
		 */
		$this->currencies_with_rates = $this->rate_storage->woocommerce_currencies_with_rates();

		$all_fields = array();

		$this->section_intro( $all_fields );
		$this->section_rates_service( $all_fields );

		if ( $this->dao->getRatesProviderID() && ! $this->dao->getRatesRetrievalStatus() ) {
			// Provider is set but rates not retrieved.
			$this->section_rates_not_retrieved( $all_fields );
		}

		if ( count( $this->currencies_with_rates ) ) {
			if ( ! CurrentProvider::isFixedRates() ) {
				$this->section_rates_timestamp( $all_fields );
			}

			$this->section_enabled_currencies( $all_fields );

			if ( CurrentProvider::isFixedRates() && count( $this->dao->getEnabledCurrencies() ) > 1 ) {
				// Show the fixed rates section only if:
				// - provider is 'FixedRates'
				// - more than 1 currency is enabled (because 1 is always the default).
				$this->section_fixed_rates( $all_fields );
			}

			$this->section_currency_symbols( $all_fields );
			$this->section_price_conversion_settings( $all_fields );
			$this->section_price_formats( $all_fields );

			if ( App::instance()->isMultilingual() ) {
				$this->section_auto_currencies( $all_fields );
			} else {
				$this->section_auto_currencies_is_disabled( $all_fields );
			}
		}

		if ( App::instance()->isReadOnlySettings() ) {
			$this->section_save_is_disabled( $all_fields );
		}

		$this->section_general_settings( $all_fields );

		return apply_filters( 'woocommerce_multicurrency_settings_fields', $all_fields );

	}

	/**
	 * Section "intro".
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_intro( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'intro';
		$section_title = __( 'WooCommerce Multi-currency', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				'<div class="howto">' .
				__( 'Thank you for installing the multi-currency extension! We appreciate your business!', 'woocommerce-multicurrency' ),
				sprintf( /* Translators: placeholders for HTML "a" tag linking 'here' to the Support page. */
					__( 'Please configure the settings using the instructions below. Should you need help, please contact our technical support by clicking %1$shere%2$s.', 'woocommerce-multicurrency' ),
					'<a href="' . App::instance()->getUrlSupport() . '">',
					'</a>'
				),
				'</div>',
			)
		);

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

	/**
	 * Section "General settings".
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_general_settings( array &$fields ) {

		$section_id    = self::SECTION_ID_PREFIX . 'general_settings';
		$section_title = \esc_html__( 'General options', 'woocommerce' );
		$section_desc  = '';

		$fields[] = array(
			'id'    => $section_id,
			'title' => $section_title,
			'desc'  => $section_desc,
			'type'  => 'title',
		);

		$fields[] = array(
			'id'      => $this->dao->key_allow_price_per_product(),
			'title'   => __( 'Allow custom product pricing for extra currencies', 'woocommerce-multicurrency' ),
			'desc'    => __( 'If checked, you can enter product prices for each currency.', 'woocommerce-multicurrency' ) . ' ' . __( '(Simple and variable products only)', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'yes',
		);

		$fields[] = array(
			'id'      => $this->dao->key_client_side_cookies(),
			'title'   => __( 'Client-side cookies only', 'woocommerce-multicurrency' ),
			'desc'    => __( 'Do not enable this unless asked by Support. Does not work with some page caching plugins!', 'woocommerce-multicurrency' ),
			'type'    => 'checkbox',
			'default' => 'no',
		);

		/*
		 * Log level.
		 */
		$link_view_logs =
			'<p style="font-style: normal; padding-left: 8px">' .
			'<a href="' . \admin_url( 'admin.php?page=wc-status&tab=logs' ) . '" target="_blank">' .
			\esc_html__( 'View logs', 'woocommerce-multicurrency' ) .
			'</a>' .
			'</p>';

		$fields[] = array(
			'title'    => \esc_html__( 'Log level', 'woocommerce-multicurrency' ),
			'desc'     => '<br>' . $link_view_logs,
			'id'       => $this->dao->key_log_level(),
			'css'      => 'min-width:350px;',
			'default'  => $this->dao->getLogLevel(),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'desc_tip' => \esc_html__( 'What to write to the log file.', 'woocommerce-multicurrency' ),
			'options'  => array(
				Log::LOG_LEVEL_NONE   => \esc_html__( 'Nothing', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::ERROR => \esc_html__( 'Error conditions', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::INFO  => \esc_html__( 'Informational messages', 'woocommerce-multicurrency' ),
				\WC_Log_Levels::DEBUG => \esc_html__( 'Debug-level messages', 'woocommerce-multicurrency' ),
				Log::LOG_LEVEL_TRACE  => \esc_html__( 'Debug with tracing', 'woocommerce-multicurrency' ),
			),
		);

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => $section_id,
		);
	}

	/**
	 * Section "rates service".
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_rates_service( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_service';
		$section_title = __( 'Currency Exchange Rates', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				__( 'To switch between currencies, your website needs to get the exchange rates from one of the service providers.', 'woocommerce-multicurrency' ),
				__( 'Alternatively, you can select the FixedRates option and enter the rates manually.', 'woocommerce-multicurrency' ),
				'<i class="dashicons dashicons-media-document"></i> ' .
				sprintf( /* translators: %1$, %2$ are HTML tags linking "here" to documentation. */
					__( 'Please read the instructions %1$shere%2$s.', 'woocommerce-multicurrency' ),
					'<a href="' . App::instance()->getUrlDocumentation() . '">',
					'</a>'
				),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$providers = \apply_filters(
			'woocommerce_multicurrency_providers',
			array(
				FixedRates::id()        => FixedRates::id(),
				OpenExchangeRates::id() => OpenExchangeRates::id(),
				Currencylayer::id()     => Currencylayer::id(),
			)
		);

		$providers_credentials_name = \apply_filters(
			'woocommerce_multicurrency_providers_credentials_name',
			array(
				FixedRates::id()        => FixedRates::credentials_label(),
				OpenExchangeRates::id() => OpenExchangeRates::credentials_label(),
				Currencylayer::id()     => Currencylayer::credentials_label(),
			)
		);

		$fields[] =
			array(
				'title'    => __( 'Service Provider', 'woocommerce-multicurrency' ),
				'desc'     => __( 'Please choose one.', 'woocommerce-multicurrency' ),
				'id'       => $this->dao->key_rates_provider_id(),
				'css'      => 'min-width:350px;',
				'default'  => $this->dao->getRatesProviderID(),
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'desc_tip' => true,
				'options'  => $providers,
			);

		foreach ( $providers as $provider_id => $provider_name ) {
			if ( FixedRates::id() === $provider_id ) {
				continue;
			}
			$fields[] =
				array(
					'title'             => $provider_id . ' ' . $providers_credentials_name[ $provider_id ],
					'id'                => $this->dao->key_rates_provider_credentials( $provider_id ),
					'default'           => $this->dao->getRatesProviderCredentials(),
					'type'              => 'password',
					'class'             => 'input-text regular-input ' . self::CSS_CLASS_CREDENTIALS_INPUT,
					'custom_attributes' => array(
						// Prevent browser from filling in this field automatically.
						'autocomplete'  => 'off',
						// Do not show LastPass password icon on this field.
						'data-lpignore' => 'true',
					),
				);
		}

		/**
		 * Cron interval options for rates update.
		 *
		 * @since 1.20.0
		 */

		// Get the existing schedules and filter out all but those we need. See `options` below.
		$cron_schedules = array_intersect_key(
			\wp_get_schedules(),
			array(
				UpdateScheduler::DEFAULT_SCHEDULE => '',
				UpdateScheduler::CUSTOM_SCHEDULE  => '',
				'hourly'                          => '',
				'daily'                           => '',
			)
		);

		$options = array();
		foreach ( $cron_schedules as $recurrence => $schedule ) {
			$options[ $recurrence ] = $cron_schedules[ $recurrence ]['display'];
		}

		$fields[] = array(
			'title'    => \esc_html__( 'Rates update schedule', 'woocommerce-multicurrency' ),
			'desc_tip' => \esc_html__( 'Not applicable to FixedRates', 'woocommerce-multicurrency' ),
			'id'       => $this->dao->key_rates_update_schedule(),
			'css'      => 'min-width:350px;',
			'default'  => $this->dao->getRatesUpdateSchedule(),
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'options'  => $options,
		);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);

	}

	/**
	 * Display the date and time when the Provider updated the rates.
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_rates_timestamp( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_timestamp';
		$section_title = '';

		$timestamp = $this->dao->getRatesTimestamp();

		$section_desc = $timestamp ?
			'<i class="dashicons dashicons-clock"></i> ' .
			__( 'Rates updated on ', 'woocommerce-multicurrency' ) . '<code>' .
			date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp )
			. ' (UTC)</code>'
			: '';

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

	/**
	 * Display notice that the rates were not retrieved.
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_rates_not_retrieved( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rates_not_retrieved';
		$section_title = '';

		$section_desc =
			'<span class="notice-large wp-ui-notification"><i class="dashicons dashicons-warning"></i> ' .
			__( 'Currency exchange rates were not retrieved. Additional information may be available in the log.', 'woocommerce-multicurrency' ) .
			'</span>';

		$fields[] = array(
			'type'  => 'title',
			'id'    => $section_id,
			'title' => $section_title,
			'desc'  => $section_desc,
		);

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => $section_id,
		);
	}

	/**
	 * Section "enabled currencies".
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_enabled_currencies( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'enabled_currencies';
		$section_title = __( 'Enabled currencies', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				__( 'Please specify all currencies you plan to use.', 'woocommerce-multicurrency' ),
				sprintf( /* Translators: %s - "Save changes" */
					__( 'Then please click the [%s] button at the bottom to continue.', 'woocommerce-multicurrency' ),
					__( 'Save Changes' )
				),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		// Show currency codes, names and rates.
		$options = $this->currencies_with_rates;
		$rates   = $this->rate_storage->getRates();
		foreach ( $options as $currency_symbol => $currency_name ) {
			$options[ $currency_symbol ] = $currency_symbol . ': ' . $currency_name;
			/**
			 * The rate is empty rate when adding a new currency with FixedRates provider.
			 *
			 * @since 1.15.0
			 */
			if ( 'USD' !== $currency_symbol && ! empty( $rates[ $currency_symbol ] ) ) {
				$options[ $currency_symbol ] .= ' = USD/' . $rates[ $currency_symbol ];
			}
		}

		$fields[] =
			array(
				'title'             => __( 'Currencies', 'woocommerce-multicurrency' ),
				'id'                => $this->dao->key_enabled_currencies(),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => (array) $this->dao->getDefaultCurrency(),
				'desc'              => __( 'Select all currencies that will be available on your website.', 'woocommerce-multicurrency' ),
				'options'           => $options,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => esc_attr__( 'Select currencies', 'woocommerce-multicurrency' ),
					'required'         => 'required',
				),
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "auto-currencies" (if multilingual).
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_auto_currencies( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'auto_currencies';
		$section_title = __( 'Link currency to language', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				__( 'If you would like to set the currency <strong>automatically</strong> when the language is switched, please set the "Language - Currency" pairs below.', 'woocommerce-multicurrency' ),
				'<i class="dashicons dashicons-warning"></i> ' .
				__( 'Note: our Currency Selector Widget can be used for the <strong>manual</strong> currency switching, independently of the language. In that case, the below settings will be ignored.', 'woocommerce-multicurrency' ),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		// For "Enabled currencies only" drop-down boxes.
		$enabled_currency_code_options = array_intersect_key( $this->currencies_with_rates, array_flip( $this->dao->getEnabledCurrencies() ) );

		// Prepare the "nice" dropdown boxes.
		foreach ( $this->currencies_with_rates as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
		foreach ( $enabled_currency_code_options as $code => $name ) {
			$enabled_currency_code_options[ $code ] = $code . ': ' . $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}

		/**
		 * Add the default option, which allows not to link any of the language (or all) to the currency.
		 *
		 * @since 1.4.0
		 * Previously, each language was linked to the shop currency by default, which prevented
		 * setting currency by the user's location.
		 */
		$enabled_currency_code_options     = array_reverse( $enabled_currency_code_options, true );
		$enabled_currency_code_options[''] = esc_html__( 'Not linked', 'woocommerce-multicurrency' );
		$enabled_currency_code_options     = array_reverse( $enabled_currency_code_options, true );

		// Show the dropdown for each language (if WPGlobus is active).
		foreach ( App::instance()->getEnabledLanguages() as $language ) {

			$fields[] =
				array(
					'title'    => App::instance()->getEnLanguageName( $language ),
					// Translators: placeholder for the language name.
					'desc'     => sprintf( __( 'Currency to use when the language is switched to %s.', 'woocommerce-multicurrency' ), App::instance()->getEnLanguageName( $language ) ),
					'id'       => $this->dao->key_language_to_currency( $language ),
					'css'      => 'min-width:350px;',
					'default'  => '',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'desc_tip' => true,
					'options'  => $enabled_currency_code_options,
				);
		}
		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "price formats".
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_price_formats( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'price_formats';
		$section_title = __( 'Price formats', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				__( 'Here you can change the way the prices are displayed, separately for each currency.', 'woocommerce-multicurrency' ) .
				// Translators: placeholder for the WooCommerce price format.
				sprintf( __( 'The default format is <code>%s</code>', 'woocommerce-multicurrency' ), get_woocommerce_price_format() ),
				// Translators: placeholders will be displayed as-is.
				__( 'The <code>%1$s</code> is the placeholder for the currency symbol. <code>%2$s</code> - for the amount.', 'woocommerce-multicurrency' ),
				// Translators: placeholders will be displayed as-is.
				__( 'For example, if you want the currency symbol to go after the amount, you can use the <code>%2$s%1$s</code> format.', 'woocommerce-multicurrency' ),
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			$fields[] =
				array(
					// Translators: placeholder for the currency symbol.
					'title'       => sprintf( __( '%s price format', 'woocommerce-multicurrency' ), $enabled_currency ),
					'type'        => 'text',
					'id'          => $this->dao->key_price_format( $enabled_currency ),
					'class'       => 'input-text regular-input',
					'placeholder' => esc_html_x( 'Default', 'Settings placeholder', 'woocommerce-multicurrency' ),
				);

		}
		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "Currency symbols".
	 *
	 * @since 1.1.0
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_currency_symbols( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'currency_symbols';
		$section_title = __( 'Currency Symbols', 'woocommerce-multicurrency' );
		$section_desc  = __( 'Change the currency symbol. Enter, for example, <code>C$</code> for Canadian Dollars.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			$fields[] =
				array(
					// Translators: placeholder for the currency symbol.
					'title' => sprintf( __( '%s symbol', 'woocommerce-multicurrency' ), $enabled_currency ),
					'type'  => 'text',
					'id'    => $this->dao->key_currency_symbol( $enabled_currency ),
					'class' => 'input-text regular-input',
					'desc'  => esc_html_x( 'Default', 'Settings placeholder', 'woocommerce-multicurrency' ) . ': ' . get_woocommerce_currency_symbol( $enabled_currency ),
				);
		}

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * The Fixed Rates section.
	 *
	 * @since 1.15.0
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_fixed_rates( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'fixed_rates';
		$section_title = __( 'Fixed Rates', 'woocommerce-multicurrency' );
		$section_desc  = __( 'Enter the exchange rate of each currency relative to the US Dollar.', 'woocommerce-multicurrency' );

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		foreach ( $this->dao->getEnabledCurrencies() as $enabled_currency ) {
			if ( 'USD' === $enabled_currency ) {
				continue;
			}
			$fields[] =
				array(
					'title'             => 'USD/' . $enabled_currency . ': &nbsp; $1 US = ',
					'type'              => 'number',
					'id'                => $this->dao->key_fixed_rate( $enabled_currency ),
					'desc'              => $enabled_currency,
					'default'           => 1.0,
					'placeholder'       => '1',
					'custom_attributes' => array(
						'min'  => 0.0000001,
						'step' => 'any',
					),
					'css'               => 'width: 10em; text-align: right',
				);
		}

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Section "price conversion settings".
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_price_conversion_settings( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'rounding_settings';
		$section_title = __( 'Price Conversion Settings', 'woocommerce-multicurrency' );
		$section_desc  = implode(
			' <br/>',
			array(
				__( 'Fine-tune the prices after currency conversion.', 'woocommerce-multicurrency' ),
				'<div style="font-family: monospace"><strong>' .
				__( 'Example', 'woocommerce-multicurrency' ) . ': ' .
				'</strong>' .
				__( 'product price', 'woocommerce-multicurrency' ) .
				' &rarr; ' .
				__( 'price after conversion', 'woocommerce-multicurrency' ) .
				' (' . __( 'change the values below to recalculate', 'woocommerce-multicurrency' ) . ')' .
				'<br/>' .
				__( 'Rate', 'woocommerce-multicurrency' ) . ': ' .
				'<span id="rate_example"></span><span id="rounding_calculator"></span>' .
				'</div>',
			)
		);

		$fields[] =
			array(
				'id'    => $section_id,
				'title' => $section_title,
				'desc'  => $section_desc,
				'type'  => 'title',
			);

		$fields[] =
			array(
				'title'    => __( 'Add a conversion fee (%)', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_fee_percent(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_FEE_PERCENT,
				'desc'     => __( 'Enter 2.5 to increase the converted price by 2.5%', 'woocommerce-multicurrency' ),
				'desc_tip' => true,
			);

		$fields[] =
			array(
				'title'    => __( 'Round up to', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_round_to(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_ROUND_TO,
				'desc'     => __( 'Enter 10 to round 123.45 to 130', 'woocommerce-multicurrency' ),
				'desc_tip' => true,
			);

		$fields[] =
			array(
				'title'    => __( 'Price charm', 'woocommerce-multicurrency' ),
				'type'     => 'text',
				'id'       => $this->dao->key_price_charm(),
				'class'    => 'input-text regular-input',
				'default'  => Rounder::DEFAULT_PRICE_CHARM,
				'desc'     => __( 'Enter 0.01 to show 50 as 49.99', 'woocommerce-multicurrency' ),
				'desc_tip' => true,
			);

		$fields[] =
			array(
				'type' => 'sectionend',
				'id'   => $section_id,
			);
	}

	/**
	 * Display notice that the saving is disabled.
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_save_is_disabled( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'save_is_disabled';
		$section_title = '';
		$section_desc  =
			'<p><span class="wp-ui-notification" style="padding:5px;">' .
			'<span class="dashicons dashicons-lock"></span> ' .
			__( 'Saving changes is not permitted.', 'woocommerce-multicurrency' ) .
			'</span></p>';

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

	/**
	 * Display notice that the Auto-currencies is disabled.
	 *
	 * @param array $fields Reference to the All Fields array.
	 */
	protected function section_auto_currencies_is_disabled( array &$fields ) {
		$section_id    = self::SECTION_ID_PREFIX . 'auto_currencies_is_disabled';
		$section_title = __( 'Link currency to language', 'woocommerce-multicurrency' );

		$section_desc =
			__( 'To use this option, you need to install and activate one of the supported multilingual plugins:', 'woocommerce-multicurrency' ) .
			Multilingual::supported_plugins_as_ul();

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

	/**
	 * JS function to show/hide the credentials input fields.
	 *
	 * @since 1.20.0 Hide also the `rate update schedule` field.
	 */
	public function js_show_hide_credentials() {
		?>
		<script>
			(function ($) {
				var $dropdown = $("#<?php echo \esc_js( $this->dao->key_rates_provider_id() ); ?>");
				var $allInputs = $(".<?php echo \esc_js( self::CSS_CLASS_CREDENTIALS_INPUT ); ?>").closest("tr");
				var showOnlyOneInput = function () {
					var $inputForSelectedProvider = $("#<?php echo \esc_js( $this->dao->key_rates_provider_credentials( '' ) ); ?>" + $dropdown.val()).closest("tr");
					var $rowRatesUpdateSchedule = $("#<?php echo \esc_js( $this->dao->key_rates_update_schedule() ); ?>").closest("tr");
					$allInputs.hide();
					$inputForSelectedProvider.show();
					if ("<?php echo \esc_js( FixedRates::id() ); ?>" === $dropdown.val()) {
						$rowRatesUpdateSchedule.hide();
					} else {
						$rowRatesUpdateSchedule.show();
					}
				};

				showOnlyOneInput();
				$dropdown.on("change", showOnlyOneInput);
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * JS function to disable saving settings.
	 */
	public function js_disable_save() {
		?>
		<!--suppress JSDeprecatedSymbols -->
		<script>
			jQuery(function ($) {
				$("p.submit").hide();
				$("#mainform").submit(function (e) {
					e.preventDefault();
				});
			});
		</script>
		<?php

	}

	/**
	 * Display a rounding example.
	 */
	public function js_rounding_calculator() {
		?>
		<!--suppress JSCheckFunctionSignatures -->
		<script>
			jQuery(function ($) {
				var $round_to = $("#<?php echo esc_js( WP::OPTIONS_PREFIX ); ?>round_to");
				var $fee_percent = $("#<?php echo esc_js( WP::OPTIONS_PREFIX ); ?>fee_percent");
				var $price_charm = $("#<?php echo esc_js( WP::OPTIONS_PREFIX ); ?>price_charm");
				var $rate;

				$("#rate_example")
					.html('<input type="text" value="1.2345" style="width: 5em; margin-right: 1em"/>');

				$rate = $("#rate_example input");


				function calculate(price_in) {

					var round_to = parseFloat($round_to.val());
					var fee_percent = parseFloat($fee_percent.val());
					var price_charm = parseFloat($price_charm.val());
					var rate = parseFloat($rate.val());
					var price_out;

					// Sanitize
					if (isNaN(round_to) || round_to < 0.01) {
						round_to = 0.01;
					}
					if (isNaN(fee_percent) || fee_percent < 0.01) {
						fee_percent = 0;
					}
					if (isNaN(price_charm) || price_charm < 0.01) {
						price_charm = 0;
					}
					if (isNaN(rate) || rate < 0.0001) {
						rate = 1;
					}

					// Calculate
					price_out = price_in * rate;
					price_out = price_out * (1 + fee_percent / 100);
					if (round_to > 0.01) {
						price_out = Math.ceil(price_out / round_to) * round_to;
					}
					price_out = price_out - price_charm;

					return price_out;
				}

				function show() {
					// Display
					$("#rounding_calculator")
						.hide()
						.html(""
							+ 1 + ' &rarr; ' + calculate(1).toFixed(2) + '; '
							+ 5 + ' &rarr; ' + calculate(5).toFixed(2) + '; '
							+ 10 + ' &rarr; ' + calculate(10).toFixed(2) + '; '
							+ 50 + ' &rarr; ' + calculate(50).toFixed(2) + '; '
							+ 100 + ' &rarr; ' + calculate(100).toFixed(2) + '; '
							+ 500 + ' &rarr; ' + calculate(500).toFixed(2) + '; '
						)
						.fadeIn()
					;
				}

				show();

				$round_to.on("change", show);
				$round_to.on("keyup", show);
				$fee_percent.on("change", show);
				$fee_percent.on("keyup", show);
				$price_charm.on("change", show);
				$price_charm.on("keyup", show);
				$rate.on("change", show);
				$rate.on("keyup", show);

			});
		</script>
		<?php

	}

	/**
	 * Embed CSS.
	 *
	 * @since 1.15.0
	 */
	public function styles() {
		?>
		<!--suppress CssUnusedSymbol -->
		<style>
			@media screen and (min-width: 783px) {
				label[for^=woocommerce_multicurrency_fixed_rate_] {
					font-family: monospace;
				}

				div#woocommerce-multicurrency_fixed_rates-description + table th {
					width: 11rem;
					padding-right: 0;
				}

				div#woocommerce-multicurrency_fixed_rates-description + table p.description {
					display: inline-block;
					margin-left: 0.5em;
					font-style: normal;
					font-family: monospace;
					font-weight: 600;
				}
			}
		</style>
		<?php
	}
}
