<?php
/**
 * Logging to WooCommerce Status->Logs
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Logger;

/**
 * WC_Logger wrapper.
 */
class Log {

	/**
	 * Default log level.
	 *
	 * @var string
	 */
	const LOG_LEVEL_NONE = '';

	/**
	 * Adding tracing to the Debug log level.
	 *
	 * @var string
	 */
	const LOG_LEVEL_TRACE = 'trace';

	/**
	 * Is tracing needed?
	 *
	 * @var bool
	 */
	protected static $need_tracing = false;

	/**
	 * Log source.
	 *
	 * @return string
	 */
	protected static function source() {
		return self::LOG_LEVEL_NONE;
	}

	/**
	 * Log level defined in the application settings.
	 *
	 * @return string
	 */
	protected static function threshold() {
		return \WC_Log_Levels::DEBUG;
	}

	/**
	 * Add a log entry by calling @see \WC_Logger::log().
	 *
	 * @param string                  $level      One of the following:
	 *                                            'emergency': System is unusable.
	 *                                            'alert': Action must be taken immediately.
	 *                                            'critical': Critical conditions.
	 *                                            'error': Error conditions.
	 *                                            'warning': Warning conditions.
	 *                                            'notice': Normal but significant condition.
	 *                                            'info': Informational messages.
	 *                                            'debug': Debug-level messages.
	 * @param string|string[]|Message $message    Message to log.
	 */
	public static function log( $level, $message ) {

		if ( ! self::should_handle( $level ) ) {
			return;
		}

		$context = array( 'source' => static::source() );

		$logger = \wc_get_logger();
		if ( is_array( $message ) ) {
			$message = implode( '|', $message );
		} elseif ( $message instanceof Message ) {
			// Get the last two tokens of the file path.
			$file    = \wp_normalize_path( $message->getFile() );
			$file    = basename( dirname( $file ) ) . '/' . basename( $file );
			$trace   = self::$need_tracing ? "\n" . $message->getTraceAsString() : '';
			$message = implode(
				'|',
				array(
					$message->getMessage(),
					$file . ':' . $message->getLine(),
					$trace,
				)
			);
		}
		$logger->log( $level, $message, $context );
	}

	/**
	 * Check if we should handle messages of this level.
	 *
	 * @param string $level The log level.
	 *
	 * @return bool
	 */
	protected static function should_handle( $level ) {

		if ( defined( 'DOING_PHPUNIT' ) ) {
			return false;
		}

		// Log level from the settings.
		$option_log_level = static::threshold();

		if ( self::LOG_LEVEL_NONE === $option_log_level ) {
			// "No log" asked in settings (also the default).
			return false;
		}

		/**
		 * Trace level is same as debug but we set the "need tracing" flag.
		 */
		if ( self::LOG_LEVEL_TRACE === $option_log_level ) {
			$option_log_level   = \WC_Log_Levels::DEBUG;
			self::$need_tracing = true;
		}

		if ( \WC_Log_Levels::DEBUG === $option_log_level ) {
			// "Debug" asked in settings..log everything.
			return true;
		}

		// Write messages with severity higher or equal to the asked in settings.
		$level_severity            = \WC_Log_Levels::get_level_severity( $level );
		$option_log_level_severity = \WC_Log_Levels::get_level_severity( $option_log_level );

		return $level_severity >= $option_log_level_severity;
	}

	/**
	 * Adds an error level message.
	 *
	 * @param string|string[]|Message $message Message to log.
	 */
	public static function error( $message ) {
		self::log( \WC_Log_Levels::ERROR, $message );
	}

	/**
	 * Adds an info level message.
	 *
	 * @param string|string[]|Message $message Message to log.
	 */
	public static function info( $message ) {
		self::log( \WC_Log_Levels::INFO, $message );
	}

	/**
	 * Adds a debug level message.
	 *
	 * @param string|string[]|Message $message Message to log.
	 */
	public static function debug( $message ) {
		self::log( \WC_Log_Levels::DEBUG, $message );
	}
}
