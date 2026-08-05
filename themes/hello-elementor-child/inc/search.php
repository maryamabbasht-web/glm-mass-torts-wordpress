<?php
/**
 * Header search.
 *
 * Uses a native <details>/<summary> disclosure rather than a custom
 * toggle. <summary> already has button semantics, is keyboard focusable,
 * responds to Enter and Space, and manages its own expanded state — so
 * this needs no JavaScript and no ARIA of our own to get right.
 *
 * Posts to WordPress core search. No plugin, no dependency.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [glm_search] — header search disclosure.
 *
 * @return string
 */
function glm_search_shortcode() {

	ob_start();
	?>
	<div class="glm-search">
		<details class="glm-search__disclosure">
			<summary class="glm-search__toggle">
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'glm' ); ?></span>
				<svg class="glm-search__icon" viewBox="0 0 24 24" width="1em" height="1em"
					fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" aria-hidden="true" focusable="false">
					<circle cx="11" cy="11" r="7"></circle>
					<line x1="16.5" y1="16.5" x2="21" y2="21"></line>
				</svg>
			</summary>
			<form class="glm-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="glm-search-field"><?php esc_html_e( 'Search for:', 'glm' ); ?></label>
				<input
					class="glm-search__field"
					id="glm-search-field"
					type="search"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php esc_attr_e( 'Search…', 'glm' ); ?>"
				>
				<button class="glm-search__submit" type="submit">
					<span class="screen-reader-text"><?php esc_html_e( 'Submit search', 'glm' ); ?></span>
					<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor"
						stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">
						<circle cx="11" cy="11" r="7"></circle>
						<line x1="16.5" y1="16.5" x2="21" y2="21"></line>
					</svg>
				</button>
			</form>
		</details>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'glm_search', 'glm_search_shortcode' );
