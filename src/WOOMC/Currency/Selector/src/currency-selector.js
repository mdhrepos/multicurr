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

import "./currency-selector.scss";

const Console = require("./Console");
const Prerequisites = require("./Prerequisites");
const Currency = require("./Currency");
const Flags = require("./flags/index");

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

	/**
	 * Hook the standard <select> to change currency.
	 */
	$(woomc_currency_selector.currencySelectorDOM).on("change", function () {
		Currency.change(this.value);
	});

	/**
	 * Run the "flags" if there are shortcodes with data-flag set.
	 * @since 2.5.2
	 */
	if ($(woomc_currency_selector.currencySelectorDOM + "[data-flag=1]").length) {
		Flags.go($);
	}

	Console.debug("The End.");
});
