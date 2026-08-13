<?php
/**
 * Tort category archive — /mass-torts/type/{slug}/
 *
 * This file exists because WordPress's template hierarchy does NOT fall
 * back from a taxonomy archive to archive-{post_type}.php. The order is:
 *
 *   taxonomy-{taxonomy}-{term}.php
 *   taxonomy-{taxonomy}.php      <- this file
 *   taxonomy.php
 *   archive.php                  <- what it was hitting instead
 *   index.php
 *
 * Without it, /mass-torts/type/pharma/ rendered the parent theme's
 * generic archive: wrong heading, zero tort cards.
 *
 * Delegates rather than duplicates, so there is one template to edit
 * (learning.md R4).
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_stylesheet_directory() . '/archive-tort.php';
