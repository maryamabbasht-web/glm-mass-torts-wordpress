<?php
/**
 * Site pages and navigation, built from code.
 *
 * Pages are assembled from section shortcodes (R4, R10) so each page's
 * content is a short table of contents rather than a pasted layout.
 *
 * Reproducible via `wp glm build-pages`. Existing pages are left alone
 * unless --force is passed, so hand-written copy is never clobbered.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The site's pages.
 *
 * @return array slug => definition
 */
function glm_page_definitions() {

	return array(
		'home' => array(
			'title'    => 'Home',
			'front'    => true,
			'content'  => array(
				'[glm_section slug="hero"]',
				'[glm_section slug="stats"]',
				'[glm_section slug="about"]',
				'[glm_section slug="divisions"]',
				'[glm_tort_grid tabs="no" featured="yes" heading="no"]',
				'[glm_section slug="contact"]',
			),
		),
		'about' => array(
			'title'   => 'About',
			'content' => array(
				'[glm_section slug="about"]',
				'[glm_section slug="stats"]',
				'[glm_section slug="divisions"]',
				'[glm_section slug="contact"]',
			),
		),
		'contact-us' => array(
			'title'   => 'Contact',
			'content' => array(
				'[glm_section slug="contact"]',
			),
		),
		'locations' => array(
			'title'   => 'Locations',
			'content' => array(
				'<h1>Our Offices</h1>',
				'[glm_locations]',
				'[glm_section slug="contact"]',
			),
		),

		/*
		 * The source's footer linked all four of these to href="#".
		 * For a firm collecting injury details through web forms, a missing
		 * privacy policy is a compliance exposure, not a cosmetic gap.
		 * These ship as clearly-marked stubs so the gap stays visible.
		 */
		'privacy-policy' => array(
			'title'   => 'Privacy Policy',
			'stub'    => true,
			'content' => array( '<p><strong>This page needs real content.</strong> It must describe what personal data the case-evaluation forms collect, how long it is retained, who it is shared with, and how to request deletion.</p>' ),
		),
		'terms' => array(
			'title'   => 'Terms',
			'stub'    => true,
			'content' => array( '<p><strong>This page needs real content.</strong> Include attorney-advertising disclosures and a statement that using the site does not create an attorney-client relationship.</p>' ),
		),
		'faq' => array(
			'title'   => 'FAQ',
			'stub'    => true,
			'content' => array( '<p><strong>This page needs real content.</strong></p>' ),
		),
	);
}

/**
 * Create or update one page.
 *
 * @param string $slug  Page slug.
 * @param array  $def   Definition.
 * @param bool   $force Overwrite existing content.
 * @return array [status, post_id]
 */
function glm_build_page( $slug, array $def, $force = false ) {

	$existing = get_page_by_path( $slug );
	$post_id  = $existing ? (int) $existing->ID : 0;

	if ( $post_id && ! $force ) {
		return array( 'skipped (exists)', $post_id );
	}

	$postarr = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $def['title'],
		'post_name'    => $slug,
		'post_content' => implode( "\n\n", $def['content'] ),
	);

	if ( $post_id ) {
		$postarr['ID'] = $post_id;
		wp_update_post( $postarr );
		$status = 'updated';
	} else {
		$post_id = wp_insert_post( $postarr );
		$status  = 'created';
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return array( 'failed', 0 );
	}

	// Full-width canvas so sections run edge to edge.
	update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $post_id, '_glm_page_slug', $slug );

	if ( ! empty( $def['front'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $post_id );
	}

	if ( ! empty( $def['stub'] ) ) {
		update_post_meta( $post_id, '_glm_needs_content', '1' );
	}

	return array( $status, $post_id );
}

/**
 * Build the primary navigation menu.
 *
 * Tort categories are pulled from the taxonomy rather than typed, so
 * adding a category adds a menu item (R5). The source maintained two
 * hand-written menus that had already drifted apart from each other.
 *
 * @return int Menu term ID.
 */
function glm_build_primary_menu() {

	$name = 'Primary';
	$menu = wp_get_nav_menu_object( $name );

	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );
	} else {
		$menu_id = (int) $menu->term_id;
		// Rebuild from scratch so re-running does not duplicate items.
		foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
	}

	if ( is_wp_error( $menu_id ) ) {
		return 0;
	}

	$order = 0;

	// Home
	$home = get_page_by_path( 'home' );
	if ( $home ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Home',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $home->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => ++$order,
			)
		);
	}

	// Mass Torts, with the six categories beneath it.
	$parent = wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'     => 'Mass Torts',
			'menu-item-url'       => get_post_type_archive_link( 'tort' ),
			'menu-item-type'      => 'custom',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => ++$order,
		)
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'tort_category',
			'hide_empty' => true,
		)
	);

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $term->name,
					'menu-item-object'    => 'tort_category',
					'menu-item-object-id' => $term->term_id,
					'menu-item-type'      => 'taxonomy',
					'menu-item-parent-id' => $parent,
					'menu-item-status'    => 'publish',
					'menu-item-position'  => ++$order,
				)
			);
		}
	}

	// Remaining pages.
	foreach ( array( 'about' => 'About', 'locations' => 'Locations', 'contact-us' => 'Contact' ) as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => ++$order,
			)
		);
	}

	return (int) $menu_id;
}

/**
 * WP-CLI: wp glm build-pages [--force]
 */
function glm_cli_build_pages( $args, $assoc_args ) {

	$force = isset( $assoc_args['force'] );
	$stubs = 0;

	foreach ( glm_page_definitions() as $slug => $def ) {
		list( $status, $id ) = glm_build_page( $slug, $def, $force );
		if ( ! empty( $def['stub'] ) && 'failed' !== $status ) {
			$stubs++;
		}
		WP_CLI::log( sprintf( '  %-16s %-18s id=%-4d /%s/', $slug, $status, $id, $slug ) );
	}

	$menu_id = glm_build_primary_menu();
	WP_CLI::log( sprintf( '  %-16s menu id=%d', 'Primary menu', $menu_id ) );

	if ( $stubs ) {
		WP_CLI::warning( sprintf( '%d page(s) are stubs awaiting real content (privacy, terms, FAQ).', $stubs ) );
	}

	WP_CLI::success( 'Pages and navigation built.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm build-pages', 'glm_cli_build_pages' );
}
