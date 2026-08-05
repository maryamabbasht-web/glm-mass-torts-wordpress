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

				/*
				 * HOMEPAGE-ONLY tort block.
				 *
				 * Deliberately inline here rather than in the shared
				 * glm_tort_grid component or a section template: the
				 * archive and taxonomy pages render the same grid with
				 * their own headings, and must not inherit this one.
				 *
				 * Reuses .glm-eyebrow and .glm-section-title unchanged —
				 * the same classes the About and Divisions sections use,
				 * so typography and colour stay in the token system with
				 * no new styles.
				 *
				 * .glm-section-shell wraps the heading AND the grid so
				 * both share the container width and section padding of
				 * the surrounding sections. Without it the grid runs
				 * full-bleed and the heading would not line up with
				 * anything.
				 *
				 * Note: heading="yes" on the shortcode refers to the
				 * per-category dark header bars, not this heading. It
				 * has no effect on a featured grid, which has no
				 * category term.
				 */
				'<div class="glm-section-shell">'
					. '<span class="glm-eyebrow">Active Litigation</span>'
					. '<h2 class="glm-section-title">Featured Mass Torts.<br><em>Filing Now.</em></h2>'
					. '[glm_tort_grid tabs="no" featured="yes" heading="yes"]'
					. '</div>',

				'[glm_section slug="contact"]',
			),
		),
		/*
		 * NOTE ON THE <h1> ON EVERY PAGE (R11)
		 *
		 * The section templates are shared, so their top heading is an <h2>
		 * — only the hero carries an <h1>, and the hero only appears on the
		 * home page. Reusing sections elsewhere therefore produced pages
		 * with ZERO <h1>, which `wp glm audit` caught on five pages.
		 *
		 * Each non-home page declares its own <h1> rather than promoting a
		 * shared section's heading, which would give the home page two.
		 */
		/*
		 * STATES
		 *
		 * Layout follows the supplied Figma frame. The frame comes from the
		 * RCA file, so its copy is healthcare-reimbursement content (NSA,
		 * PIP, medical revenue). That has been rewritten for Mass Torts —
		 * the structure is the design's, the words are this firm's.
		 *
		 * Two sections of the frame are NOT built, deliberately:
		 *   - Testimonials would need a testimonial post type, and new CPTs
		 *     are out of scope.
		 *   - Per-state "Read More" targets would need individual state
		 *     pages, which are explicitly not in scope. Those links point
		 *     at /locations/, which already lists every office by state.
		 */
		'states' => array(
			'title'   => 'States',
			'content' => array(

				// ── Hero ────────────────────────────────────────────
				'<section class="glm-states-hero">'
					. '<div class="glm-states-hero__inner">'
						. '<nav class="glm-breadcrumb" aria-label="Breadcrumb">'
							. '<a href="/">Home</a><span aria-hidden="true">|</span>'
							. '<span aria-current="page">States</span>'
						. '</nav>'
						. '<h1 class="glm-states-hero__title">States We Work In</h1>'
						. '<p class="glm-states-hero__lead">Ged Lawyers represents injured clients in mass tort and class action litigation nationwide, with attorneys admitted across multiple jurisdictions and offices in Florida, Massachusetts, New Jersey and Michigan.</p>'
					. '</div>'
				. '</section>',

				// ── Overlapping intro card ──────────────────────────
				'<section class="glm-states-intro">'
					. '<div class="glm-states-intro__card">'
						. '<div class="glm-states-intro__main">'
							. '<h2 class="glm-states-intro__title">Discover Where You Can Work With Ged Lawyers</h2>'
							. '<ul class="glm-states-intro__list">'
								. '<li><span class="glm-badge">MDL</span><span>Multidistrict litigation and class actions filed in federal courts nationwide.</span></li>'
								. '<li><span class="glm-badge">OFFICES</span><span>Staffed offices in Florida, Massachusetts, New Jersey and Michigan.</span></li>'
							. '</ul>'
						. '</div>'
						. '<div class="glm-states-intro__aside">'
							. '<p class="glm-states-intro__aside-title">States<br>We Work In</p>'
							. '<a class="glm-btn glm-btn--solid" href="/contact-us/">Meet With Us</a>'
						. '</div>'
					. '</div>'
				. '</section>',

				// ── State cards ─────────────────────────────────────
				'<section class="glm-section-shell glm-states-grid-wrap">'
					. '<h2 class="glm-section-title glm-states-grid__title">Offices in Four States</h2>'
					. '<ul class="glm-states-grid">'
						. '<li class="glm-state-card"><div class="glm-state-card__head"><h3>Florida</h3></div>'
							. '<div class="glm-state-card__body"><p class="glm-state-card__name">Florida</p>'
							. '<p class="glm-state-card__desc">Four offices — Boca Raton, Naples, Estero and Panama City — handling mass tort, defective product and catastrophic injury claims statewide.</p>'
							. '<a class="glm-state-card__more" href="/locations/">Read More <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> about our Florida offices</span></a></div></li>'
						. '<li class="glm-state-card"><div class="glm-state-card__head"><h3>Massachusetts</h3></div>'
							. '<div class="glm-state-card__body"><p class="glm-state-card__name">Massachusetts</p>'
							. '<p class="glm-state-card__desc">Offices in Boston and Rehoboth representing clients in pharmaceutical, medical device and toxic exposure litigation.</p>'
							. '<a class="glm-state-card__more" href="/locations/">Read More <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> about our Massachusetts offices</span></a></div></li>'
						. '<li class="glm-state-card"><div class="glm-state-card__head"><h3>New Jersey</h3></div>'
							. '<div class="glm-state-card__body"><p class="glm-state-card__name">New Jersey</p>'
							. '<p class="glm-state-card__desc">Our Ridgewood office serves clients across New Jersey in defective product, pharmaceutical and mass tort claims.</p>'
							. '<a class="glm-state-card__more" href="/locations/">Read More <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> about our New Jersey office</span></a></div></li>'
						. '<li class="glm-state-card"><div class="glm-state-card__head"><h3>Michigan</h3></div>'
							. '<div class="glm-state-card__body"><p class="glm-state-card__name">Michigan</p>'
							. '<p class="glm-state-card__desc">Our Southfield office represents Michigan clients in multidistrict litigation, class actions and product liability matters.</p>'
							. '<a class="glm-state-card__more" href="/locations/">Read More <span aria-hidden="true">&rarr;</span><span class="screen-reader-text"> about our Michigan office</span></a></div></li>'
					. '</ul>'
				. '</section>',

				// ── CTA band: reuses the existing case evaluation form ─
				'<section class="glm-states-cta">'
					. '<div class="glm-states-cta__inner">'
						. '<div class="glm-states-cta__copy">'
							. '<span class="glm-eyebrow">Get Started Today</span>'
							. '<h2 class="glm-states-cta__title">Recover What You Are Owed</h2>'
							. '<p>Pursue compensation through mass tort and class action litigation. No fee unless we win.</p>'
							. '<dl class="glm-states-cta__facts">'
								. '<div><dt>Telephone</dt><dd><a href="tel:8444433529">844-443-3529</a></dd></div>'
								. '<div><dt>Office Hours</dt><dd>Mon&ndash;Sun, 24 hours</dd></div>'
							. '</dl>'
						. '</div>'
						. '<div class="glm-states-cta__form">[glm_case_form]</div>'
					. '</div>'
				. '</section>',
			),
		),

		'about' => array(
			'title'   => 'About',
			'content' => array(
				'<h1 class="glm-page-title">About Ged Lawyers</h1>',
				'[glm_section slug="about"]',
				'[glm_section slug="stats"]',
				'[glm_section slug="divisions"]',
				'[glm_section slug="contact"]',
			),
		),
		'contact-us' => array(
			'title'   => 'Contact',
			'content' => array(
				'<h1 class="glm-page-title">Contact Ged Lawyers</h1>',
				'[glm_section slug="contact"]',
			),
		),
		'locations' => array(
			'title'   => 'Locations',
			'content' => array(
				/*
				 * Eyebrow + title + lead, matching the About and Divisions
				 * pattern so the page opens the way every other section does
				 * rather than starting cold on a bare heading.
				 *
				 * layout="cards" changes the MARKUP only — headings, icons
				 * and the directions link. The footer keeps the default list.
				 */
				'<div class="glm-section-shell">'
					. '<span class="glm-eyebrow">Nationwide Representation</span>'
					. '<h1 class="glm-page-title">Our Offices</h1>'
					. '<p class="glm-locations__lead">Eight offices across four states, with attorneys licensed nationwide. Call the office nearest you, or reach us on 844-443-3529 at any hour.</p>'
					. '[glm_locations layout="cards"]'
					. '</div>',

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
			'content' => array(
				'<h1 class="glm-page-title">Privacy Policy</h1>',
				'<p><strong>This page needs real content.</strong> It must describe what personal data the case-evaluation forms collect, how long it is retained, who it is shared with, and how to request deletion.</p>',
			),
		),
		'terms' => array(
			'title'   => 'Terms',
			'stub'    => true,
			'content' => array(
				'<h1 class="glm-page-title">Terms of Use</h1>',
				'<p><strong>This page needs real content.</strong> Include attorney-advertising disclosures and a statement that using the site does not create an attorney-client relationship.</p>',
			),
		),
		'faq' => array(
			'title'   => 'FAQ',
			'stub'    => true,
			'content' => array(
				'<h1 class="glm-page-title">Frequently Asked Questions</h1>',
				'<p><strong>This page needs real content.</strong></p>',
			),
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

	/*
	 * Remaining pages. Array order sets menu order, so States lands
	 * directly after the Mass Torts dropdown and before About.
	 */
	foreach ( array( 'states' => 'States', 'about' => 'About', 'locations' => 'Locations', 'contact-us' => 'Contact' ) as $slug => $label ) {
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
