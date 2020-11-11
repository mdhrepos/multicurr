<?php
/**
 * Abstract media class
 *
 * @since        1.0.0
 * Copyright (c) 2019, TIV.NET INC. All Rights Reserved.
 * @noinspection PhpUnused
 */

namespace TIVWP_140\Media;

/**
 * Class AbstractMedia
 */
abstract class AbstractMedia {

	/**
	 * Type of the media.
	 */
	const TYPE = '';

	/**
	 * My URL extensions.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	const EXT = array();

	/**
	 * Media ID.
	 * Lowercase alphanumeric characters, dashes and underscores are allowed.
	 *
	 * @var string
	 */
	protected $id = 'tivwp';

	/**
	 * Getter: ID.
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Media URL.
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Media constructor.
	 *
	 * @param string $url The URL.
	 * @param string $id  The media ID.
	 */
	public function __construct( $url, $id = '' ) {
		$this->setUrl( $url );

		$id = \sanitize_key( $id );
		if ( $id ) {
			$this->id = $id;
		}
	}

	/**
	 * Return type of the media.
	 *
	 * @return string
	 */
	public function get_type() {
		return static::TYPE;
	}

	/**
	 * Return sanitized URL.
	 *
	 * @return string
	 */
	public function get_sanitized_url() {
		return $this->url;
	}

	/**
	 * Default style for the embed HTML.
	 *
	 * @return string
	 */
	public function get_css() {
		return 'border:none;width:100%;';
	}

	/**
	 * Generate style HTML. Only if embed's ID is passed.
	 *
	 * @return string
	 */
	public function get_style_html() {
		$html = '';
		if ( $this->getId() ) {
			$html = '<style type="text/css">';

			$html .= '#' . $this->getId() . '{' . $this->get_css() . '}';
			$html .= '</style>';
		}

		return $html;
	}

	/**
	 * JS to accompany the embed HTML. Default is set styles.
	 *
	 * @since 1.1.1 Set display:block to gallery (some themes set it to inline-block, which breaks Vimeo).
	 * @since 1.1.1 Set ID to the STYLE element.
	 * @since 1.1.1 Place the STYLE element at the end of HEAD.
	 *
	 * @return string
	 */
	protected function get_js() {
		return '
(function () {
try{
elS=document.createElement("style");
elS.id="paywall-for-woocommerce-css";
elS.textContent=".' . \esc_attr( implode( '.', $this->get_css_classes() ) ) . '{' . $this->get_css() . '}";
elS.textContent+=".woocommerce .product .images figure.woocommerce-product-gallery__wrapper{display:block;}";
elH=document.querySelector("head");
if(elH){elH.appendChild(elS);}
}catch(e){console.error(e);}
}());
';
	}

	/**
	 * Generate JS HTML.
	 *
	 * @return string
	 */
	protected function get_js_html() {

		$js = $this->get_js();

		return $js ? '<script type="application/javascript">' . $js . '</script>' : '';

	}

	/**
	 * Generate embed HTML.
	 *
	 * @return string The HTML.
	 */
	abstract public function get_embed_html();

	/**
	 * Getter.
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Setter.
	 *
	 * @param string $url The URL.
	 */
	public function setUrl( $url ) {
		$this->url = $url;
	}

	/**
	 * Is this my type of the URL?
	 *
	 * @since 1.1.0 Return {@see is_my_extension} by default.
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_url( $url ) {
		return static::is_my_extension( $url );
	}

	/**
	 * Is this my file extension?
	 *
	 * @param string $url The URL.
	 *
	 * @return bool
	 */
	public static function is_my_extension( $url ) {
		$url_path = \wp_parse_url( $url, PHP_URL_PATH );
		$pathinfo = pathinfo( $url_path );

		return isset( $pathinfo['extension'] ) && in_array( strtolower( $pathinfo['extension'] ), static::EXT, true );
	}


	/**
	 * Generate attribute for HTML tag.
	 *
	 * @param string $name  Attribute name.
	 * @param string $value Attribute value.
	 *
	 * @return string
	 */
	public function make_tag_attribute( $name, $value ) {
		return $value ? ' ' . \sanitize_key( $name ) . '="' . \esc_attr( $value ) . '"' : '';
	}

	/**
	 * List of CSS classes.
	 *
	 * @return array
	 */
	protected function get_css_classes() {
		return array(
			$this->id . '-media',
			$this->id . '-media-type-' . $this->get_type(),
			'intrinsic-ignore',
		);
	}

	/**
	 * CSS class for the HTML tag.
	 *
	 * @return string
	 */
	protected function get_css_class() {
		return implode( ' ', $this->get_css_classes() );
	}

	/**
	 * CSS selector to use in JS.
	 *
	 * @return string
	 */
	protected function get_css_selector() {
		return '.' . implode( '.', $this->get_css_classes() );
	}
}
