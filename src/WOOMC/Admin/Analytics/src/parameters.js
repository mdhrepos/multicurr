/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */
/*global wcSettings*/

const getLabel = () => {
	/** @namespace wcSettings.WooMC.i18n.Currency */
	return wcSettings.WooMC.i18n.Currency;
};

const getStoreCurrency = () => {
	return wcSettings.currency.code;
};

const getCurrencies = () => {
	/** @namespace wcSettings.WooMC.currencies */
	return wcSettings.WooMC.currencies;
};

/**
 * List of wc-analytics pages where currency is used.
 *
 * @return {string[]} The list of pages.
 */
const getPages = () => {
	return [
		"orders",
		"revenue",
		"products",
		"categories",
		"coupons",
		"taxes",
	];
};

module.exports = {
	getLabel,
	getCurrencies,
	getPages,
	getStoreCurrency
};

