<?php
/**
 * Embed PDF.
 *
 * @since   1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class PDF
 */
class PDF extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'pdf';

	/**
	 * My URL extensions.
	 *
	 * @var string
	 */
	const EXT = array( 'pdf' );

	/**
	 * Return type of the media.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @return string
	 */
	public function get_css() {
		return parent::get_css() . 'height:90vh';
	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html() {
		return '<iframe' . $this->make_tag_attribute( 'class', $this->get_css_class() ) . $this->make_tag_attribute( 'src', $this->get_sanitized_url() ) . '></iframe>' . $this->get_js_html();
	}
}
