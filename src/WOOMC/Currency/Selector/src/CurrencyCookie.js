/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * @namespace woomc
 * @property {Object} cookieSettings
 * @property {string} cookieSettings.name
 */

/**
 * Save currency in cookie.
 *
 * @param {string} currency The currency code.
 */
const set = (currency) => {
	document.cookie = `${woomc.cookieSettings.name}=${currency};path=/;max-age=${woomc.cookieSettings.expires};samesite=strict`;
};

const exists = () => document.cookie.split(';').some((item) => item.trim().startsWith(woomc.cookieSettings.name));

const get = () => {
	if (!exists()) {
		return "";
	}
	return document.cookie
		.split('; ')
		.find(row => row.startsWith(woomc.cookieSettings.name))
		.split('=')[1];
};

const equals = (currency) => {
	return document.cookie.split(";").some((item) => item.includes(`${woomc.cookieSettings.name}=${currency}`));
};

const notAsCached = () => {
	return !equals(woomc.currency);
};


module.exports = {
	get,
	set,
	equals,
	notAsCached
};
