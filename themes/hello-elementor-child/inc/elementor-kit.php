<?php
/**
 * Elementor kit configuration, generated from theme-defined design tokens.
 *
 * WHY THIS EXISTS
 *
 * Elementor's Global Colors and Fonts live in postmeta on the kit post, so
 * git captures none of them (learning.md R12). Hand-clicking them into the
 * UI means the design system exists only in a database.
 *
 * Instead: the tokens below are the source of truth, they live in git, and
 * Elementor's kit is a GENERATED ARTIFACT. Run `wp glm apply-kit` to push.
 *
 * Values mirror style.css. If you change one, change both — style.css
 * serves the theme's own components, this serves Elementor-built sections.
 *
 * GOTCHA: re-running overwrites anything edited in Elementor's UI. That is
 * the intended direction of travel — the file wins, not the database.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The four Elementor system colour slots.
 *
 * IDs are fixed by Elementor and cannot be renamed — only their titles and
 * values. Every Elementor widget defaults to referencing these.
 *
 * @return array
 */
function glm_kit_system_colors() {
	return array(
		array( '_id' => 'primary',   'title' => 'Primary',    'color' => '#201E6B' ),
		array( '_id' => 'secondary', 'title' => 'Accent Blue', 'color' => '#506CFB' ),
		array( '_id' => 'text',      'title' => 'Text',       'color' => '#0B1929' ),
		array( '_id' => 'accent',    'title' => 'CTA Green',  'color' => '#01C53D' ),
	);
}

/**
 * Supporting palette.
 *
 * NOTE ON THE APPARENT DUPLICATE: 'Surface Deep' is the same hex as 'Text'
 * (#0B1929). That is deliberate. One value, two roles — body copy and dark
 * section backgrounds. De-duplicating would leave an editor picking a
 * background from a swatch labelled "Text", which is confusing. A clear
 * role name beats a clever de-duplication (R14).
 *
 * @return array
 */
function glm_kit_custom_colors() {
	return array(
		array( '_id' => 'glm_accent_light', 'title' => 'Accent Light', 'color' => '#DBE1FB' ),
		array( '_id' => 'glm_accent_pale',  'title' => 'Accent Pale',  'color' => '#DFE6FE' ),
		array( '_id' => 'glm_surface',      'title' => 'Surface',      'color' => '#EFF7F8' ),
		array( '_id' => 'glm_surface_deep', 'title' => 'Surface Deep', 'color' => '#0B1929' ),
		array( '_id' => 'glm_text_muted',   'title' => 'Text Muted',   'color' => '#5A6A7E' ),
	);
}

/**
 * Build an Elementor size control value.
 *
 * @param int|float $size Value.
 * @param string    $unit Unit.
 * @return array
 */
function glm_kit_size( $size, $unit = 'px' ) {
	return array(
		'unit'  => $unit,
		'size'  => $size,
		'sizes' => array(),
	);
}

/**
 * The four Elementor system typography slots.
 *
 * The source used clamp() for fluid headings. Elementor's font-size control
 * takes a single value per breakpoint, so the fluid range is expressed as
 * three explicit steps instead. The theme's own components keep clamp() via
 * --glm-h1 / --glm-h2 in style.css.
 *
 * @return array
 */
function glm_kit_system_typography() {
	return array(
		array(
			'_id'                          => 'primary',
			'title'                        => 'Display / H1',
			'typography_typography'        => 'custom',
			'typography_font_family'       => 'Cormorant Garamond',
			'typography_font_weight'       => '700',
			'typography_font_size'         => glm_kit_size( 76 ),
			'typography_font_size_tablet'  => glm_kit_size( 56 ),
			'typography_font_size_mobile'  => glm_kit_size( 42 ),
			'typography_line_height'       => glm_kit_size( 1.07, 'em' ),
		),
		array(
			'_id'                          => 'secondary',
			'title'                        => 'Section Title / H2',
			'typography_typography'        => 'custom',
			'typography_font_family'       => 'Cormorant Garamond',
			'typography_font_weight'       => '700',
			'typography_font_size'         => glm_kit_size( 50 ),
			'typography_font_size_tablet'  => glm_kit_size( 40 ),
			'typography_font_size_mobile'  => glm_kit_size( 30 ),
			'typography_line_height'       => glm_kit_size( 1.15, 'em' ),
		),
		array(
			'_id'                          => 'text',
			'title'                        => 'Body',
			'typography_typography'        => 'custom',
			'typography_font_family'       => 'Outfit',
			'typography_font_weight'       => '300',
			'typography_font_size'         => glm_kit_size( 16 ),
			'typography_line_height'       => glm_kit_size( 1.65, 'em' ),
		),
		array(
			'_id'                          => 'accent',
			'title'                        => 'Eyebrow / Label',
			'typography_typography'        => 'custom',
			'typography_font_family'       => 'Outfit',
			'typography_font_weight'       => '600',
			'typography_font_size'         => glm_kit_size( 11 ),
			'typography_text_transform'    => 'uppercase',
			'typography_letter_spacing'    => glm_kit_size( 3 ),
			'typography_line_height'       => glm_kit_size( 1.4, 'em' ),
		),
	);
}

/**
 * Additional typography presets used by more than one section.
 *
 * @return array
 */
function glm_kit_custom_typography() {
	return array(
		array(
			'_id'                     => 'glm_card_heading',
			'title'                   => 'Card Heading',
			'typography_typography'   => 'custom',
			'typography_font_family'  => 'Cormorant Garamond',
			'typography_font_weight'  => '700',
			'typography_font_size'    => glm_kit_size( 19 ),
			'typography_line_height'  => glm_kit_size( 1.3, 'em' ),
		),
		array(
			'_id'                     => 'glm_stat_number',
			'title'                   => 'Stat Number',
			'typography_typography'   => 'custom',
			'typography_font_family'  => 'Cormorant Garamond',
			'typography_font_weight'  => '700',
			'typography_font_size'    => glm_kit_size( 34 ),
			'typography_line_height'  => glm_kit_size( 1, 'em' ),
		),
		array(
			'_id'                     => 'glm_button',
			'title'                   => 'Button',
			'typography_typography'   => 'custom',
			'typography_font_family'  => 'Outfit',
			'typography_font_weight'  => '600',
			'typography_font_size'    => glm_kit_size( 14 ),
			'typography_text_transform' => 'uppercase',
			'typography_letter_spacing' => glm_kit_size( 0.8 ),
		),
	);
}

/**
 * Push all tokens into the active Elementor kit.
 *
 * Uses Elementor's own document API rather than writing postmeta directly,
 * so the schema is whatever this Elementor version expects.
 *
 * @return array|WP_Error Summary of what was written.
 */
function glm_apply_elementor_kit() {

	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return new WP_Error( 'glm_no_elementor', 'Elementor is not active.' );
	}

	$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

	if ( ! $kit || ! $kit->get_id() ) {
		return new WP_Error( 'glm_no_kit', 'No active Elementor kit found.' );
	}

	$settings = array(
		'system_colors'       => glm_kit_system_colors(),
		'custom_colors'       => glm_kit_custom_colors(),
		'system_typography'   => glm_kit_system_typography(),
		'custom_typography'   => glm_kit_custom_typography(),
		'default_generic_fonts' => 'Georgia, serif',

		/*
		 * BREAKPOINTS — the source breaks at 900px, Elementor defaults to
		 * 1024. Left alone, everything in the 900-1024 band renders with
		 * tablet styles the design never intended (R8).
		 *
		 * viewport_tablet = the max-width at which tablet styles apply.
		 */
		'active_breakpoints'  => array( 'viewport_mobile', 'viewport_tablet' ),
		'viewport_mobile'     => 767,
		'viewport_tablet'     => 900,
	);

	// Site logo, if it has been imported.
	$logo_id = (int) get_option( 'glm_logo_id' );
	if ( $logo_id && get_post( $logo_id ) ) {
		$settings['site_logo'] = array(
			'url' => wp_get_attachment_url( $logo_id ),
			'id'  => $logo_id,
			'size' => '',
		);
	}

	$kit->update_settings( $settings );

	/*
	 * GOTCHA — changing breakpoints is not enough on its own.
	 *
	 * Saving viewport_tablet stores the value, but Elementor's compiled CSS
	 * keeps the OLD breakpoint until its file cache is cleared. Verified:
	 * after the first run the kit reported tablet=900 while the generated
	 * CSS still emitted @media(max-width:1024px).
	 *
	 * The breakpoints manager caches its config for the request, so refresh
	 * it before clearing, or the regenerated files use stale values too.
	 */
	if ( isset( \Elementor\Plugin::$instance->breakpoints )
		&& method_exists( \Elementor\Plugin::$instance->breakpoints, 'refresh' ) ) {
		\Elementor\Plugin::$instance->breakpoints->refresh();
	}

	// Regenerates every Elementor CSS file, not just the kit's.
	if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		$css = \Elementor\Core\Files\CSS\Post::create( $kit->get_id() );
		$css->update();
	}

	return array(
		'kit_id'      => $kit->get_id(),
		'colors'      => count( $settings['system_colors'] ) + count( $settings['custom_colors'] ),
		'typography'  => count( $settings['system_typography'] ) + count( $settings['custom_typography'] ),
		'breakpoints' => sprintf( 'mobile=%d tablet=%d', $settings['viewport_mobile'], $settings['viewport_tablet'] ),
		'logo_id'     => $logo_id ?: 'none',
	);
}

/**
 * WP-CLI: wp glm apply-kit
 */
function glm_cli_apply_kit() {

	$result = glm_apply_elementor_kit();

	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}

	foreach ( $result as $k => $v ) {
		WP_CLI::log( sprintf( '  %-12s %s', $k, $v ) );
	}

	WP_CLI::success( 'Elementor kit updated from theme design tokens.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm apply-kit', 'glm_cli_apply_kit' );
}
