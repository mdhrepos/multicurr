<?php
/**
 * Image media.
 *
 * @since   1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Image
 */
class Image extends AbstractMedia {

	/**
	 * Type of the media.
	 */
	const TYPE = 'image';

	/**
	 * Is this my type of the URL?
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_url( $url ) {

		/**
		 * Image types.
		 *
		 * @link https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types
		 */
		$image_exts = array(
			'apng',
			'bmp',
			'gif',
			'jpg',
			'jpe',
			'jpeg',
			'jif',
			'jfif',
			'pjpeg',
			'pjp',
			'png',
			'svg',
			'webp',
		);
		$url_path   = \wp_parse_url( $url, PHP_URL_PATH );
		$pathinfo   = pathinfo( $url_path );

		return isset( $pathinfo['extension'] ) && in_array( $pathinfo['extension'], $image_exts, true );
	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html() {

		return '<a' . $this->make_tag_attribute( 'href', $this->getUrl() ) . $this->make_tag_attribute( 'target', '_' ) . '><img alt="" ' . $this->make_tag_attribute( 'id', $this->getId() ) . $this->make_tag_attribute( 'src', $this->getUrl() ) . $this->make_tag_attribute( 'class', 'wp-post-image' ) . '/></a>';
	}
}
