<?php
/**
 * Single tort template.
 *
 * ONE file. FORTY pages. Every tort in the CPT renders through this
 * template at /mass-torts/{slug}/, which is the highest-value outcome of
 * this migration — 40 landing pages generated rather than built.
 *
 * Add tort #41 and page #41 exists: URL, sitemap entry, and a slot in
 * every grid and menu. No layout is edited.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();

	$categories = get_the_terms( $post_id, 'tort_category' );
	$category   = ( is_array( $categories ) && ! empty( $categories ) ) ? $categories[0] : null;

	$statuses    = get_the_terms( $post_id, 'tort_status' );
	$status      = ( is_array( $statuses ) && ! empty( $statuses ) ) ? $statuses[0] : null;
	$status_slug = $status ? $status->slug : 'active';

	$status_label = glm_field( 'status_label', $post_id, $status ? $status->name : '' );
	$mdl          = glm_field( 'mdl_reference', $post_id );
	$settlement   = glm_field( 'settlement_estimate', $post_id );
	?>

	<article <?php post_class( 'glm-tort-single' ); ?>>

		<header class="glm-tort-single__header">

			<nav class="glm-breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span aria-hidden="true">›</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'tort' ) ); ?>">Mass Torts</a>
				<?php if ( $category ) : ?>
					<span aria-hidden="true">›</span>
					<a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</a>
				<?php endif; ?>
			</nav>

			<div class="glm-tort-single__meta">
				<?php if ( $category ) : ?>
					<span class="glm-pill glm-pill--<?php echo esc_attr( $category->slug ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $status_label ) : ?>
					<span class="glm-status glm-status--<?php echo esc_attr( $status_slug ); ?>">
						<span class="glm-status__dot" aria-hidden="true"></span>
						<?php echo esc_html( $status_label ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h1 class="glm-tort-single__title"><?php the_title(); ?></h1>

			<?php if ( $mdl || $settlement ) : ?>
				<dl class="glm-tort-single__facts">
					<?php if ( $mdl ) : ?>
						<div class="glm-fact">
							<dt>Litigation</dt>
							<dd><?php echo esc_html( $mdl ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $settlement ) : ?>
						<div class="glm-fact">
							<dt>Potential Recovery</dt>
							<dd><?php echo esc_html( $settlement ); ?></dd>
						</div>
					<?php endif; ?>
				</dl>
			<?php endif; ?>
		</header>

		<div class="glm-tort-single__content">
			<?php the_content(); ?>
		</div>

		<?php
		/**
		 * Case evaluation form — the conversion point on every tort page.
		 *
		 * Set in Phase 5 once the form plugin is chosen, e.g.
		 *   add_filter( 'glm_case_form_shortcode', fn() => '[fluentform id="1"]' );
		 *
		 * Filtered rather than hardcoded so swapping form plugins never
		 * means editing 40 pages — or this template twice.
		 */
		$form_shortcode = apply_filters( 'glm_case_form_shortcode', '' );

		if ( $form_shortcode ) :
			?>
			<section class="glm-tort-single__form" aria-labelledby="glm-form-heading">
				<h2 id="glm-form-heading">Free Case Evaluation</h2>
				<p>Confidential, free, and no obligation. An attorney will review your case and contact you within 24 hours.</p>
				<?php echo do_shortcode( $form_shortcode ); ?>
			</section>
			<?php
		endif;

		/* ── Related torts — same category, current excluded ── */
		if ( $category ) :

			$related = new WP_Query(
				array(
					'post_type'      => 'tort',
					'posts_per_page' => 3,
					'post__not_in'   => array( $post_id ),
					'no_found_rows'  => true,
					'tax_query'      => array(
						array(
							'taxonomy' => 'tort_category',
							'field'    => 'term_id',
							'terms'    => $category->term_id,
						),
					),
				)
			);

			if ( $related->have_posts() ) :
				?>
				<section class="glm-related" aria-labelledby="glm-related-heading">
					<h2 id="glm-related-heading" class="glm-related__heading">
						Other <?php echo esc_html( $category->name ); ?> Cases
					</h2>
					<div class="glm-tort-grid">
						<?php
						while ( $related->have_posts() ) {
							$related->the_post();
							get_template_part( 'inc/parts/tort-card', null, array( 'featured' => false ) );
						}
						?>
					</div>
				</section>
				<?php
			endif;

			wp_reset_postdata();
		endif;
		?>

		<footer class="glm-tort-single__disclaimer">
			<p>
				Attorney Advertising. Prior results do not guarantee similar outcomes.
				The information on this page is for general informational purposes only
				and does not constitute legal advice. Contacting Ged Lawyers does not
				establish an attorney-client relationship.
			</p>
		</footer>

	</article>

	<?php
endwhile;

get_footer();
