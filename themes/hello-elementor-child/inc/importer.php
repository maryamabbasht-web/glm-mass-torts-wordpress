<?php
/**
 * One-click tort importer.
 *
 * Seeds the tort CPT from data/torts.json, which was extracted from
 * source/glmasstorts.html. 40 torts x 8 fields = 320 values; typing those
 * by hand is exactly the grind that introduces drift (R14 — the correct
 * path must be the easy one).
 *
 * IDEMPOTENT. Matching is by an import key stored in post meta, so
 * re-running updates existing torts instead of duplicating them. Safe to
 * run after editing the JSON.
 *
 * Tools → Import Torts
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GLM_IMPORT_KEY  = '_glm_import_key';
const GLM_IMPORT_FILE = '/data/torts.json';

/**
 * WP-CLI: studio wp glm import-torts [--dry-run]
 *
 * The same code path as the admin page, so the two cannot diverge.
 * Scriptable, which means the import is repeatable on a fresh site
 * rather than a sequence of remembered clicks.
 *
 * @param array $args       Positional args (unused).
 * @param array $assoc_args Flags.
 */
function glm_cli_import_torts( $args, $assoc_args ) {

	$dry  = isset( $assoc_args['dry-run'] );
	$seed = glm_read_tort_seed();

	if ( is_wp_error( $seed ) ) {
		WP_CLI::error( $seed->get_error_message() );
	}

	if ( ! function_exists( 'update_field' ) ) {
		WP_CLI::warning( 'ACF inactive — values will be written as raw post meta.' );
	}

	$tally    = array();
	$progress = WP_CLI\Utils\make_progress_bar(
		$dry ? 'Dry run' : 'Importing torts',
		count( $seed )
	);

	foreach ( $seed as $row ) {
		$result           = glm_import_one_tort( $row, $dry );
		$tally[ $result ] = ( $tally[ $result ] ?? 0 ) + 1;
		$progress->tick();
	}

	$progress->finish();

	foreach ( $tally as $action => $count ) {
		WP_CLI::log( sprintf( '  %-14s %d', $action, $count ) );
	}

	if ( ! empty( $tally['failed'] ) ) {
		WP_CLI::error( sprintf( '%d torts failed to import.', $tally['failed'] ) );
	}

	WP_CLI::success(
		$dry
			? 'Dry run complete — nothing was written.'
			: sprintf( 'Import complete. %d torts published.', (int) wp_count_posts( 'tort' )->publish )
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm import-torts', 'glm_cli_import_torts' );
}

/**
 * Register the Tools submenu page.
 */
function glm_register_importer_page() {
	add_management_page(
		'Import Torts',
		'Import Torts',
		'manage_options',
		'glm-import-torts',
		'glm_render_importer_page'
	);
}
add_action( 'admin_menu', 'glm_register_importer_page' );

/**
 * Read and decode the seed file.
 *
 * @return array|WP_Error
 */
function glm_read_tort_seed() {

	$path = GLM_DIR . GLM_IMPORT_FILE;

	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'glm_no_file', 'Cannot read ' . esc_html( $path ) );
	}

	$data = json_decode( file_get_contents( $path ), true ); // phpcs:ignore

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'glm_bad_json', 'Seed file is not valid JSON.' );
	}

	return $data;
}

/**
 * Write one tort. Creates or updates based on the import key.
 *
 * @param array $row     One record from the seed file.
 * @param bool  $dry_run Report only, change nothing.
 * @return string One of 'created', 'updated', 'would-create', 'would-update'.
 */
function glm_import_one_tort( array $row, $dry_run = false ) {

	$key = sanitize_title( $row['title'] );

	$existing = get_posts(
		array(
			'post_type'      => 'tort',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => GLM_IMPORT_KEY,   // phpcs:ignore
			'meta_value'     => $key,             // phpcs:ignore
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( $dry_run ) {
		return $post_id ? 'would-update' : 'would-create';
	}

	$postarr = array(
		'post_type'    => 'tort',
		'post_status'  => 'publish',
		'post_title'   => $row['title'],
		'post_name'    => $key,
		'post_content' => wpautop( $row['description'] ),
		'post_excerpt' => $row['description'],
		'menu_order'   => (int) $row['menu_order'],
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
		return 'failed';
	}

	update_post_meta( $post_id, GLM_IMPORT_KEY, $key );

	// Taxonomies — terms are seeded by taxonomies.php, so these resolve.
	wp_set_object_terms( $post_id, $row['category'], 'tort_category', false );
	wp_set_object_terms( $post_id, $row['status'], 'tort_status', false );

	// ACF fields. update_field() also writes the field-key reference that
	// ACF needs; without it the admin UI shows empty inputs even though
	// the values exist. Fall back to raw meta if ACF is inactive.
	$fields = array(
		'status_label'         => $row['status_label'],
		'pill_suffix'          => $row['pill_suffix'],
		'mdl_reference'        => $row['mdl_reference'],
		'settlement_estimate'  => $row['settlement_estimate'],
		'is_featured'          => ! empty( $row['is_featured'] ) ? 1 : 0,
		'featured_stat_number' => $row['featured_stat_number'],
		'featured_stat_label'  => $row['featured_stat_label'],
	);

	foreach ( $fields as $name => $value ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $name, $value, $post_id );
		} else {
			update_post_meta( $post_id, $name, $value );
		}
	}

	return $action;
}

/**
 * Render the Tools page.
 */
function glm_render_importer_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions.' );
	}

	$seed = glm_read_tort_seed();
	$ran  = false;
	$dry  = false;
	$tally = array();

	if ( isset( $_POST['glm_import_nonce'] ) &&
		wp_verify_nonce( sanitize_key( $_POST['glm_import_nonce'] ), 'glm_import_torts' ) &&
		! is_wp_error( $seed ) ) {

		$dry = isset( $_POST['glm_dry_run'] );
		$ran = true;

		foreach ( $seed as $row ) {
			$result           = glm_import_one_tort( $row, $dry );
			$tally[ $result ] = ( $tally[ $result ] ?? 0 ) + 1;
		}
	}

	$existing_count = (int) wp_count_posts( 'tort' )->publish;
	?>
	<div class="wrap">
		<h1>Import Torts</h1>

		<?php if ( is_wp_error( $seed ) ) : ?>
			<div class="notice notice-error">
				<p><strong>Cannot import:</strong> <?php echo esc_html( $seed->get_error_message() ); ?></p>
			</div>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( ! function_exists( 'update_field' ) ) : ?>
			<div class="notice notice-warning">
				<p><strong>ACF is not active.</strong> Field values will be written as raw
				post meta and will not appear in the editor until ACF is installed.</p>
			</div>
		<?php endif; ?>

		<?php if ( $ran ) : ?>
			<div class="notice notice-success">
				<p>
					<strong><?php echo $dry ? 'Dry run complete.' : 'Import complete.'; ?></strong>
					<?php
					foreach ( $tally as $action => $count ) {
						printf( '%s: %d. ', esc_html( $action ), (int) $count );
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			Seeds the <strong>Mass Torts</strong> post type from
			<code>data/torts.json</code>, extracted from the source HTML.
		</p>

		<table class="widefat striped" style="max-width:640px;margin-bottom:1.5em;">
			<tbody>
				<tr><th>Torts in seed file</th><td><?php echo count( $seed ); ?></td></tr>
				<tr><th>Torts currently published</th><td><?php echo esc_html( $existing_count ); ?></td></tr>
				<tr><th>Featured in seed</th><td><?php echo count( array_filter( $seed, fn( $r ) => ! empty( $r['is_featured'] ) ) ); ?></td></tr>
			</tbody>
		</table>

		<p><strong>This is safe to re-run.</strong> Torts are matched by an
		import key, so existing entries are updated rather than duplicated.
		Manual edits to title, content, or fields <em>will be overwritten</em>
		by a re-run.</p>

		<form method="post">
			<?php wp_nonce_field( 'glm_import_torts', 'glm_import_nonce' ); ?>
			<p>
				<label>
					<input type="checkbox" name="glm_dry_run" value="1" checked>
					Dry run — report what would happen, change nothing
				</label>
			</p>
			<p>
				<button type="submit" class="button button-primary">Run import</button>
			</p>
		</form>
	</div>
	<?php
}
