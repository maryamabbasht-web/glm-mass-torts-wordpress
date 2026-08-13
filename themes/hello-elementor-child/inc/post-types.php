<?php
/**
 * Custom post types.
 *
 * Registered in code rather than with CPT UI so the definitions live in
 * git rather than in the database (learning.md R12).
 *
 * Promotion rule applied (docs/component-inventory.md §4) — a repeating
 * thing becomes a CPT when it has 10+ items, OR editors change it during
 * normal operations, OR it appears in more than one place.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * tort — 40 items. The core of the site.
 *
 * Each tort generates its own page at /mass-torts/{slug}/, which is the
 * highest-value outcome of this migration: 40 landing pages from one
 * template instead of 40 anchors on a single URL.
 *
 * GOTCHA: the 'mass-torts' slug is baked into 46 URLs. Changing it later
 * means 46 redirects. Locked 2026-07-26.
 */
function glm_register_tort_post_type() {

	$labels = array(
		'name'                  => 'Mass Torts',
		'singular_name'         => 'Mass Tort',
		'menu_name'             => 'Mass Torts',
		'add_new'               => 'Add New',
		'add_new_item'          => 'Add New Mass Tort',
		'edit_item'             => 'Edit Mass Tort',
		'new_item'              => 'New Mass Tort',
		'view_item'             => 'View Mass Tort',
		'view_items'            => 'View Mass Torts',
		'search_items'          => 'Search Mass Torts',
		'not_found'             => 'No mass torts found.',
		'not_found_in_trash'    => 'No mass torts found in Trash.',
		'all_items'             => 'All Mass Torts',
		'archives'              => 'Mass Tort Archives',
		'item_published'        => 'Mass tort published.',
		'item_updated'          => 'Mass tort updated.',
	);

	register_post_type(
		'tort',
		array(
			'labels'             => $labels,
			'public'             => true,
			'has_archive'        => 'mass-torts',
			'rewrite'            => array(
				'slug'       => 'mass-torts',
				'with_front' => false,
			),
			'menu_icon'          => 'dashicons-shield-alt',
			'menu_position'      => 20,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'show_in_rest'       => true,
			'hierarchical'       => false,
			'capability_type'    => 'post',
		)
	);
}
add_action( 'init', 'glm_register_tort_post_type', 5 );

/**
 * location — 8 offices across 4 states.
 *
 * Qualifies on two counts: editors add and close offices, and each office
 * appears in both the footer and the contact page.
 *
 * Not publicly queryable on its own — an office does not need its own URL,
 * it needs to be listable. 'publicly_queryable' => false keeps 8 thin
 * pages out of the index.
 */
function glm_register_location_post_type() {

	register_post_type(
		'location',
		array(
			'labels'              => array(
				'name'          => 'Office Locations',
				'singular_name' => 'Office Location',
				'menu_name'     => 'Locations',
				'add_new_item'  => 'Add New Office',
				'edit_item'     => 'Edit Office',
				'all_items'     => 'All Offices',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-location',
			'menu_position'       => 21,
			'supports'            => array( 'title', 'page-attributes' ),
			'show_in_rest'        => true,
		)
	);
}
add_action( 'init', 'glm_register_location_post_type', 5 );

/**
 * result — verdict and settlement figures.
 *
 * Only four items, so it fails the 10+ test — but these are marketing
 * numbers that go stale quarterly ("198K+ as of Q1 2026"), and R14 says
 * the correct path must be the easy one. A form beats editing a layout.
 */
function glm_register_result_post_type() {

	register_post_type(
		'result',
		array(
			'labels'              => array(
				'name'          => 'Case Results',
				'singular_name' => 'Case Result',
				'menu_name'     => 'Case Results',
				'add_new_item'  => 'Add New Result',
				'edit_item'     => 'Edit Result',
				'all_items'     => 'All Results',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-awards',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'page-attributes' ),
			'show_in_rest'        => true,
		)
	);
}
add_action( 'init', 'glm_register_result_post_type', 5 );

/**
 * Order torts by menu_order then title in the admin list and on archives,
 * so editors control sequence without touching a layout.
 */
function glm_tort_archive_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( is_post_type_archive( 'tort' ) || is_tax( array( 'tort_category', 'tort_status' ) ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'posts_per_page', -1 );
	}
}
add_action( 'pre_get_posts', 'glm_tort_archive_order' );
