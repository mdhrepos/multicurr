<?php
/**
 * Generic oEmbed.
 *
 * @since 1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Generic
 */
class Generic extends AbstractMedia {

	/**
	 * Type of the media.
	 */
	const TYPE = 'generic';

	/**
	 * Generate style HTML.
	 *
	 * @return string
	 */
	public function get_style_html() {
		return '<style type="text/css">.woocommerce-product-gallery__wrapper > * {width:100%!important;height:auto!important;}</style>';
	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 *
	 * List of providers {@see \WP_oEmbed::__construct}
	 */
	public function get_embed_html() {

		// Default is show URL.
		$html = '<a' . $this->make_tag_attribute( 'href', $this->getUrl() ) . '>' . \esc_html( $this->getUrl() ) . '</a>';

		$try_html = \wp_oembed_get( $this->getUrl() );
		if ( $try_html ) {
			$html = $this->get_style_html() . $try_html;
		}

		return $html;
	}
}
