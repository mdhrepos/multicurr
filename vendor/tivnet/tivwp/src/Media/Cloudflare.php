<?php
/**
 * Embed Cloudflare streams.
 *
 * @since   1.4.0
 * @link    https://developers.cloudflare.com/stream/player-and-playback/player-embed
 * Copyright (c) 2020, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class Cloudflare
 */
class Cloudflare extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'cloudflare';

	/**
	 * Is this my type of the URL?
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_url( $url ) {
		return false !== stripos( $url, '.videodelivery.net' );
	}

	/**
	 * Return sanitized URL.
	 *
	 * @return string
	 */
	public function get_sanitized_url() {

		$url = $this->getUrl();

		/**
		 * Replace https://watch.videodelivery.net/...
		 * with https://iframe.videodelivery.net/...
		 */
		$url = str_ireplace( 'watch.', 'iframe.', $url );

		// https://developers.cloudflare.com/stream/player-and-playback/player-embed#basic-options
		$params = array(
			'autoplay' => '0',
			'controls' => '1',
			'loop'     => '0',
			'muted'    => '0',
			'preload'  => 'none',
		);

		/**
		 * Filter to adjust the Cloudflare URL parameters.
		 *
		 * @param string[] params The parameters.
		 */
		$params = \apply_filters( 'tivwp_cloudflare_url_parameters', $params );

		// Remove empty parameters. Cloudflare only checks for the parameter presence, not its value.
		$params = array_filter( $params );

		$url = \add_query_arg( $params, $url );

		return $url;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @return string
	 */
	public function get_css() {
		return parent::get_css() . 'height:100%;position:absolute;top:0;left:0;';
	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 */
	public function get_embed_html() {

		$html = '<div style="padding:56.25% 0 0 0;position:relative;">';

		$html .= '<iframe' .
				 $this->make_tag_attribute( 'class', $this->get_css_class() ) .
				 $this->make_tag_attribute( 'src', $this->get_sanitized_url() ) .
				 $this->make_tag_attribute( 'allow', 'accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;' ) .
				 $this->make_tag_attribute( 'allowfullscreen', 'true' ) .
				 '></iframe>';
		$html .= '</div>';

		$html .= $this->get_js_html();

		return $html;
	}
}
