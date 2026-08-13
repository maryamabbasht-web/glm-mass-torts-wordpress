<?php
/**
 * [glm_socials] — social links using Font Awesome.
 *
 * Replaces the five SVG icons the source hotlinked from another
 * project's staging server. Those were a live outage waiting to happen:
 * when that staging site gets pruned, the icons vanish from production.
 *
 * Elementor bundles Font Awesome 5 Free, so the icons cost nothing extra.
 *
 * THE X PROBLEM: fa-x-twitter only exists in Font Awesome 6. Elementor
 * ships 5, which has the old bird as fa-twitter. Since the link points at
 * x.com, this renders X as a small inline SVG instead — exact, ~300 bytes,
 * no extra library. Set 'icon' => 'fa-twitter' on that entry to use the
 * bird instead.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The social profiles.
 *
 * Filterable rather than hardcoded so URLs can change without editing
 * markup. These change rarely enough that a developer action is the right
 * cost (R13); if editors need to change them, add a settings screen then.
 *
 * @return array
 */
function glm_social_profiles() {

	$profiles = array(
		'facebook' => array(
			'label' => 'Facebook',
			'url'   => 'https://web.facebook.com/gedlawyers/',
			'icon'  => 'fa-facebook-f',
		),
		'x' => array(
			'label' => 'X',
			'url'   => 'https://x.com/GedLawyersLLP',
			'icon'  => 'svg-x', // Not in Font Awesome 5 — see file header.
		),
		'linkedin' => array(
			'label' => 'LinkedIn',
			'url'   => 'https://www.linkedin.com/company/ged-lawyers-llp/',
			'icon'  => 'fa-linkedin-in',
		),
		'instagram' => array(
			'label' => 'Instagram',
			'url'   => 'https://www.instagram.com/gedlawyers/',
			'icon'  => 'fa-instagram',
		),
		'youtube' => array(
			'label' => 'YouTube',
			'url'   => 'https://www.youtube.com/channel/UCHqbFy-qYzcvEKHHeitT1WQ/videos',
			'icon'  => 'fa-youtube',
		),
	);

	return apply_filters( 'glm_social_profiles', $profiles );
}

/**
 * The X logo as inline SVG.
 *
 * @return string
 */
function glm_x_logo_svg() {
	return '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false">'
		. '<path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.46l8.6-9.83L0 1.15h7.59l5.24 6.93zm-1.29 19.5h2.04L6.49 3.24H4.3z"/>'
		. '</svg>';
}

/**
 * Make sure Font Awesome is actually present.
 *
 * GOTCHA 1: Elementor only enqueues its Font Awesome stylesheets when an
 * Elementor icon widget renders on the page. A shortcode using `fab`
 * classes on a page with no icon widget would output empty squares.
 *
 * GOTCHA 2: enqueueing from inside a shortcode happens during the_content,
 * which is after wp_head has already printed. The stylesheet then lands in
 * the footer and the icons visibly pop in after paint. So this also runs on
 * wp_enqueue_scripts, where it belongs. Socials appear in the footer on
 * every page, so loading it site-wide is honest rather than wasteful.
 *
 * Calling wp_enqueue_style twice is harmless, so the shortcode keeps its
 * call as a safety net for contexts that render outside the main query.
 */
function glm_enqueue_fontawesome_brands() {

	if ( wp_style_is( 'elementor-icons-fa-brands', 'registered' ) ) {
		wp_enqueue_style( 'elementor-icons-fa-brands' );
		return;
	}

	// Elementor absent or renamed the handle — fall back to its bundled files.
	$base = WP_PLUGIN_DIR . '/elementor/assets/lib/font-awesome/css/';
	if ( file_exists( $base . 'brands.min.css' ) ) {
		$uri = plugins_url( 'elementor/assets/lib/font-awesome/css/', WP_PLUGIN_DIR );
		wp_enqueue_style( 'glm-fa-core', $uri . 'fontawesome.min.css', array(), GLM_VERSION );
		wp_enqueue_style( 'glm-fa-brands', $uri . 'brands.min.css', array( 'glm-fa-core' ), GLM_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'glm_enqueue_fontawesome_brands', 25 );

/**
 * Render the social links.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function glm_socials_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'class' => '',
			'label' => 'Follow Ged Lawyers',
		),
		$atts,
		'glm_socials'
	);

	$profiles = glm_social_profiles();
	if ( empty( $profiles ) ) {
		return '';
	}

	$needs_fa = false;
	foreach ( $profiles as $p ) {
		if ( 0 === strpos( $p['icon'], 'fa-' ) ) {
			$needs_fa = true;
			break;
		}
	}
	if ( $needs_fa ) {
		glm_enqueue_fontawesome_brands();
	}

	$classes = trim( 'glm-socials ' . $atts['class'] );

	ob_start();
	?>
	<ul class="<?php echo esc_attr( $classes ); ?>" aria-label="<?php echo esc_attr( $atts['label'] ); ?>">
		<?php foreach ( $profiles as $key => $profile ) : ?>
			<li class="glm-socials__item">
				<a class="glm-socials__link glm-socials__link--<?php echo esc_attr( $key ); ?>"
				   href="<?php echo esc_url( $profile['url'] ); ?>"
				   target="_blank"
				   rel="noopener noreferrer">
					<?php
					if ( 'svg-x' === $profile['icon'] ) {
						echo glm_x_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
					} else {
						printf(
							'<i class="fab %s" aria-hidden="true"></i>',
							esc_attr( $profile['icon'] )
						);
					}
					?>
					<span class="screen-reader-text"><?php echo esc_html( $profile['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	return ob_get_clean();
}
add_shortcode( 'glm_socials', 'glm_socials_shortcode' );
