<?php
/**
 * [glm_tort_grid] — the tort browser.
 *
 * The free-tier equivalent of Elementor Pro's Loop Grid widget. Queries
 * the tort CPT and repeats inc/parts/tort-card.php for each result.
 *
 * Why this exists (docs/component-inventory.md §5): Elementor Free has no
 * ACF dynamic tags, and 6 of the tort card's 8 fields are ACF fields, so
 * no free grid widget can render this card. Owning it here also puts the
 * site's most complex component into git rather than postmeta (R12).
 *
 * USAGE
 *   [glm_tort_grid]                                Full tabbed browser
 *   [glm_tort_grid tabs="no" featured="yes" limit="6"]   Homepage preview
 *   [glm_tort_grid category="pharma" tabs="no"]    One category
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the tort grid.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML.
 */
function glm_tort_grid_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'tabs'     => 'yes',   // Show the category tab bar.
			'category' => '',      // Restrict to one tort_category slug.
			'featured' => 'no',    // Only torts flagged is_featured.
			'limit'    => -1,      // Max results (-1 = all).
			'heading'  => 'yes',   // Show per-category header bars.
		),
		$atts,
		'glm_tort_grid'
	);

	$show_tabs = ( 'yes' === $atts['tabs'] ) && empty( $atts['category'] );

	// Tabs need the assets; a flat grid does not.
	if ( $show_tabs ) {
		wp_enqueue_script( 'glm-tort-tabs' );
	}

	ob_start();

	if ( $show_tabs ) {
		glm_render_tort_tabs( $atts );
	} else {
		glm_render_tort_panel( $atts['category'], $atts, false );
	}

	return ob_get_clean();
}
add_shortcode( 'glm_tort_grid', 'glm_tort_grid_shortcode' );

/**
 * Render the full tabbed browser: one tab and one panel per category.
 *
 * Categories come from the taxonomy, so adding a category adds a tab.
 * Nothing here is hardcoded — which is the whole point (R5).
 *
 * @param array $atts Shortcode attributes.
 */
function glm_render_tort_tabs( $atts ) {

	$terms = get_terms(
		array(
			'taxonomy'   => 'tort_category',
			'hide_empty' => true,
			'orderby'    => 'term_order',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	$first = true;
	?>
	<div class="glm-tort-browser">

		<div class="glm-tort-tabs" role="tablist" aria-label="Mass tort categories">
			<?php foreach ( $terms as $term ) : ?>
				<button type="button"
				        class="glm-tort-tab<?php echo $first ? ' is-active' : ''; ?>"
				        role="tab"
				        id="glm-tab-<?php echo esc_attr( $term->slug ); ?>"
				        aria-controls="glm-panel-<?php echo esc_attr( $term->slug ); ?>"
				        aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
				        data-target="<?php echo esc_attr( $term->slug ); ?>">
					<?php
					$emoji = get_term_meta( $term->term_id, 'glm_emoji', true );
					if ( $emoji ) {
						echo '<span aria-hidden="true">' . esc_html( $emoji ) . '</span> ';
					}
					echo esc_html( $term->name );
					?>
				</button>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>

		<?php
		$first = true;
		foreach ( $terms as $term ) {
			glm_render_tort_panel( $term->slug, $atts, true, $first );
			$first = false;
		}
		?>
	</div>
	<?php
}

/**
 * Render one category panel — header bar plus the card grid.
 *
 * @param string $category_slug Category to query, or '' for all.
 * @param array  $atts          Shortcode attributes.
 * @param bool   $as_panel      Wrap as a tabpanel.
 * @param bool   $is_active     Whether this panel starts visible.
 */
function glm_render_tort_panel( $category_slug, $atts, $as_panel = false, $is_active = true ) {

	$query_args = array(
		'post_type'      => 'tort',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['limit'],
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true, // No pagination here — cheaper query.
	);

	if ( $category_slug ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'tort_category',
				'field'    => 'slug',
				'terms'    => $category_slug,
			),
		);
	}

	if ( 'yes' === $atts['featured'] ) {
		$query_args['meta_query'] = array(
			array(
				'key'   => 'is_featured',
				'value' => '1',
			),
		);
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return;
	}

	$term = $category_slug ? get_term_by( 'slug', $category_slug, 'tort_category' ) : null;

	if ( $as_panel ) {
		printf(
			'<div class="glm-tort-panel%s" id="glm-panel-%s" role="tabpanel" aria-labelledby="glm-tab-%s"%s>',
			$is_active ? ' is-active' : '',
			esc_attr( $category_slug ),
			esc_attr( $category_slug ),
			$is_active ? '' : ' hidden'
		);
	}

	if ( $term && 'yes' === $atts['heading'] ) {
		?>
		<div class="glm-cat-header">
			<span class="glm-cat-header__title"><?php echo esc_html( $term->name ); ?></span>
			<span class="glm-cat-header__count">
				<?php
				// Computed, never hardcoded. The source said "35" when there
				// were 40 because someone typed it. This cannot drift (R5).
				printf(
					/* translators: %d: number of active cases */
					esc_html( _n( '%d Active Case', '%d Active Cases', $query->post_count, 'glm' ) ),
					(int) $query->post_count
				);
				?>
			</span>
		</div>
		<?php
	}

	echo '<div class="glm-tort-grid">';

	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part(
			'inc/parts/tort-card',
			null,
			array( 'featured' => true )
		);
	}

	echo '</div>';

	if ( $as_panel ) {
		echo '</div>';
	}

	wp_reset_postdata();
}

/**
 * Register the tab script. Enqueued only when a tabbed grid renders.
 */
function glm_register_tort_assets() {
	wp_register_script(
		'glm-tort-tabs',
		GLM_URI . '/assets/js/tort-tabs.js',
		array(),
		GLM_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'glm_register_tort_assets' );

/**
 * [glm_tort_count] — the live tort count.
 *
 * Replaces the hardcoded "35" in the source, which was wrong by five.
 * Use anywhere the number appears in copy.
 *
 * @return string
 */
function glm_tort_count_shortcode() {
	$counts = wp_count_posts( 'tort' );
	return esc_html( (string) ( $counts->publish ?? 0 ) );
}
add_shortcode( 'glm_tort_count', 'glm_tort_count_shortcode' );

/**
 * [glm_tort_options] — <option> tags for the contact form's case-type field.
 *
 * The source's dropdown listed 29 case types against 40 torts; it had
 * drifted. Generating it from the same query makes that impossible.
 *
 * @return string
 */
function glm_tort_options_shortcode() {

	$torts = get_posts(
		array(
			'post_type'      => 'tort',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$out = '';
	foreach ( $torts as $tort ) {
		$out .= sprintf(
			'<option value="%1$s">%1$s</option>',
			esc_attr( $tort->post_title )
		);
	}
	$out .= '<option value="Other / Not Listed">Other / Not Listed</option>';

	return $out;
}
add_shortcode( 'glm_tort_options', 'glm_tort_options_shortcode' );
