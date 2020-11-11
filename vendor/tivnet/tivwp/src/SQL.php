<?php
/**
 * SQL methods.
 *
 * @since 1.0.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140;

class SQL {

	/**
	 * Builds the SQL IN(...) statement.
	 *
	 * @param mixed|array $items  List of items.
	 * @param string      $format printf format; default is '%s'.
	 *
	 * @return string
	 */
	public static function in( $items, $format = '%s' ) {
		/**
		 * WPDB.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$sql = '';

		$items = (array) $items;
		if ( count( $items ) ) {
			$template = implode( ', ', array_fill( 0, count( $items ), $format ) );
			$sql      = $wpdb->prepare( $template, $items ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $sql;
	}

}
