<?php
/**
 * Third-party script integrations.
 *
 * Kept here rather than pasted into a header template or an Elementor
 * "custom code" box, so the tags live in git and survive a rebuild (R12).
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Web Assistant widget configuration.
 *
 * NOTE ON THE ENDPOINT: this currently points at the **QA** environment
 * (`aiwebassist-qa`). That is correct for local development and wrong for
 * production. Both values are filterable so the swap is one line in a
 * host-specific mu-plugin rather than an edit to this file.
 *
 * NOTE ON THE KEY: the api key is prefixed `publishable_`, i.e. designed
 * for client-side exposure — it appears in the page source of every visit
 * regardless. Committing it is therefore fine. A secret key never would be.
 *
 * @return array
 */
function glm_web_assistant_config() {

	$config = array(
		'src'          => 'https://aiwebassist-qa.hazentech.com/api/widget/widget.js',
		'data-bot-id'  => 'c9cd5e9a-0044-463d-b629-bce0fdf3dc57',
		'data-api-key' => 'publishable_13bd7d3f8239943f2f908e000a440c4da9505545db616f7a9cd760a60ba38cfd',
		'data-api-url' => 'https://aiwebassist-qa.hazentech.com/api',
		'data-color'   => '#0a348f',
	);

	/**
	 * Filter the web assistant configuration.
	 *
	 * Use this to point at production without editing the theme:
	 *
	 *     add_filter( 'glm_web_assistant_config', function ( $c ) {
	 *         $c['src']            = 'https://aiwebassist.hazentech.com/api/widget/widget.js';
	 *         $c['data-api-url']   = 'https://aiwebassist.hazentech.com/api';
	 *         return $c;
	 *     } );
	 *
	 * Return an empty array to disable the widget entirely.
	 *
	 * @param array $config Script attributes.
	 */
	return (array) apply_filters( 'glm_web_assistant_config', $config );
}

/**
 * Print the widget script in <head>.
 *
 * Uses wp_print_script_tag() rather than a hardcoded <script> string so
 * WordPress escapes the attributes and other plugins can filter the tag.
 */
function glm_print_web_assistant() {

	// Never in the admin, the block editor iframe, or a feed.
	if ( is_admin() || is_feed() ) {
		return;
	}

	$config = glm_web_assistant_config();

	if ( empty( $config['src'] ) ) {
		return;
	}

	wp_print_script_tag( $config );
}
add_action( 'wp_head', 'glm_print_web_assistant' );
