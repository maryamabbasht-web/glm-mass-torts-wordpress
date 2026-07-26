<?php
/**
 * Header and footer templates for Header Footer Elementor.
 *
 * Replaces the source's two separately maintained menus — a desktop nav
 * and a full duplicate mobile menu, each with its own copy of the tort
 * links. They had already drifted apart. One menu now, rendered
 * responsively (R6, R8).
 *
 * HFE schema, read from the plugin rather than assumed:
 *   post type : elementor-hf
 *   meta      : ehf_template_type = type_header | type_footer
 *   meta      : ehf_target_include_locations = ['rule' => ['basic-global']]
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Header: logo, navigation, phone CTA.
 *
 * @return array
 */
function glm_template_header() {

	$menu = wp_get_nav_menu_object( 'Primary' );

	$logo_id = (int) get_option( 'glm_logo_id' );
	$logo    = glm_widget(
		'hdr.logo',
		'image',
		array(
			'image'      => array( 'id' => $logo_id, 'url' => $logo_id ? wp_get_attachment_url( $logo_id ) : '' ),
			'image_size' => 'full',
			'link_to'    => 'custom',
			'link'       => array( 'url' => home_url( '/' ), 'is_external' => '', 'nofollow' => '' ),
		),
		'glm-header__logo',
		'Logo'
	);

	$nav = glm_widget(
		'hdr.nav',
		'navigation-menu',
		array(
			'menu'     => $menu ? $menu->slug : '',
			'layout'   => 'horizontal',
			'dropdown' => 'tablet',
		),
		'glm-header__nav',
		'Navigation'
	);

	$cta = glm_widget(
		'hdr.cta',
		'button',
		array(
			'text' => '844-443-3529',
			'link' => array( 'url' => 'tel:8444433529', 'is_external' => '', 'nofollow' => '' ),
			'selected_icon' => array( 'value' => 'fas fa-phone', 'library' => 'fa-solid' ),
		),
		'glm-btn glm-btn--cta glm-header__cta',
		'Phone CTA'
	);

	return array(
		glm_container(
			'hdr.root',
			'glm-header',
			array( glm_container( 'hdr.inner', 'glm-header__inner', array( $logo, $nav, $cta ), 'Inner' ) ),
			'Header'
		),
	);
}

/**
 * Footer: divisions, offices, socials, legal.
 *
 * Offices come from the location CPT via shortcode, so adding an office
 * never means editing this layout (R5).
 *
 * @return array
 */
function glm_template_footer() {

	$divisions = glm_text(
		'ftr.divisions',
		'<h4>Ged Lawyers Divisions</h4>'
		. '<ul>'
		. '<li><a href="https://www.revenuecycleattorneys.com/">Healthcare Revenue Cycle Recovery</a></li>'
		. '<li><a href="https://www.gedlawyers.com/">Personal Injury &amp; Catastrophic Loss</a></li>'
		. '<li><a href="' . esc_url( home_url( '/mass-torts/' ) ) . '">Mass Torts &amp; Class Actions</a></li>'
		. '</ul>',
		'glm-footer__divisions',
		'Divisions'
	);

	$offices = glm_text(
		'ftr.offices',
		'<h4>Ged Lawyers Locations</h4>[glm_locations]',
		'glm-footer__offices',
		'Offices (from CPT)'
	);

	$socials = glm_text( 'ftr.socials', '[glm_socials]', 'glm-footer__socials', 'Social links' );

	$legal = glm_text(
		'ftr.legal',
		'<ul class="glm-footer__links">'
		. '<li><a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a></li>'
		. '<li><a href="' . esc_url( home_url( '/terms/' ) ) . '">Terms</a></li>'
		. '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About Us</a></li>'
		. '<li><a href="' . esc_url( home_url( '/faq/' ) ) . '">FAQ</a></li>'
		. '</ul>'
		. '<p class="glm-footer__copy">© ' . esc_html( gmdate( 'Y' ) ) . ' Ged Lawyers · GLMasstorts.com · 844-443-3529</p>'
		. '<p class="glm-footer__disclaimer">Attorney Advertising. Prior results do not guarantee similar '
		. 'outcomes. The information on this website is for general informational purposes only and does '
		. 'not constitute legal advice. Contacting Ged Lawyers does not establish an attorney-client '
		. 'relationship.</p>',
		'glm-footer__legal',
		'Legal'
	);

	return array(
		glm_container(
			'ftr.root',
			'glm-footer',
			array(
				glm_container( 'ftr.top', 'glm-footer__top', array( $divisions, $offices ), 'Top' ),
				glm_container( 'ftr.bottom', 'glm-footer__bottom', array( $socials, $legal ), 'Bottom' ),
			),
			'Footer'
		),
	);
}

/**
 * Definitions for the two HFE templates.
 *
 * @return array
 */
function glm_hf_definitions() {
	return array(
		'header' => array(
			'title'    => 'Site Header',
			'type'     => 'type_header',
			'callback' => 'glm_template_header',
		),
		'footer' => array(
			'title'    => 'Site Footer',
			'type'     => 'type_footer',
			'callback' => 'glm_template_footer',
		),
	);
}

/**
 * Create or update one HFE template.
 *
 * @param string $slug  Slug.
 * @param array  $def   Definition.
 * @param bool   $force Overwrite.
 * @return array [status, id]
 */
function glm_build_hf_template( $slug, array $def, $force = false ) {

	$existing = get_posts(
		array(
			'post_type'      => 'elementor-hf',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_glm_hf_slug',  // phpcs:ignore
			'meta_value'     => $slug,           // phpcs:ignore
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( $post_id && ! $force ) {
		return array( 'skipped (exists)', $post_id );
	}

	$postarr = array(
		'post_type'   => 'elementor-hf',
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

	update_metadata( 'post', $post_id, '_elementor_data', wp_slash( wp_json_encode( call_user_func( $def['callback'] ) ) ) );
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_elementor_template_type', 'wp-post' );
	update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );

	// HFE wiring.
	update_post_meta( $post_id, 'ehf_template_type', $def['type'] );
	update_post_meta( $post_id, 'ehf_target_include_locations', array( 'rule' => array( 'basic-global' ) ) );
	update_post_meta( $post_id, '_glm_hf_slug', $slug );

	if ( isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
		\Elementor\Core\Files\CSS\Post::create( $post_id )->update();
	}

	return array( $status, $post_id );
}

/**
 * WP-CLI: wp glm build-header-footer [--force]
 */
function glm_cli_build_hf( $args, $assoc_args ) {

	if ( ! post_type_exists( 'elementor-hf' ) ) {
		WP_CLI::error( 'Header Footer Elementor is not active.' );
	}

	$force = isset( $assoc_args['force'] );

	foreach ( glm_hf_definitions() as $slug => $def ) {
		list( $status, $id ) = glm_build_hf_template( $slug, $def, $force );
		WP_CLI::log( sprintf( '  %-8s %-18s id=%-4d type=%s', $slug, $status, $id, $def['type'] ) );
	}

	WP_CLI::success( 'Header and footer built.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm build-header-footer', 'glm_cli_build_hf' );
}
