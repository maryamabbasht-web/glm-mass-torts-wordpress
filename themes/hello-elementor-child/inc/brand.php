<?php
/**
 * Brand assets: import from the repo into the Media Library.
 *
 * WHY THIS EXISTS
 *
 * The logo and favicon were originally imported by a throwaway script, so
 * they were the one step in the whole build that a fresh clone could not
 * reproduce — `wp glm build-*` rebuilt everything else and silently left
 * the site with no logo and no site icon.
 *
 * Masters live in brand/ and are version-controlled. This turns them into
 * Media Library attachments and records the resulting IDs, so the site can
 * be rebuilt on any machine with one more command.
 *
 * IDEMPOTENT: matched on a _glm_brand_key meta value, so re-running
 * replaces rather than accumulating duplicates.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The brand assets, and where each one is used.
 *
 * `file` is a basename without extension — any of the extensions below is
 * accepted, so a JPG or WebP can be dropped in without editing this file.
 *
 * @return array
 */
function glm_brand_assets() {
	return array(
		'logo'        => array(
			'file'   => 'GL Logo',
			'title'  => 'GL Mass Torts Logo',
			'alt'    => 'Ged Lawyers Mass Torts',
			'option' => 'glm_logo_id',
		),
		'favicon'     => array(
			'file'   => 'fav-icon',
			'title'  => 'GL Site Icon',
			'alt'    => '',
			'option' => 'site_icon',
		),
		'states-hero' => array(
			'file'   => 'states-hero',
			'title'  => 'States page banner',
			'alt'    => '', // Decorative: the hero already carries its heading.
			'option' => 'glm_states_hero_id',
		),
	);
}

/**
 * Accepted extensions, in preference order.
 *
 * @return string[]
 */
function glm_brand_extensions() {
	return array( 'webp', 'jpg', 'jpeg', 'png' );
}

/**
 * Directories searched for brand masters, in order.
 *
 * GOTCHA: GLM_DIR comes from get_stylesheet_directory(), which resolves
 * through the junction to the WordPress install — NOT the repo. So
 * dirname( GLM_DIR, 2 ) points at wp-content/, not at VS-WP/. That is why
 * the theme-local directory is checked first: it is the only one that
 * works identically whether the theme is junctioned, copied, or deployed
 * as a plain folder.
 *
 * The repo-root brand/ is kept as a fallback so the existing masters are
 * still found without being moved.
 *
 * @return string[]
 */
function glm_brand_dirs() {
	return array(
		GLM_DIR . '/assets/brand/',
		dirname( GLM_DIR, 2 ) . '/brand/',
		dirname( GLM_DIR, 3 ) . '/brand/',
	);
}

/**
 * Locate a master file regardless of extension or location.
 *
 * @param string $basename Filename without extension.
 * @return string|false Absolute path.
 */
function glm_find_brand_file( $basename ) {
	foreach ( glm_brand_dirs() as $dir ) {
		foreach ( glm_brand_extensions() as $ext ) {
			$path = $dir . $basename . '.' . $ext;
			if ( is_readable( $path ) ) {
				return $path;
			}
		}
	}
	return false;
}

/**
 * Import one asset.
 *
 * @param string $key Asset key.
 * @param array  $def Definition.
 * @return array [status, attachment id]
 */
function glm_import_brand_asset( $key, array $def ) {

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$src = glm_find_brand_file( $def['file'] );

	if ( ! $src ) {
		return array( 'missing from brand/', 0 );
	}

	// Remove any previous import of this asset.
	$previous = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_glm_brand_key', // phpcs:ignore
			'meta_value'     => $key,             // phpcs:ignore
		)
	);
	foreach ( $previous as $old ) {
		wp_delete_attachment( $old, true );
	}

	// Sideload MOVES the file, so work on a copy — never the repo master.
	$tmp = wp_tempnam( basename( $src ) );
	copy( $src, $tmp );

	$id = media_handle_sideload(
		array(
			'name'     => sanitize_file_name( basename( $src ) ),
			'tmp_name' => $tmp,
		),
		0,
		$def['title']
	);

	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return array( 'FAILED: ' . $id->get_error_message(), 0 );
	}

	update_post_meta( $id, '_glm_brand_key', $key );

	if ( '' !== $def['alt'] ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $def['alt'] );
	}

	update_option( $def['option'], $id );

	/*
	 * The site icon needs its own intermediate sizes. Setting the option
	 * directly skips the registration WP_Site_Icon performs during a
	 * Customizer upload, which leaves WordPress declaring a 150px file as
	 * sizes="32x32".
	 */
	if ( 'favicon' === $key ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';
		$site_icon = new WP_Site_Icon();
		add_filter( 'intermediate_image_sizes_advanced', array( $site_icon, 'additional_sizes' ) );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, get_attached_file( $id ) ) );
	}

	return array( 'imported', $id );
}

/**
 * Expose the states hero as a CSS custom property.
 *
 * Kept out of the markup so the page definition stays free of inline
 * styles (R9). The stylesheet falls back to a gradient when unset, so the
 * hero is never broken by a missing file.
 */
function glm_brand_inline_css() {

	$id = (int) get_option( 'glm_states_hero_id' );

	if ( ! $id ) {
		return;
	}

	$url = wp_get_attachment_image_url( $id, 'full' );

	if ( ! $url ) {
		return;
	}

	wp_add_inline_style(
		'glm-components',
		sprintf( ':root{--glm-states-hero-image:url("%s");}', esc_url( $url ) )
	);
}
add_action( 'wp_enqueue_scripts', 'glm_brand_inline_css', 30 );

/**
 * WP-CLI: wp glm import-brand
 */
function glm_cli_import_brand() {

	$missing = 0;

	foreach ( glm_brand_assets() as $key => $def ) {
		list( $status, $id ) = glm_import_brand_asset( $key, $def );

		if ( 0 === $id ) {
			$missing++;
		}

		WP_CLI::log(
			sprintf(
				'  %-12s %-24s %s',
				$key,
				$status,
				$id ? "id={$id} -> " . $def['option'] : 'expected brand/' . $def['file'] . '.{' . implode( ',', glm_brand_extensions() ) . '}'
			)
		);
	}

	if ( $missing ) {
		WP_CLI::warning( sprintf( '%d asset(s) not found in brand/.', $missing ) );
	}

	WP_CLI::success( 'Brand assets imported.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm import-brand', 'glm_cli_import_brand' );
}
