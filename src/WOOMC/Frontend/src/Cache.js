/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

const Console = require("./Console");

/**
 * If the body class' currency does not match the currency cookie,
 * we assume that the page is cached. We'll try to reload it.
 *
 * @since 1.20.1
 * @since 1.20.2 Check for a special cookie because not all themes have the body class filter.
 * @since 2.0.0 Do not use body class for checking. Use the woomc.currency var set by backend.
 * @since 2.6.3 Ignore browsers with cookies disabled.
 */
const bust = () => {

	// Browser with cookies disabled. Cannot help.
	if (!navigator.cookieEnabled) {
		Console.error("Cookies disabled in the browser. Cache buster will not work.");
		return;
	}

	// Currency is set via URL. No additional processing is needed.
	if (window.location.search.indexOf("currency=") > 0) {
		Console.debug("Currency is set via URL. No additional processing is needed.");
		return;
	}

	/**
	 * The `reloaded` cookie tells that the page was just reloaded.
	 * To avoid endless loop, we check for it and do not reload.
	 */
	const Reloaded = require("./Reloaded");
	if (Reloaded.isSet()) {
		Reloaded.unset();
		// Also, we try a trick to make the browser forgetting that we reloaded using a POST.
		// Otherwise, if people try F5, browser prompts for form re-submission.
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
		return;
	}

	const CurrencyCookie = require("./CurrencyCookie");
	if (CurrencyCookie.notAsCached()) {
		const Page = require("./Page");
		Console.debug("Reloading page...");
		Page.reload();
	}
};

module.exports = {
	bust
};
