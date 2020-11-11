/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

const Currency = require("../Currency");

/**
 * Convert each SELECT with data-flag=1 to "flags" menu.
 * Hook each menu option to the change currency function.
 *
 * @param {jQuery} $
 */
const go = ($) => {

	$(woomc_currency_selector.currencySelectorDOM).each(function () {
		if ($(this).data("flag")) {
			$(this).iconSelectMenu(
				{
					change: function (event, data) {
						Currency.change(data.item.value);
					}
				}
			);
		}
	});
}

module.exports = {
	go
};
