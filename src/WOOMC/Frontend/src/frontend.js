/**
 * JS to handle the currency selector.
 *
 * @package WooCommerce-MultiCurrency
 *
 * @since 1.17.0
 *
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */
/*global woomc*/

const MODULE = "ROOT";

const Prerequisites = require("./Prerequisites");
const CurrencyCookie = require("./CurrencyCookie");
const Cache = require("./Cache");
const Console = require("./Console");

jQuery(function ($) {

	if (!Prerequisites.met()) {
		return;
	}

	/**
	 * @namespace woomc
	 * @property {string} currentURL
	 * @property {string} currency
	 * @property {Object} cookieSettings
	 * @property {string} cookieSettings.name
	 * @property {number} cookieSettings.expires
	 * @property {string} console_log
	 * @property {string} settings
	 * @property {string} settings.woocommerce_default_customer_address
	 */

	/**
	 * @namespace woomc_currency_selector
	 * @property {string} currencySelectorDOM
	 * @property {string} url.currencySelectorCSS
	 * @property {string} url.currencyFlagsCSS
	 */

	Console.debug(`[${MODULE}] Geolocation=${woomc.settings.woocommerce_default_customer_address}`);
	Console.debug(`[${MODULE}] Currency: on page=${woomc.currency}, in cookie=${CurrencyCookie.get()}`);

	/**
	 * Check if we need to reload the outdated cached page.
	 *
	 * @since 1.20.1
	 */
	Cache.bust();

	Console.debug(`[${MODULE}] The End.`);
});
