<?php
/**
 * Tort archive — /mass-torts/
 *
 * The full tabbed browser. Also renders the category archives at
 * /mass-torts/type/{slug}/, as a single filtered grid with no tab bar —
 * but only because taxonomy-tort_category.php delegates here.
 *
 * WordPress does NOT fall back from a taxonomy archive to
 * archive-{post_type}.php. Deleting that delegating file silently sends
 * category archives to the parent theme's generic archive.php.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$is_category = is_tax( 'tort_category' );
$term        = $is_category ? get_queried_object() : null;
$total       = wp_count_posts( 'tort' )->publish;
?>

<div class="glm-archive">

	<header class="glm-archive__header">
		<span class="glm-eyebrow">Active Litigation</span>

		<h1 class="glm-archive__title">
			<?php if ( $term ) : ?>
				<?php echo esc_html( $term->name ); ?>
			<?php else : ?>
				<?php
				// Computed, never typed. The source hardcoded "35" in two
				// places and was wrong by five (R5).
				printf(
					'%d Active <em>Mass Tort Cases</em>',
					(int) $total
				);
				?>
			<?php endif; ?>
		</h1>

		<?php
		if ( $term && $term->description ) {
			echo '<p class="glm-archive__desc">' . esc_html( $term->description ) . '</p>';
		}
		?>
	</header>

	<?php
	if ( $is_category ) {
		// One category: a flat grid, no tab bar.
		echo do_shortcode(
			sprintf(
				'[glm_tort_grid tabs="no" heading="no" category="%s"]',
				esc_attr( $term->slug )
			)
		);
	} else {
		// Everything: the full tabbed browser.
		echo do_shortcode( '[glm_tort_grid]' );
	}
	?>

	<footer class="glm-archive__disclaimer">
		<p>
			Attorney Advertising. Prior results do not guarantee similar outcomes.
			Case values and pending-case counts reflect national MDL litigation and
			industry settlements, and vary by individual circumstance.
		</p>
	</footer>

</div>

<?php
get_footer();
