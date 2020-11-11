/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * @namespace woomc
 */

const Console = require("./Console");

/**
 * Check if prerequisites met.
 * @returns {boolean}
 */
const met = () => {

	if ("undefined" === typeof woomc) {
		Console.error("Internal Error: Undefined 'woomc'.");
		// disableCurrencySelectors();
		return false;
	}

	// Browser with cookies disabled.
	if (!navigator.cookieEnabled) {
		Console.error("Cookies disabled in the browser. Currency selector will not work.");
		// disableCurrencySelectors();
		return false;
	}

	Console.debug("Frontend prerequisites met.");
	return true;

};

// const disableCurrencySelectors = () => {
// 	document
// 		.querySelectorAll(".woocommerce-currency-selector")
// 		.forEach(selector => {
// 			selector.disabled = true;
// 			selector.style.opacity = ".2";
// 		})
// 	;
// }

module.exports = {
	met
};
