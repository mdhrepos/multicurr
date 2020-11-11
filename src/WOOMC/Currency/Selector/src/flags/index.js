/*
 * Widget with flags
 *
 * @since 1.17.0
 * @link  https://jqueryui.com/selectmenu/#product-selection
 *
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * Controller.
 *
 * @param {jQuery} $
 */
const go = ($) => {

	/**
	 * @since 2.0.0 Prevent Type Error when jquery-ui is not loaded for any reason.
	 */
	if ("function" !== typeof $.widget || "undefined" === typeof $.ui) {
		return;
	}

	require("./Setup").go($);
	require("./Hook").go($);
	require("./Style").go($);
};

module.exports = {
	go
};
