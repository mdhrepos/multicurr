/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * @namespace woomc
 * @property {Object} cookieSettings
 * @property {string} cookieSettings.name
 */

const MODULE = "CurrencyCookie";

const Console = require("./Console");
const Cookie = require("./Cookie");

/**
 * Save currency in cookie.
 *
 * @param {string} currency The currency code.
 */
const set = (currency) => {
	document.cookie = `${woomc.cookieSettings.name}=${currency};path=/;max-age=${woomc.cookieSettings.expires};samesite=strict`;
};

const get = () => Cookie.get(woomc.cookieSettings.name);

const notAsCached = () => {

	const activeCookie = get();

	Console.debug(`[${MODULE}] Cookies: active=${activeCookie}; woomc.currency=${woomc.currency}`);

	if (activeCookie !== woomc.currency) {
		Console.debug("activeCookie !== woomc.currency");
		return true;
	}

	return false;
}


module.exports = {
	get,
	set,
	notAsCached
};
