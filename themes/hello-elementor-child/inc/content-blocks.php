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
 * [glm_locations] — offices, grouped by state.
 *
 * @return string
 */
function glm_locations_shortcode() {

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
	echo '<div class="glm-locations">';

	foreach ( $by_state as $state => $group ) {
		printf( '<div class="glm-loc-state"><div class="glm-loc-state__name">%s</div><div class="glm-loc-state__offices">', esc_html( $state ) );

		foreach ( $group as $o ) {
			$phone  = glm_field( 'phone', $o->ID );
			$phone2 = glm_field( 'phone_secondary', $o->ID );

			echo '<div class="glm-office">';
			printf( '<div class="glm-office__city">%s</div>', esc_html( $o->post_title ) );
			printf( '<div class="glm-office__address">%s</div>', nl2br( esc_html( glm_field( 'address', $o->ID ) ) ) );

			if ( $phone ) {
				printf(
					'<a class="glm-office__phone" href="tel:%s">%s</a>',
					esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ),
					esc_html( $phone )
				);
			}
			if ( $phone2 ) {
				printf(
					'<a class="glm-office__phone" href="tel:%s">%s</a>',
					esc_attr( preg_replace( '/[^0-9+]/', '', $phone2 ) ),
					esc_html( $phone2 )
				);
			}

			echo '</div>';
		}

		echo '</div></div>';
	}

	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'glm_locations', 'glm_locations_shortcode' );
