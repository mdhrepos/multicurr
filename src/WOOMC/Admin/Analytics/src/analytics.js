/*
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

import {addFilter} from '@wordpress/hooks';
import * as P from "./parameters";

const namespace = "tivnet/woocommerce-multicurrency/analytics";

const addCurrencyFilters = (filters) => {

	return [
		{
			label: P.getLabel(),
			staticParams: [],
			param: 'currency',
			showFilters: () => true,
			defaultValue: P.getStoreCurrency(),
			filters: [...(P.getCurrencies() || [])],
		},
		...filters,
	];
};

P.getPages().forEach(page => {

	/**
	 * Adds the hook to the appropriate hooks container.
	 *
	 * @param {string}   hookName  Name of hook to add
	 * @param {string}   namespace The unique namespace identifying the callback in the form `vendor/plugin/function`.
	 * @param {Function} callback  Function to call when the hook is run
	 * @param {?number}  priority  Priority of this hook (default=10)
	 */
	addFilter(
		`woocommerce_admin_${page}_report_filters`,
		namespace,
		addCurrencyFilters
	);

});

/**
 * Add a column to a report table. Include a header and
 * manipulate each row to handle the added parameter.
 *
 * @param {Object} reportTableData - table data.
 * @return {Object} - table data.
 */
const addTableColumn = (reportTableData) => {

	if (
		!P.getPages().includes(reportTableData.endpoint) ||
		!reportTableData.items ||
		!reportTableData.items.data ||
		!reportTableData.items.data.length
	) {
		return reportTableData;
	}

	const newHeaders = [
		...reportTableData.headers,
		{
			label: P.getLabel(),
			key: 'currency',
		},
	];
	const newRows = reportTableData.rows.map((row, index) => {
		const item = reportTableData.items.data[index];
		return [
			...row,
			{
				display: item.currency,
				value: item.currency,
			},
		];
	});

	reportTableData.headers = newHeaders;
	reportTableData.rows = newRows;

	return reportTableData;
};

addFilter(
	'woocommerce_admin_report_table',
	namespace,
	addTableColumn
);

/**
 * Add 'currency' to the list of persisted queries so that the parameter remains
 * when navigating from report to report.
 *
 * @param {Array} params - array of report slugs.
 * @return {Array} - array of report slugs including 'currency'.
 */
const persistQueries = (params) => {
	params.push('currency');
	return params;
};

addFilter(
	'woocommerce_admin_persisted_queries',
	namespace,
	persistQueries
);

/**
 * Change the price display format.
 *
 * @param {Object} config Currency configuration.
 * @param {String} currency Currency code in the query.
 * @returns {Object} Modified configuration.
 */
const changePriceDisplayFormat = (config, {currency}) => {

	if (currency) {
		// Currency is in the query, eq ?currency=JPY.
		config['code'] = currency;
		config['symbol'] = currency;
	}

	config['priceFormat'] = '%2$s';

	return config;
};

addFilter(
	'woocommerce_admin_report_currency',
	namespace,
	changePriceDisplayFormat
);
