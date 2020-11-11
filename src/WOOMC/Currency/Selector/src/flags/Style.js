/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * @namespace woomc_currency_selector
 * @property {string} currencySelectorDOM
 * @property {string} url.currencySelectorCSS
 * @property {string} url.currencyFlagsCSS
 */

/**
 * Copy some CSS styles from standard SELECT to the flags dropdown.
 * @since 2.5.2
 * @param {jQuery} $
 */
const go = ($) => {
	var $stdSelect = $(woomc_currency_selector.currencySelectorDOM);
	var css = ".woocommerce-currency-selector-wrap .ui-widget {";

	// For Chrome, those are enough.
	var props = [
		"background",
		"border",
		"border-radius",
		"color",
		"padding",
	];

	// Firefox and Edge do not return shorthand properties.
	if (/firefox|edge/.test(navigator.userAgent.toLowerCase())) {
		props = props.concat([
			"background-color",
			"border-top-color",
			"border-top-style",
			"border-top-width",
			"border-right-color",
			"border-right-style",
			"border-right-width",
			"border-bottom-color",
			"border-bottom-style",
			"border-bottom-width",
			"border-left-color",
			"border-left-style",
			"border-left-width",
			"padding-top",
			"padding-right",
			"padding-bottom",
			// No padding-left - see 2px below.
		]);
	}

	props.forEach(function (prop) {
		var prop_css = $stdSelect.css(prop);
		if (prop_css) {
			css += prop + ":" + prop_css + ";";
		}
	})
	css += "padding-left: 2px;";
	css += "}";

	/**
	 * CSS links to static jQuery-UI select menu styling and flags.
	 */
	var $link_selector = $("<link>", {
		id: "woocommerce-currency-selector-css",
		rel: "stylesheet",
		type: "text/css",
		href: woomc_currency_selector.url.currencySelectorCSS
	});
	var $link_flags = $("<link>", {
		id: "woocommerce-currency-flags-css",
		rel: "stylesheet",
		type: "text/css",
		href: woomc_currency_selector.url.currencyFlagsCSS
	});

	/**
	 * STYLE tag with rules copied from the theme.
	 */
	var $style_theme = $("<style>", {
		id: "woocommerce-currency-selector-theme-css",
		type: "text/css"
	})
		.text(css);

	var toAdd = [$link_flags, $link_selector, $style_theme];

	/**
	 * Add to the HEAD, right after the TITLE.
	 * Fallback (no title?) is the first line in the HEAD.
	 */
	var $title = $("head title");

	if ($title.length) {
		$title.first().after(toAdd);
	} else {
		// noinspection JSCheckFunctionSignatures
		$(document.head).prepend(toAdd);
	}

};

module.exports = {
	go
};
