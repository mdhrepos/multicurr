<?php
/**
 * URLs to video files (mp4, etc.)
 *
 * @since 1.1.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Video
 */
class Video extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'video';

	/**
	 * My URL extensions.
	 *
	 * @var string
	 */
	const EXT = array(
		'flv',
		'm4v',
		'mov',
		'mp4',
		'mpeg',
		'oga',
		'ogg',
		'ogv',
		'ogv',
		'webm',
		'webma',
		'webmv',
	);

	/**
	 * Generate style HTML.
	 *
	 * @return string
	 * public function NO_get_style_html() {
	 * return '<style type="text/css">.woocommerce-product-gallery__wrapper > * {width:100%!important;height:auto!important;}</style>';
	 * }
	 */

	/**
	 * Generate embed HTML.
	 *
	 * @since 1.1.1 Append the standard JS-generated styles.
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html() {

		$html = \wp_video_shortcode( array( 'src' => $this->getUrl() ) );

		$html .= $this->get_js_html();

		return $html;
	}
}
