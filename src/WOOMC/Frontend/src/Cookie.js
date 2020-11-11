/**
 * Cookie handling.
 * @since 2.6.3
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

/**
 * Get a cookie value.
 * @param {string} name Name of the cookie.
 * @returns {string} The value or empty string if not found.
 */
const get = (name) => {
	const theCookie = document.cookie
		.split('; ')
		.find(row => row.startsWith(`${name}=`));

	return theCookie ? theCookie.split('=')[1] : "";
};

module.exports = {
	get
};
