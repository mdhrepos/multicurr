<?php
/**
 * Multilingual plugins integration.
 *
 * @since 2.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace WOOMC\Integration;

class Multilingual {

	/**
	 * Supported plugins.
	 *
	 * @var array
	 */
	protected static $supported_plugins = array(
		'WPGlobus' => array(
			'url' => 'https://wordpress.org/plugins/wpglobus/',
		),
		'Polylang' => array(
			'url' => 'https://wordpress.org/plugins/polylang/',
		)
	);

	/**
	 * Getter for Supported plugins.
	 *
	 * @return array
	 */
	public static function getSupportedPlugins() {
		return self::$supported_plugins;
	}

	/**
	 * Return the list of supported plugins in the "<ul>" HTML format.
	 *
	 * @return string
	 */
	public static function supported_plugins_as_ul() {

		$supported = array();

		foreach ( \wp_list_pluck( self::getSupportedPlugins(), 'url' ) as $name => $url ) {
			$supported[] = '- <a href="' . $url . '">' . $name . '</a>';
		}

		return '<ul><li>' . implode( '</li><li>', $supported ) . '</li></ul>';

	}

}
