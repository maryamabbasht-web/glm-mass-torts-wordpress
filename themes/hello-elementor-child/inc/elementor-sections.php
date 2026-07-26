<?php
/**
 * Generates Elementor Saved Templates for the site's sections.
 *
 * WHY GENERATED
 *
 * These establish the intended structure once, correctly, so the remaining
 * sections can be built by copying a known-good pattern rather than a
 * remembered one. Run `wp glm build-sections`.
 *
 * DESIGN DECISION — STRUCTURE HERE, STYLING IN CSS
 *
 * Elementor 4.x does not expose the classic style controls (title_color,
 * typography_*, padding) on these widgets, and its styling schema shifts
 * between versions. More importantly, anything set through Elementor's
 * style panel lives in postmeta, where git cannot see it (R12).
 *
 * So every element carries a CSS class and nothing else. Structure and
 * copy live in Elementor, where editors can reach them. Styling lives in
 * assets/css/components.css, in git, using the design tokens (R1, R9).
 *
 * The trade-off: an editor cannot restyle these from the panel. Given the
 * founding brief, that is a feature.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deterministic 7-character element ID.
 *
 * Elementor needs unique IDs per element. Deriving them from a seed keeps
 * regeneration stable, so re-running does not churn the stored JSON.
 *
 * @param string $seed Unique seed.
 * @return string
 */
function glm_el_id( $seed ) {
	return substr( md5( 'glm:' . $seed ), 0, 7 );
}

/**
 * Build a container element.
 *
 * @param string $seed     Seed for the ID.
 * @param string $class    CSS class.
 * @param array  $children Child elements.
 * @param string $label    Navigator label (R7).
 * @param array  $extra    Extra settings.
 * @return array
 */
function glm_container( $seed, $class, array $children = array(), $label = '', array $extra = array() ) {
	return array(
		'id'       => glm_el_id( $seed ),
		'elType'   => 'container',
		'isInner'  => false,
		/*
		 * GOTCHA: containers use 'css_classes'. Widgets use '_css_classes'
		 * WITH a leading underscore. Getting this wrong fails silently —
		 * the element renders, just without your class, so the styling
		 * simply never applies and nothing reports an error.
		 * Verified against Elementor 4.2.0's control schema.
		 */
		'settings' => array_merge(
			array(
				'css_classes'   => $class,
				'_title'        => $label,      // Navigator name — R7.
				'content_width' => 'full',
			),
			$extra
		),
		'elements' => $children,
	);
}

/**
 * Build a widget element.
 *
 * @param string $seed     Seed for the ID.
 * @param string $type     Widget type.
 * @param array  $settings Widget settings.
 * @param string $class    CSS class.
 * @param string $label    Navigator label.
 * @return array
 */
function glm_widget( $seed, $type, array $settings, $class = '', $label = '' ) {
	if ( $class ) {
		$settings['_css_classes'] = $class;
	}
	if ( $label ) {
		$settings['_title'] = $label;
	}
	return array(
		'id'         => glm_el_id( $seed ),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => $settings,
		'elements'   => array(),
	);
}

/**
 * The Hero section.
 *
 * Copy lifted verbatim from source/glmasstorts.html lines 608-619.
 *
 * @return array Elementor data structure.
 */
function glm_section_hero() {

	$eyebrow = glm_widget(
		'hero.eyebrow',
		'heading',
		array(
			'title'       => 'Mass Torts &amp; Class Actions',
			'header_size' => 'div',
		),
		'glm-eyebrow',
		'Eyebrow'
	);

	$title = glm_widget(
		'hero.title',
		'heading',
		array(
			'title'       => 'Your Case.<br><em>Our Fight.</em><br>Maximum Recovery.',
			'header_size' => 'h1',
		),
		'glm-hero__title',
		'Heading: H1'
	);

	$lead = glm_widget(
		'hero.lead',
		'text-editor',
		array(
			'editor' => '<p>Ged Lawyers represents victims of dangerous drugs, defective medical '
				. 'devices, and corporate negligence nationwide. With offices across Florida, '
				. 'Massachusetts, New Jersey, and Michigan — we are ready to fight for you.</p>',
		),
		'glm-hero__lead',
		'Lead paragraph'
	);

	$call = glm_widget(
		'hero.btn.call',
		'button',
		array(
			'text' => 'Call 844-443-3529',
			'link' => array(
				'url'         => 'tel:8444433529',
				'is_external' => '',
				'nofollow'    => '',
			),
		),
		'glm-btn glm-btn--cta',
		'Button: Call'
	);

	$eval = glm_widget(
		'hero.btn.eval',
		'button',
		array(
			'text' => 'Free Case Evaluation →',
			'link' => array(
				'url'         => '#contact',
				'is_external' => '',
				'nofollow'    => '',
			),
		),
		'glm-btn glm-btn--outline',
		'Button: Evaluation'
	);

	$actions = glm_container( 'hero.actions', 'glm-hero__actions', array( $call, $eval ), 'Actions' );
	$content = glm_container( 'hero.content', 'glm-hero__content', array( $eyebrow, $title, $lead, $actions ), 'Content' );

	return array(
		glm_container( 'hero.root', 'glm-hero', array( $content ), 'Section: Hero' ),
	);
}

/**
 * Convenience: a heading widget.
 *
 * @param string $seed  Seed.
 * @param string $text  Title.
 * @param string $class CSS class.
 * @param string $label Navigator label.
 * @param string $tag   Heading tag.
 * @return array
 */
function glm_heading( $seed, $text, $class, $label, $tag = 'div' ) {
	return glm_widget( $seed, 'heading', array( 'title' => $text, 'header_size' => $tag ), $class, $label );
}

/**
 * Convenience: a text-editor widget.
 *
 * Used rather than a heading wherever the content may contain a shortcode —
 * the Text Editor widget runs the_content filters, so [glm_tort_count] and
 * [glm_results] resolve. The heading widget does not.
 *
 * @param string $seed  Seed.
 * @param string $html  Content.
 * @param string $class CSS class.
 * @param string $label Navigator label.
 * @return array
 */
function glm_text( $seed, $html, $class, $label ) {
	return glm_widget( $seed, 'text-editor', array( 'editor' => $html ), $class, $label );
}

/**
 * Stats bar — 5 figures.
 *
 * The first is [glm_tort_count], not a typed number. The source hardcoded
 * "35+" here and was wrong by five (R5).
 *
 * @return array
 */
function glm_section_stats() {

	$stats = array(
		array( 'count', '[glm_tort_count]+', 'Active Mass Torts' ),
		array( 'mdl',   '198K+',             'MDL Cases Nationwide' ),
		array( 'states', 'All 50',           'States Represented' ),
		array( 'cost',  '$0',                'Upfront to You' ),
		array( 'hours', '24/7',              'Free Consultations' ),
	);

	$items = array();
	foreach ( $stats as $i => $s ) {
		list( $key, $num, $lbl ) = $s;
		$items[] = glm_container(
			'stats.item.' . $key,
			'glm-stat',
			array(
				glm_text( 'stats.num.' . $key, $num, 'glm-stat__num', 'Number' ),
				glm_heading( 'stats.lbl.' . $key, $lbl, 'glm-stat__lbl', 'Label' ),
			),
			'Stat: ' . $lbl
		);
	}

	return array(
		glm_container(
			'stats.root',
			'glm-stats',
			array( glm_container( 'stats.inner', 'glm-stats__inner', $items, 'Items' ) ),
			'Section: Stats Bar'
		),
	);
}

/**
 * About — copy, highlight boxes, and the results list.
 *
 * @return array
 */
function glm_section_about() {

	$highlights = array(
		array( 'fee',   '⚖️', 'No Fee Unless We Win',   '100% contingency representation' ),
		array( 'reach', '🏛️', 'Nationwide Representation', 'Licensed across multiple states' ),
		array( 'eval',  '🔍', 'Free Case Evaluation',   'Confidential, no obligation' ),
		array( 'torts', '📋', '[glm_tort_count]+ Active Mass Torts', 'All major MDLs covered' ),
	);

	$boxes = array();
	foreach ( $highlights as $h ) {
		list( $key, $icon, $label, $sub ) = $h;
		$boxes[] = glm_container(
			'about.hl.' . $key,
			'glm-highlight',
			array(
				glm_heading( 'about.hl.icon.' . $key, $icon, 'glm-highlight__icon', 'Icon' ),
				glm_text( 'about.hl.label.' . $key, $label, 'glm-highlight__label', 'Label' ),
				glm_heading( 'about.hl.sub.' . $key, $sub, 'glm-highlight__sub', 'Sub-label' ),
			),
			'Highlight: ' . $key
		);
	}

	$left = glm_container(
		'about.left',
		'glm-about__text',
		array(
			glm_heading( 'about.eyebrow', 'About Ged Lawyers', 'glm-eyebrow', 'Eyebrow' ),
			glm_heading( 'about.title', 'Relentless Advocacy for<br><em>Injured Victims</em>', 'glm-section-title', 'Heading: H2', 'h2' ),
			glm_text(
				'about.copy',
				'<p>Ged Lawyers is a nationally recognized plaintiff litigation firm with decades of '
				. 'experience holding corporations, pharmaceutical manufacturers, and negligent entities '
				. 'accountable for the harm they cause to individuals and families.</p>'
				. '<p>Our Mass Torts and Class Actions division specializes exclusively in large-scale '
				. 'product liability and pharmaceutical injury cases. We combine the resources of a '
				. 'national firm with the personal attention of a boutique practice.</p>'
				. '<p>There are no upfront costs or fees. We only get paid when you win.</p>',
				'glm-about__copy',
				'Body copy'
			),
			glm_container( 'about.highlights', 'glm-about__highlights', $boxes, 'Highlights' ),
		),
		'Left column'
	);

	$right = glm_container(
		'about.right',
		'glm-about__right',
		array(
			glm_heading( 'about.right.eyebrow', 'Why Clients Choose Us', 'glm-eyebrow', 'Eyebrow' ),
			glm_text( 'about.results', '[glm_results]', 'glm-about__results', 'Results (from CPT)' ),
			glm_text(
				'about.disclaimer',
				'<p>* Results shown represent national MDL litigation outcomes and industry settlements. '
				. 'Past results do not guarantee future results. Individual recoveries vary.</p>',
				'glm-about__disclaimer',
				'Disclaimer'
			),
		),
		'Right column'
	);

	return array(
		glm_container(
			'about.root',
			'glm-about',
			array( glm_container( 'about.grid', 'glm-about__grid', array( $left, $right ), 'Grid' ) ),
			'Section: About'
		),
	);
}

/**
 * Divisions — three numbered cards.
 *
 * @return array
 */
function glm_section_divisions() {

	$divisions = array(
		array(
			'rcm', '01', '🏥', 'Healthcare Revenue Cycle Recovery',
			'Healthcare reimbursement and insurance recovery representation. Our team navigates the '
			. 'complexities of medical billing disputes, insurance denials, and revenue cycle challenges '
			. 'for healthcare providers and patients alike.',
			'https://www.revenuecycleattorneys.com/', 'Learn More',
		),
		array(
			'pi', '02', '⚖️', 'Personal Injury &amp; Catastrophic Loss',
			'Serious injury and wrongful death representation. When you or a loved one has suffered '
			. 'life-altering harm due to another\'s negligence, our trial attorneys pursue maximum '
			. 'compensation without compromise.',
			'https://www.gedlawyers.com/', 'Learn More',
		),
		array(
			'mt', '03', '📋', 'Mass Torts &amp; Class Actions',
			'Large-scale product and pharmaceutical liability cases. Our dedicated mass tort team '
			. 'handles all active MDLs — from dangerous drugs and defective devices to toxic exposures '
			. 'and institutional abuse.',
			'/mass-torts/', 'See All Mass Torts',
		),
	);

	$cards = array();
	foreach ( $divisions as $d ) {
		list( $key, $num, $icon, $title, $desc, $url, $cta ) = $d;
		$cards[] = glm_container(
			'div.card.' . $key,
			'glm-div-card',
			array(
				glm_heading( 'div.num.' . $key, $num, 'glm-div-card__num', 'Number' ),
				glm_heading( 'div.icon.' . $key, $icon, 'glm-div-card__icon', 'Icon' ),
				glm_heading( 'div.title.' . $key, $title, 'glm-div-card__title', 'Title', 'h3' ),
				glm_text( 'div.desc.' . $key, '<p>' . $desc . '</p>', 'glm-div-card__desc', 'Description' ),
				glm_widget(
					'div.link.' . $key,
					'button',
					array(
						'text' => $cta,
						'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
					),
					'glm-div-card__link',
					'Link'
				),
			),
			'Division: ' . $title
		);
	}

	return array(
		glm_container(
			'div.root',
			'glm-divisions',
			array(
				glm_container(
					'div.inner',
					'glm-divisions__inner',
					array(
						glm_heading( 'div.eyebrow', 'Ged Lawyers Divisions', 'glm-eyebrow', 'Eyebrow' ),
						glm_heading( 'div.title', 'Three Divisions.<br><em>One Mission.</em>', 'glm-section-title', 'Heading: H2', 'h2' ),
						glm_container( 'div.grid', 'glm-div-grid', $cards, 'Cards' ),
					),
					'Inner'
				),
			),
			'Section: Divisions'
		),
	);
}

/**
 * Contact — the conversion section.
 *
 * The form itself is added in Phase 5 once the form plugin is chosen.
 * The placeholder container is where it goes.
 *
 * @return array
 */
function glm_section_contact() {

	return array(
		glm_container(
			'contact.root',
			'glm-contact',
			array(
				glm_container(
					'contact.inner',
					'glm-contact__inner',
					array(
						glm_heading( 'contact.eyebrow', 'Get Help Now', 'glm-eyebrow', 'Eyebrow' ),
						glm_heading( 'contact.title', 'Free Case Evaluation.<br><em>No Fee Unless We Win.</em>', 'glm-section-title', 'Heading: H2', 'h2' ),
						glm_heading( 'contact.sub', 'Tell Us About Your Case', 'glm-contact__sub', 'Sub-heading', 'h3' ),
						glm_text(
							'contact.lead',
							'<p>All consultations are confidential, free, and carry no obligation. '
							. 'An attorney will review your case and contact you within 24 hours.</p>',
							'glm-contact__lead',
							'Lead'
						),
						glm_container( 'contact.form', 'glm-contact__form', array(), 'Form goes here (Phase 5)' ),
					),
					'Inner'
				),
			),
			'Section: Contact'
		),
	);
}

/**
 * All generatable sections.
 *
 * @return array slug => [title, callback]
 */
function glm_section_definitions() {
	return array(
		'hero'      => array( 'title' => 'Section: Hero',      'callback' => 'glm_section_hero' ),
		'stats'     => array( 'title' => 'Section: Stats Bar', 'callback' => 'glm_section_stats' ),
		'about'     => array( 'title' => 'Section: About',     'callback' => 'glm_section_about' ),
		'divisions' => array( 'title' => 'Section: Divisions', 'callback' => 'glm_section_divisions' ),
		'contact'   => array( 'title' => 'Section: Contact',   'callback' => 'glm_section_contact' ),
	);
}

/**
 * Hash a template's stored Elementor tree.
 *
 * Both the fingerprint and the later comparison must read through this one
 * function, or they will disagree about slashes and the guard misfires.
 *
 * @param int $post_id Template post ID.
 * @return string
 */
function glm_elementor_data_hash( $post_id ) {
	$raw = get_post_meta( $post_id, '_elementor_data', true );

	if ( ! is_string( $raw ) ) {
		$raw = wp_json_encode( $raw );
	}

	return $raw ? md5( $raw ) : '';
}

/**
 * Create or update a Saved Template.
 *
 * @param string $slug                   Section slug.
 * @param array  $def                    Definition.
 * @param bool   $force                  Overwrite if it already exists.
 * @param bool   $allow_overwrite_edited Also overwrite templates edited in Elementor.
 * @return array [status, post_id]
 */
function glm_build_section( $slug, array $def, $force = false, $allow_overwrite_edited = false ) {

	$existing = get_posts(
		array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_glm_section_slug',   // phpcs:ignore
			'meta_value'     => $slug,                 // phpcs:ignore
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( $post_id && ! $force ) {
		return array( 'skipped (exists)', $post_id );
	}

	$data = call_user_func( $def['callback'] );

	/*
	 * PROTECT HAND EDITS.
	 *
	 * A hash of the generated tree is stored at build time. If the stored
	 * data no longer matches that hash, someone has edited the template in
	 * Elementor — and --force would throw that work away silently.
	 *
	 * Refusing here rather than documenting the risk is the point of R14:
	 * the safe path should not depend on remembering anything.
	 */
	if ( $post_id && $force && ! $allow_overwrite_edited ) {
		$stored_hash  = get_post_meta( $post_id, '_glm_generated_hash', true );
		$current_hash = glm_elementor_data_hash( $post_id );

		if ( $stored_hash && $current_hash && $stored_hash !== $current_hash ) {
			return array( 'SKIPPED (edited in Elementor)', $post_id );
		}
	}

	$postarr = array(
		'post_type'   => 'elementor_library',
		'post_status' => 'publish',
		'post_title'  => $def['title'],
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

	// Elementor stores its tree as JSON with slashes already escaped.
	update_metadata( 'post', $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );

	/*
	 * Fingerprint AFTER writing, by reading back through the exact same
	 * path the comparison will later use.
	 *
	 * Hashing the string we passed in does NOT work: update_metadata()
	 * calls wp_unslash() internally, so what lands in the database is not
	 * what we handed it. Hashing the input made every template look edited
	 * on the next run — a guard that fires constantly is worse than none,
	 * because people learn to pass --force reflexively.
	 */
	update_post_meta( $post_id, '_glm_generated_hash', glm_elementor_data_hash( $post_id ) );
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_elementor_template_type', 'section' );
	update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
	update_post_meta( $post_id, '_glm_section_slug', $slug );

	wp_set_object_terms( $post_id, 'section', 'elementor_library_type' );

	/*
	 * GOTCHA: regenerating this template's CSS is not enough. Elementor
	 * caches rendered output, so pages embedding the section keep serving
	 * the previous markup — which looks exactly like "my change did not
	 * work" and sends you chasing the wrong bug. Cost me a round trip.
	 */
	if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		$css = \Elementor\Core\Files\CSS\Post::create( $post_id );
		$css->update();
	}

	wp_cache_delete( 'glm_section_id_' . $slug, 'glm' );

	return array( $status, $post_id );
}

/**
 * Find a section template's post ID by its slug.
 *
 * @param string $slug Section slug.
 * @return int 0 if not found.
 */
function glm_section_id( $slug ) {

	$cache_key = 'glm_section_id_' . $slug;
	$cached    = wp_cache_get( $cache_key, 'glm' );
	if ( false !== $cached ) {
		return (int) $cached;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_glm_section_slug',  // phpcs:ignore
			'meta_value'     => $slug,                // phpcs:ignore
		)
	);

	$id = $ids ? (int) $ids[0] : 0;
	wp_cache_set( $cache_key, $id, 'glm' );

	return $id;
}

/**
 * [glm_section slug="hero"] — insert a section template.
 *
 * R4 SAYS: build once, use everywhere, never copy-paste. In Elementor 3.x
 * that was done with [elementor-template id="123"].
 *
 * THAT SHORTCODE DOES NOT EXIST IN ELEMENTOR FREE 4.x. Verified on 4.2.0:
 * it is unregistered, so the literal text renders on the page — and because
 * wptexturize skips only registered shortcodes, WordPress even curls the
 * quotes into id=&#8221;50&#8243; first.
 *
 * This replaces it, and improves on it:
 *
 *   - Addressed by SLUG, not ID. Delete and rebuild a template and every
 *     page still resolves; a hardcoded ID would break everywhere.
 *   - Registered, so wptexturize leaves the attribute quotes alone.
 *   - Lives in git.
 *   - Fails visibly for logged-in editors instead of rendering nothing.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function glm_section_shortcode( $atts ) {

	$atts = shortcode_atts( array( 'slug' => '' ), $atts, 'glm_section' );

	if ( ! $atts['slug'] ) {
		return '';
	}

	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return '';
	}

	$id = glm_section_id( $atts['slug'] );

	if ( ! $id ) {
		// Silent for visitors; loud for anyone who can fix it.
		if ( current_user_can( 'edit_posts' ) ) {
			return sprintf(
				'<p style="padding:1rem;border:2px dashed #b91c1c;color:#b91c1c;">'
				. 'Section template <code>%s</code> not found. Run <code>wp glm build-sections</code>.</p>',
				esc_html( $atts['slug'] )
			);
		}
		return '';
	}

	return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id );
}
add_shortcode( 'glm_section', 'glm_section_shortcode' );

/**
 * WP-CLI: wp glm build-sections [--force]
 */
function glm_cli_build_sections( $args, $assoc_args ) {

	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		WP_CLI::error( 'Elementor is not active.' );
	}

	$force          = isset( $assoc_args['force'] );
	$allow_clobber  = isset( $assoc_args['overwrite-edited'] );
	$protected      = 0;

	foreach ( glm_section_definitions() as $slug => $def ) {
		list( $status, $id ) = glm_build_section( $slug, $def, $force, $allow_clobber );

		if ( false !== strpos( $status, 'edited' ) ) {
			$protected++;
		}

		WP_CLI::log( sprintf( '  %-10s %-30s id=%-4d  [glm_section slug="%s"]', $slug, $status, $id, $slug ) );
	}

	if ( $protected ) {
		WP_CLI::warning(
			sprintf(
				'%d template(s) were edited in Elementor and left untouched. '
				. 'Pass --overwrite-edited to discard those edits.',
				$protected
			)
		);
	}

	WP_CLI::success( 'Sections built. Insert with the shortcode above (R4 — never copy-paste).' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm build-sections', 'glm_cli_build_sections' );
}
