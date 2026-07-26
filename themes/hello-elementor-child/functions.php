<?php
/**
 * GLM Mass Torts — child theme bootstrap.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'GLM_VERSION', '0.1.0' );
define( 'GLM_DIR', get_stylesheet_directory() );
define( 'GLM_URI', get_stylesheet_directory_uri() );

/**
 * Enqueue parent and child styles.
 *
 * Hello Elementor registers 'hello-elementor' and 'hello-elementor-theme-style'.
 * We depend on the former so our tokens always load after it.
 */
function glm_enqueue_assets() {
	wp_enqueue_style(
		'glm-tokens',
		GLM_URI . '/style.css',
		array( 'hello-elementor' ),
		GLM_VERSION
	);

	wp_enqueue_style(
		'glm-components',
		GLM_URI . '/assets/css/components.css',
		array( 'glm-tokens' ),
		GLM_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'glm_enqueue_assets', 20 );

/**
 * Self-host note (docs/design-tokens.md):
 * Fonts are currently expected from Google. Consider self-hosting in
 * Phase 2 to remove a third-party connection and improve LCP.
 */
function glm_enqueue_fonts() {
	wp_enqueue_style(
		'glm-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600&display=swap',
		array(),
		null // phpcs:ignore — external stylesheet, no version query.
	);
}
add_action( 'wp_enqueue_scripts', 'glm_enqueue_fonts', 10 );

/**
 * Tell ACF to load AND save field groups as JSON inside this theme.
 *
 * Why (learning.md R12): field definitions otherwise live only in the
 * database. This turns them into version-controlled files.
 */
function glm_acf_json_save_point( $path ) {
	return GLM_DIR . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'glm_acf_json_save_point' );

function glm_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = GLM_DIR . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'glm_acf_json_load_point' );

/* -------------------------------------------------------------------------
 * Includes
 * ---------------------------------------------------------------------- */

require_once GLM_DIR . '/inc/post-types.php';
require_once GLM_DIR . '/inc/taxonomies.php';
require_once GLM_DIR . '/inc/tort-grid.php';

// Admin UI, plus WP-CLI so the import is scriptable and repeatable.
if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once GLM_DIR . '/inc/importer.php';
}

/**
 * Flush rewrite rules once after activation.
 *
 * Gotcha: without this, /mass-torts/{slug}/ returns 404 until someone
 * visits Settings → Permalinks and clicks Save. Never call
 * flush_rewrite_rules() on every load — it is expensive.
 */
function glm_maybe_flush_rewrites() {
	if ( get_option( 'glm_rewrites_flushed' ) === GLM_VERSION ) {
		return;
	}
	flush_rewrite_rules();
	update_option( 'glm_rewrites_flushed', GLM_VERSION );
}
add_action( 'init', 'glm_maybe_flush_rewrites', 99 );

/**
 * Warn in admin if ACF is missing — every tort field depends on it.
 */
function glm_admin_dependency_notice() {
	if ( function_exists( 'get_field' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>GLM Mass Torts:</strong> '
		. 'Advanced Custom Fields is not active. Tort cards will render '
		. 'without status, MDL reference, or settlement fields.</p></div>';
}
add_action( 'admin_notices', 'glm_admin_dependency_notice' );

/**
 * Safe ACF accessor.
 *
 * Lets templates call glm_field() without guarding for ACF being absent,
 * so a deactivated plugin degrades instead of fatally erroring.
 *
 * @param string   $selector Field name.
 * @param int|null $post_id  Post ID, defaults to current.
 * @param mixed    $fallback Returned when ACF is unavailable or empty.
 * @return mixed
 */
function glm_field( $selector, $post_id = null, $fallback = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}
	$value = get_field( $selector, $post_id );
	return ( null === $value || '' === $value || false === $value ) ? $fallback : $value;
}
