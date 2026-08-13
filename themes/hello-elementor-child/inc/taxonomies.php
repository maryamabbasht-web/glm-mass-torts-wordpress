<?php
/**
 * Taxonomies for the tort post type.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * tort_category — 6 terms, drives the tab browser and the pill colour.
 *
 * GOTCHA: the rewrite base is 'mass-torts/type', NOT 'mass-torts/category'.
 * 'category' would read as core's post category and confuse both editors
 * and URL scanning. Locked 2026-07-26 alongside the CPT slug.
 */
function glm_register_tort_category() {

	register_taxonomy(
		'tort_category',
		array( 'tort' ),
		array(
			'labels'            => array(
				'name'              => 'Tort Categories',
				'singular_name'     => 'Tort Category',
				'menu_name'         => 'Categories',
				'all_items'         => 'All Categories',
				'edit_item'         => 'Edit Category',
				'add_new_item'      => 'Add New Category',
				'search_items'      => 'Search Categories',
				'not_found'         => 'No categories found.',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'         => 'mass-torts/type',
				'with_front'   => false,
				'hierarchical' => false,
			),
		)
	);
}
add_action( 'init', 'glm_register_tort_category', 4 );

/**
 * tort_status — 5 terms, drives the status badge colour.
 *
 * Why a taxonomy AND a text field: the source contains 27 distinct status
 * strings ("Active · Filing Now", "Active · Bellwether 2026", "Settling ·
 * $1B+ Fund") but only 5 colours. The taxonomy carries the colour; the
 * ACF text field 'status_label' carries the specific wording.
 *
 * Modelling 27 terms would mean 27 colours to maintain. Modelling it as
 * free text alone would lose the colour logic entirely.
 *
 * Not public — status is a filter and a colour, not a browsable archive.
 */
function glm_register_tort_status() {

	register_taxonomy(
		'tort_status',
		array( 'tort' ),
		array(
			'labels'            => array(
				'name'          => 'Tort Statuses',
				'singular_name' => 'Tort Status',
				'menu_name'     => 'Statuses',
				'all_items'     => 'All Statuses',
				'edit_item'     => 'Edit Status',
				'add_new_item'  => 'Add New Status',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'glm_register_tort_status', 4 );

/* -------------------------------------------------------------------------
 * Default terms
 *
 * Seeded once on first load so the taxonomies are never empty and slugs
 * match what the CSS and templates expect. Editors can rename the labels;
 * the slugs are what the code keys off.
 * ---------------------------------------------------------------------- */

/**
 * Category definitions — slug => [ label, emoji, description ].
 *
 * The emoji is stored as term meta rather than baked into the label so
 * the label stays clean for screen readers and URLs.
 */
function glm_tort_categories() {
	return array(
		'pharma'  => array( 'Pharmaceutical Drugs',   '💊', 'Dangerous and defectively labelled prescription drugs.' ),
		'device'  => array( 'Medical Devices',        '🔬', 'Defective implants, catheters, and medical equipment.' ),
		'toxic'   => array( 'Toxic Exposure',         '☣️', 'Chemical, environmental, and water contamination claims.' ),
		'product' => array( 'Consumer Products',      '🏭', 'Defective and contaminated consumer goods.' ),
		'abuse'   => array( 'Sexual Assault & Abuse', '🔒', 'Institutional and platform-enabled abuse claims.' ),
		'tech'    => array( 'Technology & Emerging',  '📱', 'Platform addiction and emerging litigation.' ),
	);
}

/**
 * Status definitions — slug => label.
 *
 * These slugs map directly to CSS classes: .glm-status--active etc.
 */
function glm_tort_statuses() {
	return array(
		'active'    => 'Active',
		'settling'  => 'Settling',
		'emerging'  => 'Emerging',
		'appellate' => 'Appellate',
		'inactive'  => 'Not Active',
	);
}

/**
 * Seed both taxonomies once.
 *
 * Guarded by an option so this is not a per-request cost. Uses
 * term_exists() as a second guard so re-seeding never duplicates.
 */
function glm_seed_taxonomy_terms() {

	if ( get_option( 'glm_terms_seeded' ) === GLM_VERSION ) {
		return;
	}

	foreach ( glm_tort_categories() as $slug => $data ) {
		list( $label, $emoji, $description ) = $data;

		if ( ! term_exists( $slug, 'tort_category' ) ) {
			$term = wp_insert_term(
				$label,
				'tort_category',
				array(
					'slug'        => $slug,
					'description' => $description,
				)
			);

			if ( ! is_wp_error( $term ) ) {
				update_term_meta( $term['term_id'], 'glm_emoji', $emoji );
			}
		}
	}

	foreach ( glm_tort_statuses() as $slug => $label ) {
		if ( ! term_exists( $slug, 'tort_status' ) ) {
			wp_insert_term( $label, 'tort_status', array( 'slug' => $slug ) );
		}
	}

	update_option( 'glm_terms_seeded', GLM_VERSION );
}
add_action( 'init', 'glm_seed_taxonomy_terms', 20 );

/**
 * Get a category's emoji by term slug.
 *
 * @param string $slug Term slug.
 * @return string Emoji, or empty string.
 */
function glm_category_emoji( $slug ) {
	$term = get_term_by( 'slug', $slug, 'tort_category' );
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}
	return (string) get_term_meta( $term->term_id, 'glm_emoji', true );
}
