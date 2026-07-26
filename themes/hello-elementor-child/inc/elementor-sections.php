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
 * All generatable sections.
 *
 * @return array slug => [title, callback]
 */
function glm_section_definitions() {
	return array(
		'hero' => array(
			'title'    => 'Section: Hero',
			'callback' => 'glm_section_hero',
		),
	);
}

/**
 * Create or update a Saved Template.
 *
 * @param string $slug  Section slug.
 * @param array  $def   Definition.
 * @param bool   $force Overwrite if it already exists.
 * @return array [status, post_id]
 */
function glm_build_section( $slug, array $def, $force = false ) {

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

	$force = isset( $assoc_args['force'] );

	foreach ( glm_section_definitions() as $slug => $def ) {
		list( $status, $id ) = glm_build_section( $slug, $def, $force );
		WP_CLI::log( sprintf( '  %-10s %-18s id=%-4d  [glm_section slug="%s"]', $slug, $status, $id, $slug ) );
	}

	WP_CLI::success( 'Sections built. Insert with the shortcode above (R4 — never copy-paste).' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm build-sections', 'glm_cli_build_sections' );
}
