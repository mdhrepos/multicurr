<?php
/**
 * Migration.
 *
 * @package WOOMC\Settings
 *
 * @since   1.2.2
 */

namespace WOOMC\Settings;

use WOOMC\DAO\Factory;
use WOOMC\DAO\IDAO;
use WOOMC\DAO\WP;

/**
 * Class Migration
 *
 * @since   1.2.2
 */
class Migration {

	/**
	 * The DAO instance.
	 *
	 * @var IDAO
	 */
	protected $dao;


	/**
	 * Migration constructor.
	 *
	 * @since 1.2.2
	 */
	public function __construct() {
		$this->dao = Factory::getDao();
	}

	/**
	 * Do the migration if necessary.
	 *
	 * @since 1.2.2
	 */
	public function maybe_migrate() {

		// If version exists in options and >= current version then stop processing.
		$version_in_db = $this->dao->getVersionInDB();
		if ( version_compare( $version_in_db, WOOCOMMERCE_MULTICURRENCY_VERSION, '>=' ) ) {
			return;
		}

		// The very first time (no version) - see if need to migrate from WPGlobus.
		if ( ! $version_in_db ) {
			$this->migrate_wpglobus_options();
		}

		// Store the current version.
		$this->dao->setVersionInDB( WOOCOMMERCE_MULTICURRENCY_VERSION );
	}

	/**
	 * Migrate from the WPGlobus version.
	 *
	 * @since 1.2.2
	 */
	protected function migrate_wpglobus_options() {

		/**
		 * Option prefix used in the WPGlobus version.
		 *
		 * @var string $prefix_wpglobus
		 */
		static $prefix_wpglobus = 'wpglobus_mc_';

		/**
		 * Keys to import - the main list.
		 *
		 * @var string[] $keys_to_import
		 */
		$keys_to_import = array(
			'fee_percent',
			'price_charm',
			'round_to',
			'rates_credentials_Currencylayer',
			'rates_credentials_OpenExchangeRates',
			'rates_provider_id',
			'rates',
			'rates_timestamp',
		);

		// Import the list of enabled currencies.
		$this->import_option(
			$prefix_wpglobus . 'enabled_currencies',
			WP::OPTIONS_PREFIX . 'enabled_currencies'
		);
		// If there was a list, add the currency-specific keys to the import.
		$enabled_currencies = $this->dao->retrieve( WP::OPTIONS_PREFIX . 'enabled_currencies', null );
		if ( null !== $enabled_currencies ) {
			foreach ( $enabled_currencies as $currency ) {
				$keys_to_import[] = 'currency_symbol_' . $currency;
				$keys_to_import[] = 'price_format_' . $currency;
			}
		}

		// Process with the import of all keys.
		foreach ( $keys_to_import as $key ) {
			$this->import_option(
				$prefix_wpglobus . $key,
				WP::OPTIONS_PREFIX . $key
			);
		}
	}

	/**
	 * Copy and "Save As..." an option.
	 *
	 * @param string $key_from The key to import from.
	 * @param string $key_to   The key to Save As.
	 * @param bool   $force    True = Import even if already exists.
	 *
	 * @since 1.2.2
	 */
	protected function import_option( $key_from, $key_to, $force = false ) {
		$current_value = $this->dao->retrieve( $key_to, null );

		// Continue only if the current value does not exist yet, or if forced.
		if ( null === $current_value || $force ) {
			$value_to_import = $this->dao->retrieve( $key_from, null );
			if ( null !== $value_to_import ) {
				$this->dao->store( $key_to, $value_to_import );
			}
		}
	}
}
