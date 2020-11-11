/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

const RELOADED_NAME = "woocommerce-multicurrency-reloaded";

const set = () => {
	document.cookie = `${RELOADED_NAME}=1;samesite=strict`;
};

const unset = () => {
	document.cookie = `${RELOADED_NAME}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;samesite=strict`;
};

const isSet = () => {
	return document.cookie.split(";").some((item) => item.trim().startsWith(`${RELOADED_NAME}=`))
};

module.exports = {
	set,
	unset,
	isSet
};
