<?php
/**
 * DAO Factory.
 *
 * @since 1.0.0
 */

namespace WOOMC\DAO;

/**
 * Class Factory
 */
class Factory {

	/**
	 * The DAO instance.
	 *
	 * @var IDAO
	 */
	protected static $dao;

	/**
	 * Get the DAO instance.
	 *
	 * @return IDAO
	 */
	public static function getDao() {
		return self::$dao ? self::$dao : self::$dao = new WP();
	}
}
