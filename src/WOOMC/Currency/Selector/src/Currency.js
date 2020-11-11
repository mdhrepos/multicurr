/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

const CurrencyCookie = require("./CurrencyCookie");
const Page = require("./Page");

/**
 * Change currency.
 *
 * @param {string} currency The currency code.
 */
const change = function (currency) {

	const TIMEOUT = 2000;
	const $body = jQuery(document.body);

	CurrencyCookie.set(currency);

	/**
	 * Trigger the "refresh fragments" AJAX to update the mini-cart widget and the top bar cart icon totals.
	 *
	 * Related: @see \WOOMC\MultiCurrency\App::action__wc_ajax_get_refreshed_fragments
	 *
	 * @since 1.9.0
	 */
	$body.trigger("wc_fragment_refresh");

	/**
	 * Reload page when fragments refreshed or after timeout.
	 * If reloaded immediately, fragments refresh is aborted.
	 */
	let reload_timeout = setTimeout(Page.reload, TIMEOUT);
	$body.on("wc_fragments_refreshed", function () {
		clearTimeout(reload_timeout);
		Page.reload();
	});
};

module.exports = {
	change
};
