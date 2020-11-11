<?php
/**
 * Syntax sugar to avoid using the word "Exception" when it's just a message.
 *
 * @since 1.1.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Logger;

/**
 * Class Logger\Message
 */
class Message extends \Exception {

	/**
	 * Glue for converting array to string.
	 *
	 * @since 1.4.0
	 * @var string
	 */
	const GLUE = '|';

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 *
	 * @since 1.4.0 Allow message to be an array of strings.
	 *
	 * @param string|string[]       $message  [optional] The Exception message to throw.
	 * @param int                   $code     [optional] The Exception code.
	 * @param \Exception|\Throwable $previous [optional] The previous throwable used for the exception chaining.
	 *
	 * @link  https://php.net/manual/en/exception.construct.php
	 */
	public function __construct( $message = '', $code = 0, $previous = null ) {

		if ( is_array( $message ) ) {
			$message = implode( self::GLUE, $message );
		}

		parent::__construct( $message, $code, $previous );
	}
}
