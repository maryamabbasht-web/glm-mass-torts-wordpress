<?php
/**
 * `wp glm install` — one command from a bare WordPress to a working site.
 *
 * WHAT THIS IS, AND WHAT IT IS NOT
 *
 * This is an ORCHESTRATOR. It contains no build logic of its own: every step
 * delegates to a command that already exists and is already tested. If a step
 * misbehaves, the bug is in that command, not here.
 *
 * Onboarding previously meant knowing nine commands and the order they had to
 * run in — an order with real dependencies that are not obvious from the
 * command names:
 *
 *   - `build-pages` reads tort_category with hide_empty => true, so the menu
 *     gets no category items unless torts already exist.
 *   - `apply-kit` and `build-header-footer` both read glm_logo_id, so brand
 *     assets must be imported before either runs.
 *
 * Getting that order wrong produces a site that looks built but is missing
 * pieces, with nothing to indicate why.
 *
 * SAFE BY DEFAULT
 *
 * A plain `wp glm install` never overwrites anything a person may have edited.
 * Content importers are skipped when content already exists, and the builders
 * are called without --force, which makes them create-if-missing. Destroying
 * work requires --rebuild, explicitly.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GLM_MIN_PHP = '7.4';
const GLM_MIN_WP  = '6.4';

/**
 * Read the pinned plugin manifest.
 *
 * @return array|WP_Error
 */
function glm_plugin_manifest() {

	$path = GLM_DIR . '/data/plugins.json';

	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'glm_no_manifest', 'Cannot read data/plugins.json' );
	}

	$data = json_decode( file_get_contents( $path ), true ); // phpcs:ignore

	if ( ! is_array( $data ) || empty( $data['required'] ) ) {
		return new WP_Error( 'glm_bad_manifest', 'data/plugins.json is not valid or has no "required" list.' );
	}

	return $data['required'];
}

/**
 * Compare installed plugins against the manifest.
 *
 * Shared with the audit so the two cannot disagree about what "correct" means.
 *
 * @return array slug => ['name','pinned','installed','active','state']
 */
function glm_plugin_status() {

	$manifest = glm_plugin_manifest();

	if ( is_wp_error( $manifest ) ) {
		return array();
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$installed = get_plugins();
	$out       = array();

	foreach ( $manifest as $p ) {

		$file    = '';
		$version = '';

		foreach ( $installed as $rel => $info ) {
			if ( dirname( $rel ) === $p['slug'] ) {
				$file    = $rel;
				$version = $info['Version'];
				break;
			}
		}

		if ( ! $file ) {
			$state = 'missing';
		} elseif ( ! is_plugin_active( $file ) ) {
			$state = 'inactive';
		} elseif ( version_compare( $version, $p['version'], '!=' ) ) {
			$state = 'version-drift';
		} else {
			$state = 'ok';
		}

		$out[ $p['slug'] ] = array(
			'name'      => $p['name'],
			'pinned'    => $p['version'],
			'installed' => $version,
			'file'      => $file,
			'state'     => $state,
		);
	}

	return $out;
}

/**
 * Preflight checks.
 *
 * @return array List of failure messages; empty means good to go.
 */
function glm_install_preflight() {

	$problems = array();

	if ( version_compare( PHP_VERSION, GLM_MIN_PHP, '<' ) ) {
		$problems[] = sprintf( 'PHP %s or newer required; running %s.', GLM_MIN_PHP, PHP_VERSION );
	}

	if ( version_compare( get_bloginfo( 'version' ), GLM_MIN_WP, '<' ) ) {
		$problems[] = sprintf( 'WordPress %s or newer required; running %s.', GLM_MIN_WP, get_bloginfo( 'version' ) );
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || ! is_writable( $uploads['basedir'] ) ) {
		$problems[] = 'Uploads directory is not writable: ' . $uploads['basedir'];
	}

	if ( ! is_readable( GLM_DIR . '/data/plugins.json' ) ) {
		$problems[] = 'data/plugins.json is missing.';
	}

	// The theme must be the active one, or its CPTs never register.
	if ( 'hello-elementor-child' !== get_stylesheet() ) {
		$problems[] = sprintf(
			'Active theme is "%s"; expected hello-elementor-child. Run: wp theme activate hello-elementor-child',
			get_stylesheet()
		);
	}

	return $problems;
}

/**
 * The build steps, in dependency order.
 *
 * `force` is the argument string added under --rebuild.
 * `skip_if` returns true when the step's output already exists, which is how
 * a normal install avoids overwriting content.
 *
 * @return array
 */
function glm_install_steps() {

	return array(
		array(
			'label' => 'Import brand assets',
			'cmd'   => 'glm import-brand',
			'note'  => 'logo, favicon, states banner — hash-checked, safe to repeat',
		),
		array(
			'label' => 'Apply Elementor kit',
			'cmd'   => 'glm apply-kit',
			'note'  => 'design tokens from PHP; needs glm_logo_id from the step above',
		),
		array(
			'label'   => 'Import torts',
			'cmd'     => 'glm import-torts',
			'note'    => '40 torts from data/torts.json',
			'skip_if' => function () {
				return wp_count_posts( 'tort' )->publish > 0;
			},
			'skip_msg' => 'torts already present — use --rebuild to re-import',
		),
		array(
			'label'   => 'Import offices and results',
			'cmd'     => 'glm import-content',
			'note'    => '8 offices, 4 results',
			'skip_if' => function () {
				return wp_count_posts( 'location' )->publish > 0
					|| wp_count_posts( 'result' )->publish > 0;
			},
			'skip_msg' => 'content already present — use --rebuild to re-import',
		),
		array(
			'label' => 'Build Elementor sections',
			'cmd'   => 'glm build-sections',
			'force' => '--force --overwrite-edited',
			'note'  => 'hero, stats, about, divisions, contact',
		),
		array(
			'label' => 'Build contact form',
			'cmd'   => 'glm build-form',
			'force' => '--force',
			'note'  => 'CF7 case evaluation form',
		),
		array(
			'label' => 'Build pages and menu',
			'cmd'   => 'glm build-pages',
			'force' => '--force',
			'note'  => '9 pages + Primary menu; needs torts for the category items',
		),
		array(
			'label' => 'Build header and footer',
			'cmd'   => 'glm build-header-footer',
			'force' => '--force',
			'note'  => 'HFE templates; needs glm_logo_id',
		),
		array(
			'label' => 'Run ruleset audit',
			'cmd'   => 'glm audit',
			'note'  => 'reports only; never fails the install',
		),
	);
}

/**
 * Run a WP-CLI command in-process.
 *
 * WHY NOT `launch => true`
 *
 * Spawning a subprocess would re-bootstrap WordPress, which is the textbook
 * way to pick up plugins activated earlier in the same run. It does not work
 * here: WP-CLI builds the child command line without quoting the path to its
 * own binary, and under WordPress Studio that path is
 * `C:\Program Files\WindowsApps\…`. Every spawned step failed with
 * "Could not open input file: C:\Program".
 *
 * In-process avoids that entirely, and is faster. The trade-off is that a
 * plugin activated during this run is not LOADED in this process — its
 * plugins_loaded and init hooks have already passed. See the two-pass note
 * in glm_cli_install(): on a genuinely fresh site the command asks to be run
 * a second time, which costs one extra invocation exactly once.
 *
 * @param string $command Command and arguments.
 * @return array [ok, output]
 */
function glm_install_run( $command ) {

	$result = WP_CLI::runcommand(
		$command,
		array(
			'return'     => 'all',
			'launch'     => false,
			'exit_error' => false,
		)
	);

	return array(
		0 === (int) $result->return_code,
		trim( $result->stdout . "\n" . $result->stderr ),
	);
}

/**
 * Install and activate the pinned plugins.
 *
 * @param bool $rebuild Force pinned versions over whatever is installed.
 * @return array [ok, lines]
 */
function glm_install_plugins( $rebuild = false ) {

	$status = glm_plugin_status();
	$lines  = array();
	$ok     = true;

	foreach ( $status as $slug => $p ) {

		switch ( $p['state'] ) {

			case 'ok':
				$lines[] = sprintf( '    %-28s %s  already correct', $slug, $p['pinned'] );
				break;

			case 'inactive':
				list( $good, $out ) = glm_install_run( 'plugin activate ' . $slug );
				$ok      = $ok && $good;
				$lines[] = sprintf( '    %-28s %s  activated', $slug, $p['installed'] );
				break;

			case 'missing':
				list( $good, $out ) = glm_install_run(
					sprintf( 'plugin install %s --version=%s --activate', $slug, $p['pinned'] )
				);
				$ok      = $ok && $good;
				$lines[] = sprintf(
					'    %-28s %s  %s',
					$slug,
					$p['pinned'],
					$good ? 'installed + activated' : 'FAILED: ' . substr( $out, 0, 80 )
				);
				break;

			case 'version-drift':
				if ( $rebuild ) {
					list( $good, $out ) = glm_install_run(
						sprintf( 'plugin install %s --version=%s --force --activate', $slug, $p['pinned'] )
					);
					$ok      = $ok && $good;
					$lines[] = sprintf( '    %-28s %s  pinned (was %s)', $slug, $p['pinned'], $p['installed'] );
				} else {
					$lines[] = sprintf(
						'    %-28s %s  DRIFT: pinned %s — left alone, use --rebuild to pin',
						$slug,
						$p['installed'],
						$p['pinned']
					);
				}
				break;
		}
	}

	return array( $ok, $lines );
}

/**
 * Is this site explicitly declared as production?
 *
 * Deliberately checks for an EXPLICIT declaration rather than using
 * wp_get_environment_type(), which returns 'production' when nothing is set —
 * which is the normal state of a Studio site, and would block --rebuild on
 * every local install.
 *
 * @return bool
 */
function glm_is_declared_production() {

	$env = getenv( 'WP_ENVIRONMENT_TYPE' );

	if ( ! $env && defined( 'WP_ENVIRONMENT_TYPE' ) ) {
		$env = WP_ENVIRONMENT_TYPE;
	}

	return 'production' === $env;
}

/**
 * WP-CLI: wp glm install [--rebuild] [--skip-plugins] [--dry-run] [--yes]
 *
 * ## OPTIONS
 *
 * [--rebuild]
 * : Regenerate everything, overwriting generated content and any Elementor
 *   edits. Refuses to run when the site declares itself production unless
 *   --yes is also passed.
 *
 * [--skip-plugin-install]
 * : Skip plugin installation. Every other step still runs. For hosts that
 *   manage plugins outside the repository.
 *
 *   NOT named --skip-plugins: that is a WP-CLI GLOBAL flag which tells
 *   WP-CLI not to LOAD plugins at all. It is intercepted before a command
 *   ever sees it, and using it here would leave Elementor and ACF unloaded,
 *   failing every build step.
 *
 * [--dry-run]
 * : Print the execution plan and change nothing.
 *
 * [--yes]
 * : Confirm a destructive --rebuild on a site declared as production.
 *
 * @param array $args       Positional args (unused).
 * @param array $assoc_args Flags.
 */
function glm_cli_install( $args, $assoc_args ) {

	$rebuild      = isset( $assoc_args['rebuild'] );
	$skip_plugins = isset( $assoc_args['skip-plugin-install'] );
	$dry_run      = isset( $assoc_args['dry-run'] );
	$yes          = isset( $assoc_args['yes'] );

	/*
	 * Catch WP-CLI's own --skip-plugins.
	 *
	 * It is a global flag, intercepted before this command runs, and it stops
	 * WordPress loading plugins at all — so Elementor's classes and ACF's
	 * update_field() would be missing and every build step would fail in a
	 * way that looks like our bug rather than a misused flag.
	 */
	$runner = class_exists( 'WP_CLI' ) ? WP_CLI::get_runner() : null;

	if ( $runner && ! empty( $runner->config['skip-plugins'] ) ) {
		WP_CLI::error(
			"--skip-plugins is a WP-CLI global that stops plugins loading entirely;\n"
			. 'the build steps need Elementor and ACF. '
			. 'Use --skip-plugin-install to skip installing them instead.'
		);
	}

	$mode = $dry_run ? 'DRY RUN' : ( $rebuild ? 'REBUILD' : 'INSTALL' );

	WP_CLI::log( '' );
	WP_CLI::log( sprintf( 'GLM %s', $mode ) );
	WP_CLI::log( str_repeat( '─', 64 ) );

	/* ── Production guard ─────────────────────────────────── */

	if ( $rebuild && ! $dry_run && glm_is_declared_production() && ! $yes ) {
		WP_CLI::error(
			"This site declares WP_ENVIRONMENT_TYPE=production.\n"
			. '--rebuild overwrites generated content and Elementor edits. '
			. 'Re-run with --yes if that is genuinely intended.'
		);
	}

	/* ── 1. Preflight ─────────────────────────────────────── */

	WP_CLI::log( '' );
	WP_CLI::log( '1. Preflight' );

	$problems = glm_install_preflight();

	if ( $problems ) {
		foreach ( $problems as $p ) {
			WP_CLI::log( '    ✗ ' . $p );
		}
		WP_CLI::error( 'Preflight failed. Nothing was changed.' );
	}

	WP_CLI::log( sprintf( '    ✓ PHP %s, WordPress %s', PHP_VERSION, get_bloginfo( 'version' ) ) );
	WP_CLI::log( '    ✓ uploads writable' );
	WP_CLI::log( '    ✓ hello-elementor-child active' );

	/* ── 2. Plugins ───────────────────────────────────────── */

	WP_CLI::log( '' );
	WP_CLI::log( '2. Plugins' . ( $skip_plugins ? '  — skipped (--skip-plugin-install)' : '' ) );

	if ( ! $skip_plugins ) {

		$status = glm_plugin_status();

		if ( $dry_run ) {
			foreach ( $status as $slug => $p ) {
				WP_CLI::log(
					sprintf(
						'    %-28s %-9s would %s',
						$slug,
						$p['pinned'],
						'ok' === $p['state'] ? 'do nothing'
							: ( 'missing' === $p['state'] ? 'install + activate'
							: ( 'inactive' === $p['state'] ? 'activate'
							: ( $rebuild ? 'pin over ' . $p['installed'] : 'report drift (' . $p['installed'] . ')' ) ) )
					)
				);
			}
		} else {

			$before = glm_plugin_status();

			list( $ok, $lines ) = glm_install_plugins( $rebuild );
			foreach ( $lines as $l ) {
				WP_CLI::log( $l );
			}

			if ( ! $ok ) {
				WP_CLI::warning( 'One or more plugins failed to install. Later steps may fail.' );
			}

			/*
			 * If anything was just installed or activated, stop here.
			 *
			 * The build steps run in this same PHP process, which booted
			 * before those plugins existed — their init hooks have already
			 * passed, so Elementor's classes and ACF's update_field() are
			 * genuinely unavailable. Continuing would produce a half-built
			 * site and a pile of confusing failures.
			 *
			 * Only ever happens on a fresh site, and only once.
			 */
			$activated = array();
			foreach ( $before as $slug => $p ) {
				if ( in_array( $p['state'], array( 'missing', 'inactive' ), true ) ) {
					$activated[] = $slug;
				}
			}

			if ( $activated ) {
				WP_CLI::log( '' );
				WP_CLI::log( str_repeat( '─', 64 ) );
				WP_CLI::log( '  Newly activated: ' . implode( ', ', $activated ) );
				WP_CLI::log( '  WordPress must reload before these can be used.' );
				WP_CLI::log( '' );
				WP_CLI::success( 'Plugins ready. Run `wp glm install` once more to build the site.' );
				return;
			}
		}
	}

	/* ── 3. Theme + rewrites ──────────────────────────────── */

	WP_CLI::log( '' );
	WP_CLI::log( '3. Theme and permalinks' );

	if ( $dry_run ) {
		WP_CLI::log( '    would flush rewrite rules' );
	} else {
		glm_install_run( 'rewrite flush --hard' );
		WP_CLI::log( '    ✓ rewrite rules flushed' );
	}

	/* ── 4. Build steps ───────────────────────────────────── */

	WP_CLI::log( '' );
	WP_CLI::log( '4. Build' );

	$failed  = 0;
	$skipped = 0;
	$n       = 0;

	foreach ( glm_install_steps() as $step ) {

		$n++;
		$cmd = $step['cmd'];

		if ( $rebuild && ! empty( $step['force'] ) ) {
			$cmd .= ' ' . $step['force'];
		}

		// Content importers are skipped when their content already exists,
		// unless --rebuild. This is what "never overwrite" means in practice.
		$skip = ! $rebuild
			&& ! empty( $step['skip_if'] )
			&& call_user_func( $step['skip_if'] );

		if ( $dry_run ) {
			WP_CLI::log(
				sprintf(
					'    %d. %-28s %s',
					$n,
					$step['label'],
					$skip ? 'SKIP — ' . $step['skip_msg'] : 'wp ' . $cmd
				)
			);
			continue;
		}

		if ( $skip ) {
			$skipped++;
			WP_CLI::log( sprintf( '    %d. %-28s skipped (%s)', $n, $step['label'], $step['skip_msg'] ) );
			continue;
		}

		list( $ok, $out ) = glm_install_run( $cmd );

		if ( $ok ) {
			WP_CLI::log( sprintf( '    %d. %-28s ✓', $n, $step['label'] ) );
		} else {
			$failed++;
			WP_CLI::log( sprintf( '    %d. %-28s ✗ FAILED', $n, $step['label'] ) );
			foreach ( array_slice( explode( "\n", $out ), 0, 4 ) as $line ) {
				if ( trim( $line ) ) {
					WP_CLI::log( '         ' . trim( $line ) );
				}
			}
		}
	}

	/* ── 5. Summary ───────────────────────────────────────── */

	WP_CLI::log( '' );
	WP_CLI::log( str_repeat( '─', 64 ) );

	if ( $dry_run ) {
		WP_CLI::success( 'Dry run complete. Nothing was changed.' );
		return;
	}

	WP_CLI::log(
		sprintf(
			'  torts %d   offices %d   results %d   pages %d',
			wp_count_posts( 'tort' )->publish,
			wp_count_posts( 'location' )->publish,
			wp_count_posts( 'result' )->publish,
			wp_count_posts( 'page' )->publish
		)
	);
	WP_CLI::log( sprintf( '  site: %s', home_url( '/' ) ) );

	if ( $skipped ) {
		WP_CLI::log( sprintf( '  %d step(s) skipped to protect existing content.', $skipped ) );
	}

	if ( $failed ) {
		WP_CLI::error( sprintf( '%d step(s) failed. See the output above.', $failed ) );
	}

	WP_CLI::success( 'Site ready.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm install', 'glm_cli_install' );
}
