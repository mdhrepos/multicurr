<?php
/**
 * Media Factory.
 *
 * @since        1.0.0
 * @noinspection PhpUnused
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Factory
 */
class Factory {

	/**
	 * Get a media object.
	 *
	 * @param string $url The URL.
	 * @param string $id  The media ID.
	 *
	 * @return AbstractMedia
	 */
	public static function get_media( $url, $id = '' ) {

		if ( YouTube::is_my_url( $url ) ) {
			return new YouTube( $url, $id );
		}

		if ( Vimeo::is_my_url( $url ) ) {
			return new Vimeo( $url, $id );
		}

		if ( Cloudflare::is_my_url( $url ) ) {
			return new Cloudflare( $url, $id );
		}

		if ( Video::is_my_url( $url ) ) {
			return new Video( $url, $id );
		}

		if ( Audio::is_my_url( $url ) ) {
			return new Audio( $url, $id );
		}

		if ( Image::is_my_url( $url ) ) {
			return new Image( $url, $id );
		}

		if ( PDF::is_my_url( $url ) ) {
			return new PDF( $url, $id );
		}

		return new Generic( $url, $id );
	}

}
