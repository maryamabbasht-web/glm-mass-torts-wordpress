<?php
/**
 * Renderers for the result and location post types, plus their importers.
 *
 * These follow the same reasoning as the tort grid (R5): the data lives in
 * the database and is rendered by one template, so adding an office or
 * updating a settlement figure never means editing a layout.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Importers
 * ---------------------------------------------------------------------- */

/**
 * Generic seeder for the simple post types.
 *
 * @param string $post_type CPT name.
 * @param string $file      Filename inside data/.
 * @param array  $fields    ACF field names to copy from each record.
 * @param bool   $dry_run   Report only.
 * @return array|WP_Error Tally.
 */
function glm_import_simple( $post_type, $file, array $fields, $dry_run = false ) {

	$path = GLM_DIR . '/data/' . $file;

	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'glm_no_file', 'Cannot read ' . $path );
	}

	$rows = json_decode( file_get_contents( $path ), true ); // phpcs:ignore

	if ( ! is_array( $rows ) ) {
		return new WP_Error( 'glm_bad_json', $file . ' is not valid JSON.' );
	}

	$tally = array();

	foreach ( $rows as $row ) {

		$key = sanitize_title( $row['title'] );

		$existing = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_glm_import_key', // phpcs:ignore
				'meta_value'     => $key,              // phpcs:ignore
			)
		);

		$post_id = $existing ? (int) $existing[0] : 0;

		if ( $dry_run ) {
			$action           = $post_id ? 'would-update' : 'would-create';
			$tally[ $action ] = ( $tally[ $action ] ?? 0 ) + 1;
			continue;
		}

		$postarr = array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'post_title'  => $row['title'],
			'post_name'   => $key,
			'menu_order'  => (int) ( $row['menu_order'] ?? 0 ),
		);

		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			wp_update_post( $postarr );
			$action = 'updated';
		} else {
			$post_id = wp_insert_post( $postarr );
			$action  = 'created';
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$tally['failed'] = ( $tally['failed'] ?? 0 ) + 1;
			continue;
		}

		update_post_meta( $post_id, '_glm_import_key', $key );

		foreach ( $fields as $f ) {
			$value = $row[ $f ] ?? '';
			if ( function_exists( 'update_field' ) ) {
				update_field( $f, $value, $post_id );
			} else {
				update_post_meta( $post_id, $f, $value );
			}
		}

		$tally[ $action ] = ( $tally[ $action ] ?? 0 ) + 1;
	}

	return $tally;
}

/**
 * WP-CLI: wp glm import-content [--dry-run]
 */
function glm_cli_import_content( $args, $assoc_args ) {

	$dry = isset( $assoc_args['dry-run'] );

	$jobs = array(
		'result'   => array( 'results.json',   array( 'amount', 'result_description' ) ),
		'location' => array( 'locations.json', array( 'state', 'address', 'phone', 'phone_secondary' ) ),
	);

	foreach ( $jobs as $post_type => $job ) {
		list( $file, $fields ) = $job;

		$tally = glm_import_simple( $post_type, $file, $fields, $dry );

		if ( is_wp_error( $tally ) ) {
			WP_CLI::warning( $post_type . ': ' . $tally->get_error_message() );
			continue;
		}

		$parts = array();
		foreach ( $tally as $k => $v ) {
			$parts[] = "{$k}={$v}";
		}
		WP_CLI::log( sprintf( '  %-10s %s', $post_type, implode( '  ', $parts ) ) );
	}

	WP_CLI::success( $dry ? 'Dry run complete.' : 'Content imported.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm import-content', 'glm_cli_import_content' );
}

/* -------------------------------------------------------------------------
 * Renderers
 * ---------------------------------------------------------------------- */

/**
 * [glm_results] — the verdict / settlement list.
 *
 * @return string
 */
function glm_results_shortcode() {

	$results = get_posts(
		array(
			'post_type'      => 'result',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( ! $results ) {
		return '';
	}

	ob_start();
	echo '<ul class="glm-results">';

	foreach ( $results as $r ) {
		printf(
			'<li class="glm-result">'
				. '<div class="glm-result__amount">%s</div>'
				. '<div class="glm-result__info">'
					. '<div class="glm-result__case">%s</div>'
					. '<div class="glm-result__desc">%s</div>'
				. '</div>'
			. '</li>',
			esc_html( glm_field( 'amount', $r->ID ) ),
			esc_html( $r->post_title ),
			esc_html( glm_field( 'result_description', $r->ID ) )
		);
	}

	echo '</ul>';
	return ob_get_clean();
}
add_shortcode( 'glm_results', 'glm_results_shortcode' );

/**
 * Address, safe for output.
 *
 * The ACF field is a textarea with new_lines => br, so the STORED value
 * already contains "<br />". The previous nl2br( esc_html() ) escaped that
 * into visible "&lt;br /&gt;" text and then added a second break, so every
 * address on the site showed the tag as literal characters.
 *
 * wp_kses with a <br>-only allowlist keeps the break and escapes anything
 * else, which is safe whether the value arrives with tags or with newlines.
 *
 * @param string $address Raw field value.
 * @return string
 */
function glm_format_address( $address ) {
	return wp_kses( (string) $address, array( 'br' => array() ) );
}

/**
 * Address as a single line, for a maps query or an aria-label.
 *
 * @param string $address Raw field value.
 * @return string
 */
function glm_address_oneline( $address ) {
	$flat = preg_replace( '#<br\s*/?>#i', ', ', (string) $address );
	$flat = wp_strip_all_tags( $flat );
	return trim( preg_replace( '/\s+/', ' ', $flat ) );
}

/**
 * Inline SVG icon.
 *
 * Inline rather than a font class: Font Awesome's `solid` stylesheet is not
 * enqueued (only core + brands), so <i class="fas …"> would render nothing.
 * Two paths cost less than another stylesheet.
 *
 * @param string $name pin|phone|arrow.
 * @return string
 */
function glm_icon( $name ) {

	$paths = array(
		'pin'   => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle>',
		'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"></path>',
		'arrow' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="glm-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none"'
		. ' stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
		. ' aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * [glm_locations] — offices, grouped by state.
 *
 * ONE PARAMETER, AND ONLY FOR MARKUP
 *
 * There are exactly two call sites: this page and the site footer. Their
 * visual differences are handled by contextual CSS (`.glm-footer__offices`),
 * which needs no parameter at all.
 *
 * `layout` exists because two differences are STRUCTURAL, and CSS cannot
 * express either:
 *
 *   1. Heading levels. The page needs <h2>/<h3> beneath its <h1>. The
 *      footer already sits under an <h4>, so emitting <h2> there would
 *      send the document outline backwards.
 *   2. The directions link. Useful on the page; in the footer it would
 *      add eight outbound links to every page on the site.
 *
 *   layout="list"  (default) — footer: divs, no directions
 *   layout="cards"           — page:   headings, icons, directions
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function glm_locations_shortcode( $atts ) {

	$atts = shortcode_atts( array( 'layout' => 'list' ), $atts, 'glm_locations' );
	$cards = ( 'cards' === $atts['layout'] );

	$offices = get_posts(
		array(
			'post_type'      => 'location',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( ! $offices ) {
		return '';
	}

	// Group by state, preserving menu_order within each group.
	$by_state = array();
	foreach ( $offices as $o ) {
		$state                = glm_field( 'state', $o->ID, 'Other' );
		$by_state[ $state ][] = $o;
	}

	ob_start();

	printf( '<div class="glm-locations%s">', $cards ? ' glm-locations--cards' : '' );

	foreach ( $by_state as $state => $group ) {

		echo '<div class="glm-loc-state">';

		printf(
			$cards ? '<h2 class="glm-loc-state__name">%s</h2>' : '<div class="glm-loc-state__name">%s</div>',
			esc_html( $state )
		);

		echo '<div class="glm-loc-state__offices">';

		foreach ( $group as $o ) {

			$address = glm_field( 'address', $o->ID );
			$phone   = glm_field( 'phone', $o->ID );
			$phone2  = glm_field( 'phone_secondary', $o->ID );

			echo '<div class="glm-office">';

			printf(
				$cards ? '<h3 class="glm-office__city">%s</h3>' : '<div class="glm-office__city">%s</div>',
				esc_html( $o->post_title )
			);

			printf(
				'<div class="glm-office__address">%s%s</div>',
				$cards ? glm_icon( 'pin' ) : '',
				glm_format_address( $address )
			);

			foreach ( array_filter( array( $phone, $phone2 ) ) as $p ) {
				printf(
					'<a class="glm-office__phone" href="tel:%s">%s%s</a>',
					esc_attr( preg_replace( '/[^0-9+]/', '', $p ) ),
					$cards ? glm_icon( 'phone' ) : '',
					esc_html( $p )
				);
			}

			/*
			 * Directions built from the address already on record — no new
			 * field, no change to how offices are managed.
			 */
			if ( $cards ) {
				$query = glm_address_oneline( $address );
				if ( $query ) {
					printf(
						'<a class="glm-office__directions" href="https://www.google.com/maps/search/?api=1&query=%s"'
							. ' target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text">%s</span>%s</a>',
						rawurlencode( $o->post_title . ', ' . $query ),
						esc_html__( 'Get directions', 'glm' ),
						/* translators: %s: office city. */
						esc_html( sprintf( __( 'to our %s office', 'glm' ), $o->post_title ) ),
						glm_icon( 'arrow' )
					);
				}
			}

			echo '</div>';
		}

		echo '</div></div>';
	}

	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'glm_locations', 'glm_locations_shortcode' );
