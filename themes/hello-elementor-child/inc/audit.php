<?php
/**
 * Ruleset audit — `wp glm audit`.
 *
 * The 14 rules in learning.md are only worth something if they are checked.
 * A rule enforced by memory is a rule that decays the week after handoff.
 *
 * This walks every Elementor template and page and reports violations with
 * the rule that was broken, so the output is actionable rather than a
 * generic lint.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collect every Elementor document on the site.
 *
 * @return array id => label
 */
function glm_audit_documents() {

	$docs = array();

	$posts = get_posts(
		array(
			'post_type'      => array( 'elementor_library', 'elementor-hf', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $p ) {
		if ( get_post_meta( $p->ID, '_elementor_data', true ) ) {
			$docs[ $p->ID ] = $p->post_type . ': ' . $p->post_title;
		}
	}

	return $docs;
}

/**
 * Walk an Elementor tree, invoking a callback on each element.
 *
 * @param array    $els      Elements.
 * @param callable $callback Receives ( $element, $depth ).
 * @param int      $depth    Current depth.
 */
function glm_audit_walk( $els, callable $callback, $depth = 0 ) {
	foreach ( (array) $els as $el ) {
		$callback( $el, $depth );
		if ( ! empty( $el['elements'] ) ) {
			glm_audit_walk( $el['elements'], $callback, $depth + 1 );
		}
	}
}

/**
 * Run the audit.
 *
 * @return array rule => list of findings
 */
function glm_run_audit() {

	$findings = array();

	$add = function ( $rule, $msg ) use ( &$findings ) {
		$findings[ $rule ][] = $msg;
	};

	/* ── Elementor documents ─────────────────────────────── */

	foreach ( glm_audit_documents() as $id => $label ) {

		$raw = get_post_meta( $id, '_elementor_data', true );
		$tree = json_decode( is_string( $raw ) ? $raw : wp_json_encode( $raw ), true );

		if ( ! is_array( $tree ) ) {
			continue;
		}

		glm_audit_walk(
			$tree,
			function ( $el ) use ( $add, $label ) {

				$type     = $el['elType'] ?? '';
				$settings = (array) ( $el['settings'] ?? array() );
				$what     = $type . ( isset( $el['widgetType'] ) ? ':' . $el['widgetType'] : '' );

				// R2 — legacy Section/Column.
				if ( in_array( $type, array( 'section', 'column' ), true ) ) {
					$add( 'R2', "{$label} — legacy <{$type}> element (use Flexbox Container)" );
				}

				// R7 — unnamed layer in the Navigator.
				if ( 'container' === $type && empty( $settings['_title'] ) ) {
					$add( 'R7', "{$label} — container with no Navigator name" );
				}

				// R1 — raw hex on an element instead of a Global Color.
				foreach ( $settings as $key => $value ) {
					if ( is_string( $value )
						&& preg_match( '/^#[0-9a-f]{3,8}$/i', trim( $value ) )
						&& empty( $settings['__globals__'][ $key ] ) ) {
						$add( 'R1', "{$label} — {$what}.{$key} = {$value} (use a Global Color)" );
					}
				}

				// R8 — responsive visibility toggles used to duplicate content.
				foreach ( array( 'hide_desktop', 'hide_tablet', 'hide_mobile' ) as $flag ) {
					if ( ! empty( $settings[ $flag ] ) ) {
						$add( 'R8', "{$label} — {$what} uses {$flag} (do not maintain duplicate variants)" );
					}
				}

				// R9 — per-element custom CSS.
				if ( ! empty( $settings['custom_css'] ) ) {
					$add( 'R9', "{$label} — {$what} has per-element custom CSS (move to components.css)" );
				}

				// R3 — a single text widget carrying several headings.
				if ( 'text-editor' === ( $el['widgetType'] ?? '' ) ) {
					$editor = (string) ( $settings['editor'] ?? '' );
					$blocks = preg_match_all( '/<h[1-6][\s>]/i', $editor );
					if ( $blocks > 1 ) {
						$add( 'R3', "{$label} — one text widget holds {$blocks} headings (split into elements)" );
					}
				}
			}
		);
	}

	/* ── Pages ───────────────────────────────────────────── */

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	foreach ( $pages as $p ) {

		// R14 — stubs still awaiting real content.
		if ( get_post_meta( $p->ID, '_glm_needs_content', true ) ) {
			$add( 'R14', "Page '{$p->post_title}' is still a placeholder awaiting real content" );
		}

		// R11 — heading hierarchy, checked on the rendered page.
		$html = wp_remote_retrieve_body( wp_remote_get( get_permalink( $p->ID ), array( 'timeout' => 20 ) ) );
		if ( $html ) {
			$h1 = preg_match_all( '/<h1[\s>]/i', $html );
			if ( 1 !== $h1 ) {
				$add( 'R11', "Page '{$p->post_title}' has {$h1} <h1> elements (expect exactly 1)" );
			}

			// R11 — images without alt text.
			preg_match_all( '/<img\b[^>]*>/i', $html, $imgs );
			$noalt = 0;
			foreach ( $imgs[0] as $img ) {
				if ( ! preg_match( '/\balt\s*=/i', $img ) ) {
					$noalt++;
				}
			}
			if ( $noalt ) {
				$add( 'R11', "Page '{$p->post_title}' has {$noalt} image(s) with no alt attribute" );
			}

			// Not a numbered rule, but the risk that started the asset work.
			if ( false !== strpos( $html, 'wpengine' ) ) {
				$add( 'ASSETS', "Page '{$p->post_title}' still references a staging server" );
			}
		}
	}

	/* ── Stylesheet ──────────────────────────────────────── */

	/*
	 * R9 — the single-source-of-truth architecture.
	 *
	 * Elementor's design system cannot be removed, so it is neutralised by
	 * one reset layer at the top of components.css. Everything below that
	 * styles GLM wrapper classes and relies on inheritance.
	 *
	 * Three ways this can silently regress:
	 *   1. the reset block gets deleted or edited away
	 *   2. someone reaches into Elementor markup without the `html` prefix,
	 *      producing a (0,2,0) tie that loses on load order
	 *   3. !important creeps back in place of specificity
	 */
	$css_file = GLM_DIR . '/assets/css/components.css';

	if ( is_readable( $css_file ) ) {

		$css = file_get_contents( $css_file ); // phpcs:ignore

		// 1. The neutralisation layer must still be there.
		$has_reset = false !== strpos( $css, 'html .elementor-widget-heading .elementor-heading-title' )
			&& preg_match( '/font\s*:\s*inherit/', $css );

		if ( ! $has_reset ) {
			$add( 'R9', 'components.css — the Elementor neutralisation layer is missing; Elementor kit defaults will override the design system' );
		}

		/*
		 * 1b. The reset must NEVER target a widget root.
		 *
		 * Elementor styles `.elementor-widget-text-editor` — the root —
		 * which is the same element GLM classes sit on. Resetting there
		 * out-specifies our own component rules and silently suppresses
		 * them. Invariant #2 in docs/elementor-compatibility.md.
		 */
		if ( preg_match( '/^html \.elementor-widget-[a-z-]+\s*[,{]\s*$/m', $css, $root_reset ) ) {
			$add( 'R9', 'components.css — the reset targets a widget ROOT (`' . trim( $root_reset[0], " ,{\n" ) . '`); it will suppress GLM component rules. Prefix those rules instead.' );
		}

		// 2. Anything touching Elementor markup needs the prefix.
		preg_match_all( '/^([^{}\n\/]*\.elementor-[^{}\n]*)[,{]\s*$/m', $css, $sel );

		foreach ( $sel[1] as $s ) {
			$s = trim( $s );
			if ( 0 !== strpos( $s, 'html ' ) ) {
				$add( 'R9', "components.css — `{$s}` targets Elementor markup without the `html` prefix; it will lose on load order" );
			}
		}

		// 3. Redundant now that the reset handles inheritance.
		preg_match_all( '/^html (\.glm-[a-z_-]+) \.elementor-heading-title\s*[,{]/m', $css, $redundant );
		foreach ( $redundant[1] as $s ) {
			$add( 'R9', "components.css — `{$s} .elementor-heading-title` is redundant; style `{$s}` alone and let it inherit" );
		}

		// 4. !important should be rare and explained.
		preg_match_all( '/^\s*[a-z-]+\s*:[^;]*!important/m', $css, $imp );
		if ( count( $imp[0] ) > 4 ) {
			$add( 'R9', sprintf( 'components.css uses !important %d times — prefer specificity, which composes', count( $imp[0] ) ) );
		}
	}

	/* ── Site-level ──────────────────────────────────────── */

	/*
	 * HFE widget assets are dequeued because no HFE widget is used. If one
	 * is added back, its styling would be silently missing — so fail here
	 * rather than let it be found visually.
	 */
	if ( function_exists( 'glm_hfe_widget_handles' ) && apply_filters( 'glm_dequeue_hfe_assets', true ) ) {

		$hfe_widget_types = array(
			'navigation-menu', 'hfe-site-title', 'hfe-site-tagline', 'site-logo',
			'copyright', 'hfe-search-button', 'hfe-cart', 'hfe-nav-menu',
			'page-title', 'hfe-post-info', 'hfe-scroll-to-top', 'hfe-breadcrumbs',
		);

		foreach ( glm_audit_documents() as $id => $label ) {
			$raw  = get_post_meta( $id, '_elementor_data', true );
			$tree = json_decode( is_string( $raw ) ? $raw : wp_json_encode( $raw ), true );

			if ( ! is_array( $tree ) ) {
				continue;
			}

			glm_audit_walk(
				$tree,
				function ( $el ) use ( $add, $label, $hfe_widget_types ) {
					$t = $el['widgetType'] ?? '';
					if ( $t && in_array( $t, $hfe_widget_types, true ) ) {
						$add( 'ASSETS', "{$label} uses the HFE widget `{$t}`, but HFE's widget CSS is dequeued — it will render unstyled. Remove the widget, or filter glm_dequeue_hfe_assets to false." );
					}
				}
			);
		}
	}

	// R6 — header and footer must exist as templates.
	foreach ( array( 'type_header' => 'header', 'type_footer' => 'footer' ) as $type => $label ) {
		$found = get_posts(
			array(
				'post_type'      => 'elementor-hf',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ehf_template_type', // phpcs:ignore
				'meta_value'     => $type,               // phpcs:ignore
			)
		);
		if ( ! $found ) {
			$add( 'R6', "No {$label} template found" );
		}
	}

	// R5 — repeating data should not be hardcoded in page content.
	$count = (int) wp_count_posts( 'tort' )->publish;
	foreach ( $pages as $p ) {
		if ( preg_match( '/\b(\d{2,3})\+\s*(active\s+)?mass tort/i', $p->post_content, $m ) ) {
			if ( (int) $m[1] !== $count ) {
				$add( 'R5', "Page '{$p->post_title}' hardcodes '{$m[1]}+' torts; actual count is {$count}. Use [glm_tort_count]." );
			}
		}
	}

	return $findings;
}

/**
 * WP-CLI: wp glm audit
 */
function glm_cli_audit() {

	WP_CLI::log( 'Auditing against learning.md ruleset…' . PHP_EOL );

	$findings = glm_run_audit();

	$rules = array(
		'R1'     => 'Tokens before pixels',
		'R2'     => 'Flexbox Container only',
		'R3'     => 'No rich-text blobs',
		'R5'     => 'Repeating data in the database',
		'R6'     => 'Header/footer are templates',
		'R7'     => 'Name everything in the Navigator',
		'R8'     => 'No duplicated responsive variants',
		'R9'     => 'Custom CSS in the theme',
		'R11'    => 'Ship checklist',
		'R14'    => 'Correct action is the easiest',
		'ASSETS' => 'No external staging hotlinks',
	);

	$total = 0;

	foreach ( $rules as $rule => $title ) {

		$hits = $findings[ $rule ] ?? array();
		$n    = count( $hits );
		$total += $n;

		if ( ! $n ) {
			WP_CLI::log( sprintf( '  %-7s %-38s PASS', $rule, $title ) );
			continue;
		}

		WP_CLI::log( sprintf( '  %-7s %-38s %d issue(s)', $rule, $title, $n ) );

		// Cap the per-rule output but never hide the total.
		foreach ( array_slice( $hits, 0, 8 ) as $h ) {
			WP_CLI::log( '            - ' . $h );
		}
		if ( $n > 8 ) {
			WP_CLI::log( sprintf( '            … and %d more', $n - 8 ) );
		}
	}

	WP_CLI::log( '' );

	if ( $total ) {
		WP_CLI::warning( sprintf( '%d issue(s) found across %d rule(s).', $total, count( $findings ) ) );
	} else {
		WP_CLI::success( 'All audited rules pass.' );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm audit', 'glm_cli_audit' );
}
