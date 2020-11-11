<?php
/**
 * Embed YouTube.
 *
 * @since   1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 */

namespace TIVWP_140\Media;

/**
 * Class YouTube
 */
class YouTube extends AbstractMedia {

	/**
	 * Type of the media.
	 *
	 * @var string
	 */
	const TYPE = 'youtube';

	/**
	 * YouTube short URL domain.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const SHORT_URL_DOMAIN = 'youtu.be';

	/**
	 * YouTube "no-cookie" domain used for embedding.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const EMBED_DOMAIN = 'www.youtube-nocookie.com';

	/**
	 * Is this a short YouTube URL?
	 *
	 * @since 1.1.0
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	protected static function is_short_url( $url ) {
		return false !== stripos( $url, self::SHORT_URL_DOMAIN );
	}

	/**
	 * Is this my type of the URL?
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_url( $url ) {
		return false !== stripos( $url, self::TYPE ) || self::is_short_url( $url );
	}

	/**
	 * Return type of the media.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Return sanitized URL.
	 *
	 * @since 1.1.0 Handle YouTube short URLs.
	 * @return string
	 */
	public function get_sanitized_url() {

		$url = $this->getUrl();

		if ( self::is_short_url( $url ) ) {
			// Search: https://youtu.be/z6e_W6L6sTg
			// Replace: https://www.youtube-nocookie.com/embed/z6e_W6L6sTg
			$url = str_ireplace( self::SHORT_URL_DOMAIN, self::EMBED_DOMAIN . '/embed', $url );
		} else {
			// Make sure youtube URL is "no cookie embed".
			$url = preg_replace( '^https?://(www\.)?youtube\.com^i', 'https://' . self::EMBED_DOMAIN, $url );
			$url = preg_replace( '^(youtube.+)watch\?v=^i', '\1embed/', $url );
		}

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
				 $this->make_tag_attribute( 'allow', 'autoplay; fullscreen; accelerometer; encrypted-media; gyroscope; picture-in-picture' ) .
				 $this->make_tag_attribute( 'allowfullscreen', 'true' ) .
				 '></iframe>';
		$html .= '</div>';

		$html .= $this->get_js_html();

		return $html;
	}

}
