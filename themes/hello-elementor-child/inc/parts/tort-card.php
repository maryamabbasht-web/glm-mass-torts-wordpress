<?php
/**
 * Tort card — the "loop item".
 *
 * This is the free-tier equivalent of an Elementor Pro Loop Item template:
 * the card is designed ONCE here, and the grid repeats it. Editing this
 * file changes every card everywhere (learning.md R4).
 *
 * Called via get_template_part( 'inc/parts/tort-card', null, $args )
 * from inside a WP_Query loop.
 *
 * Expects in $args:
 *   'featured' (bool) — render the wide two-column variant.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id  = get_the_ID();
$featured = ! empty( $args['featured'] ) && glm_field( 'is_featured', $post_id, false );

// Category — drives the pill colour and its label.
$categories   = get_the_terms( $post_id, 'tort_category' );
$category     = ( is_array( $categories ) && ! empty( $categories ) ) ? $categories[0] : null;
$category_slug = $category ? $category->slug : 'default';
$pill_label    = $category ? $category->name : '';
$pill_suffix   = glm_field( 'pill_suffix', $post_id );

// Status — taxonomy drives the colour, ACF text carries the wording.
$statuses     = get_the_terms( $post_id, 'tort_status' );
$status       = ( is_array( $statuses ) && ! empty( $statuses ) ) ? $statuses[0] : null;
$status_slug  = $status ? $status->slug : 'active';
$status_label = glm_field( 'status_label', $post_id, $status ? $status->name : '' );

$mdl        = glm_field( 'mdl_reference', $post_id );
$settlement = glm_field( 'settlement_estimate', $post_id );

$classes = array( 'glm-tort-card' );
if ( $featured ) {
	$classes[] = 'glm-tort-card--featured';
}
?>

<a href="<?php the_permalink(); ?>"
   class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
   data-category="<?php echo esc_attr( $category_slug ); ?>">

	<div class="glm-tort-card__body">

		<div class="glm-tort-card__meta">
			<?php if ( $pill_label ) : ?>
				<span class="glm-pill glm-pill--<?php echo esc_attr( $category_slug ); ?>">
					<?php
					echo esc_html( $pill_label );
					if ( $pill_suffix ) {
						echo ' ' . esc_html( $pill_suffix );
					}
					?>
				</span>
			<?php endif; ?>

			<?php if ( $status_label ) : ?>
				<span class="glm-status glm-status--<?php echo esc_attr( $status_slug ); ?>">
					<span class="glm-status__dot" aria-hidden="true"></span>
					<?php echo esc_html( $status_label ); ?>
				</span>
			<?php endif; ?>
		</div>

		<h3 class="glm-tort-card__title"><?php the_title(); ?></h3>

		<?php if ( has_excerpt() || get_the_content() ) : ?>
			<p class="glm-tort-card__desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>

		<?php if ( $mdl || $settlement ) : ?>
			<div class="glm-tort-card__footer">
				<?php if ( $mdl ) : ?>
					<span class="glm-tort-card__mdl"><?php echo esc_html( $mdl ); ?></span>
				<?php endif; ?>
				<?php if ( $settlement ) : ?>
					<span class="glm-tort-card__settlement"><?php echo esc_html( $settlement ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>

	<?php
	// Featured variant only: the pulled-out statistic panel.
	if ( $featured ) :
		$stat_number = glm_field( 'featured_stat_number', $post_id );
		$stat_label  = glm_field( 'featured_stat_label', $post_id );

		if ( $stat_number ) :
			?>
			<div class="glm-tort-card__stat">
				<span class="glm-tort-card__stat-num"><?php echo esc_html( $stat_number ); ?></span>
				<?php if ( $stat_label ) : ?>
					<span class="glm-tort-card__stat-lbl"><?php echo esc_html( $stat_label ); ?></span>
				<?php endif; ?>
			</div>
			<?php
		endif;
	endif;
	?>
</a>
