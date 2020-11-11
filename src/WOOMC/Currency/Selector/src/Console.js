/**
 * Browser console API.
 * @since 2.6.3
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

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

const LABEL = "WooCommerce-Multicurrency-Selector";

/**
 * To activate console messages, need to set a cookie.
 * @returns {boolean}
 */
const isActive = () => "object" === typeof woomc && "Y" === woomc.console_log;

/**
 * Format the message.
 * @param {string} msg Raw message.
 * @returns {string}
 */
const formatMessage = (msg) => `[${LABEL}] ${msg}`;

/**
 * Format the data object attached to the message.
 * @param {string} level Severity.
 * @param {string} msg Raw message.
 * @returns {{msg: string, trace: string[], level: string, url: string, timestamp: string}}
 */
const formatData = (level, msg) => {
	let theTrace = new Error().stack.split("\n");
	theTrace.shift();

	return {
		"level": level,
		// "msg": msg,
		"timestamp": new Date().toLocaleString(),
		"url": window.location.href,
		"trace": theTrace
	}
};

/**
 * Print the console message.
 * @param {string} msg Raw message.
 * @param {string} level Severity.
 */
const printMessage = (msg, level) => {
	if (!isActive()) {
		return;
	}

	switch (level) {
		case "DEBUG":
			console.debug(formatMessage(msg), formatData(level, msg));
			break;
		case "ERROR":
			console.error(formatMessage(msg), formatData(level, msg));
			break;
		case "INFO":
			console.info(formatMessage(msg), formatData(level, msg));
			break;
		case "LOG":
			console.log(formatMessage(msg), formatData(level, msg));
			break;
		case "WARN":
			console.warn(formatMessage(msg), formatData(level, msg));
			break;
		default:
		// Do nothing.
	}
};

/**
 * Display console message.
 * @param msg The message text.
 */
const debug = (msg) => printMessage(msg, "DEBUG");
const error = (msg) => printMessage(msg, "ERROR");
const info = (msg) => printMessage(msg, "INFO");
const log = (msg) => printMessage(msg, "LOG");
const warn = (msg) => printMessage(msg, "WARN");

module.exports = {
	debug,
	error,
	info,
	log,
	warn
};
