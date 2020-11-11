/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * Force reloading page from the server.
 *
 * @since 1.20.1
 * @since 1.20.2 Set also a special cookie because not all themes have the body class filter.
 */

const reload = () => {

	require("./Reloaded").set();

	jQuery("<form>",
		{
			"method": "post",
			"action": woomc.currentURL
		}
	)
		.appendTo(document.body)
		.submit()
	;
};

module.exports = {
	reload
};
