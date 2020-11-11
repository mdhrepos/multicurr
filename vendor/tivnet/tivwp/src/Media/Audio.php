<?php
/**
 * URLs to audio files (mp3, etc.)
 *
 * @since   1.4.0
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Audio
 */
class Audio extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'audio';

	/**
	 * My URL extensions.
	 *
	 * @see wp_get_ext_types
	 * @see wp_get_audio_extensions
	 * @var string
	 */
	const EXT = array(
		'aac',
		'ac3',
		'aif',
		'aiff',
		'flac',
		'm3a',
		'm4a',
		'm4b',
		'mka',
		'mp1',
		'mp2',
		'mp3',
		'ogg',
		'oga',
		'ram',
		'wav',
		'wma',
	);

	/**
	 * Generate embed HTML.
	 *
	 * @since 1.1.1 Append the standard JS-generated styles.
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html() {

		$html = \wp_audio_shortcode( array( 'src' => $this->getUrl() ) );

		$html .= $this->get_js_html();

		return $html;
	}
}
