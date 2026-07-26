<?php
/**
 * Case evaluation form — Contact Form 7.
 *
 * WHY CF7 AND NOT A NICER BUILDER
 *
 * These forms collect names, phone numbers, emails and injury descriptions.
 * CF7 does NOT persist submissions to the database — it emails them and
 * forgets them. For a firm handling this data that is the safer posture: a
 * site compromise exposes no case history, because none is stored.
 *
 * BUILT FOR A CRM HANDOVER
 *
 * The plan is to forward leads into GoHighLevel or Litify. Rather than make
 * that a rebuild later, every successful submission fires a single action
 * with normalised data:
 *
 *     do_action( 'glm_case_submission', $lead, $contact_form );
 *
 * Connecting a CRM then means one function on that hook — no form changes.
 *
 * @package glm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GLM_FORM_KEY = '_glm_form_slug';

/**
 * The case evaluation form markup.
 *
 * The case-type <select> is left with a single placeholder here and filled
 * at render time from the tort CPT — see glm_populate_case_types(). The
 * source's dropdown listed 29 case types against 40 torts because it was
 * maintained by hand (R5).
 *
 * @return string
 */
function glm_case_form_markup() {
	return trim(
		'<div class="glm-form">

<p class="glm-form__row">
  <label>Full Name <span class="glm-req">*</span>
    [text* fullname placeholder "Your full name"]
  </label>
</p>

<div class="glm-form__grid">
  <p class="glm-form__row">
    <label>Phone Number <span class="glm-req">*</span>
      [tel* phone placeholder "(___) ___-____"]
    </label>
  </p>
  <p class="glm-form__row">
    <label>Email Address <span class="glm-req">*</span>
      [email* email placeholder "your@email.com"]
    </label>
  </p>
</div>

<p class="glm-form__row">
  <label>State You Live In
    [select state "Select your state..." "Florida" "Massachusetts" "New Jersey" "Michigan" "Other State"]
  </label>
</p>

<p class="glm-form__row">
  <label>Type of Case
    [select case_type "Select a mass tort..."]
  </label>
</p>

<p class="glm-form__row">
  <label>Brief Description of What Happened
    [textarea message placeholder "Tell us briefly about your injury, when it occurred, and what product or drug was involved..."]
  </label>
</p>

<p class="glm-form__hp">
  [text glm_hp]
</p>

<p class="glm-form__submit">[submit "Submit Free Case Evaluation →"]</p>

<p class="glm-form__disclaimer">
  By submitting, you agree to be contacted by Ged Lawyers. Attorney-client
  privilege applies. Your information is never sold or shared.
</p>

</div>'
	);
}

/**
 * Create or update the CF7 form.
 *
 * @param bool $force Overwrite existing.
 * @return array [status, id]
 */
function glm_build_case_form( $force = false ) {

	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return array( 'CF7 not active', 0 );
	}

	$existing = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => GLM_FORM_KEY, // phpcs:ignore
			'meta_value'     => 'case-eval',  // phpcs:ignore
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( $post_id && ! $force ) {
		return array( 'skipped (exists)', $post_id );
	}

	$admin_email = get_option( 'admin_email' );

	$mail = array(
		'subject'            => '[Case Evaluation] [case_type] — [fullname]',
		'sender'             => 'GLMassTorts <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
		'recipient'          => $admin_email,
		'body'               => "New case evaluation request\n\n"
			. "Name:        [fullname]\n"
			. "Phone:       [phone]\n"
			. "Email:       [email]\n"
			. "State:       [state]\n"
			. "Case type:   [case_type]\n\n"
			. "Description:\n[message]\n\n"
			. "---\nSubmitted from [_url] at [_date] [_time]\n",
		'additional_headers' => 'Reply-To: [email]',
		'attachments'        => '',
		'use_html'           => 0,
		'exclude_blank'      => 0,
	);

	$postarr = array(
		'post_type'   => 'wpcf7_contact_form',
		'post_status' => 'publish',
		'post_title'  => 'Case Evaluation',
	);

	if ( $post_id ) {
		$postarr['ID'] = $post_id;
		wp_update_post( $postarr );
		$status = 'updated';
	} else {
		$post_id = wp_insert_post( $postarr );
		$status  = 'created';
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return array( 'failed', 0 );
	}

	update_post_meta( $post_id, '_form', glm_case_form_markup() );
	update_post_meta( $post_id, '_mail', $mail );
	update_post_meta( $post_id, '_messages', WPCF7_ContactForm::get_template()->prop( 'messages' ) );
	update_post_meta( $post_id, '_locale', get_locale() );
	update_post_meta( $post_id, GLM_FORM_KEY, 'case-eval' );

	// Remember the id so the shortcode filter does not need a lookup.
	update_option( 'glm_case_form_id', $post_id );

	return array( $status, $post_id );
}

/**
 * Populate the case-type dropdown from the tort CPT.
 *
 * Generated rather than typed, so the dropdown cannot drift from the tort
 * list — which is exactly what had happened on the source site (R5).
 *
 * @param array $tag Form tag.
 * @return array
 */
function glm_populate_case_types( $tag ) {

	if ( 'case_type' !== ( $tag['name'] ?? '' ) ) {
		return $tag;
	}

	$torts = get_posts(
		array(
			'post_type'      => 'tort',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	foreach ( $torts as $id ) {
		$title            = get_the_title( $id );
		$tag['values'][]  = $title;
		$tag['labels'][]  = $title;
		$tag['raw_values'][] = $title;
	}

	$tag['values'][]     = 'Other / Not Listed';
	$tag['labels'][]     = 'Other / Not Listed';
	$tag['raw_values'][] = 'Other / Not Listed';

	return $tag;
}
add_filter( 'wpcf7_form_tag', 'glm_populate_case_types', 10, 1 );

/**
 * Reject submissions that fill the honeypot.
 *
 * CF7 ships no spam protection. This is a plain honeypot: a text input
 * hidden by CSS that humans never see and bots commonly fill.
 *
 * GOTCHA: not sufficient on its own against targeted spam. Add hCaptcha
 * before launch if volume becomes a problem — but not reCAPTCHA v3 alone,
 * which silently drops real users on a site where a lost enquiry is a lost
 * case.
 *
 * @param WPCF7_Validation $result Validation result.
 * @param array            $tags   Form tags.
 * @return WPCF7_Validation
 */
function glm_honeypot_check( $result, $tags ) {

	if ( ! empty( $_POST['glm_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$result->invalidate(
			array(
				'type' => 'text',
				'name' => 'glm_hp',
			),
			'Submission rejected.'
		);
	}

	return $result;
}
add_filter( 'wpcf7_validate', 'glm_honeypot_check', 20, 2 );

/**
 * Normalise a successful submission and hand it to whatever wants it.
 *
 * THIS IS THE CRM SEAM. To forward leads into GoHighLevel, Litify or
 * anything else, hook this — no form or template changes needed:
 *
 *     add_action( 'glm_case_submission', function ( $lead ) {
 *         wp_remote_post( 'https://services.leadconnectorhq.com/hooks/…', array(
 *             'headers' => array( 'Content-Type' => 'application/json' ),
 *             'body'    => wp_json_encode( $lead ),
 *         ) );
 *     } );
 *
 * @param WPCF7_ContactForm $contact_form The submitted form.
 */
function glm_dispatch_case_submission( $contact_form ) {

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission ) {
		return;
	}

	$data = $submission->get_posted_data();

	$lead = array(
		'full_name'   => sanitize_text_field( $data['fullname'] ?? '' ),
		'phone'       => sanitize_text_field( $data['phone'] ?? '' ),
		'email'       => sanitize_email( $data['email'] ?? '' ),
		'state'       => sanitize_text_field( $data['state'] ?? '' ),
		'case_type'   => sanitize_text_field( $data['case_type'] ?? '' ),
		'description' => sanitize_textarea_field( $data['message'] ?? '' ),
		'source_url'  => esc_url_raw( $submission->get_meta( 'url' ) ),
		'submitted'   => current_time( 'mysql' ),
		'form'        => $contact_form->title(),
	);

	/**
	 * A validated case enquiry.
	 *
	 * Nothing listens by default — the lead is emailed and not stored.
	 *
	 * @param array             $lead         Normalised submission.
	 * @param WPCF7_ContactForm $contact_form The form.
	 */
	do_action( 'glm_case_submission', $lead, $contact_form );
}
add_action( 'wpcf7_mail_sent', 'glm_dispatch_case_submission' );

/**
 * Supply the form to templates that ask for it.
 *
 * single-tort.php has exposed this filter since Phase 3 precisely so the
 * form plugin could be chosen later without editing 40 pages.
 */
function glm_provide_case_form() {
	$id = (int) get_option( 'glm_case_form_id' );
	return $id ? '[contact-form-7 id="' . $id . '" title="Case Evaluation"]' : '';
}
add_filter( 'glm_case_form_shortcode', 'glm_provide_case_form' );

/**
 * WP-CLI: wp glm build-form [--force]
 */
function glm_cli_build_form( $args, $assoc_args ) {

	list( $status, $id ) = glm_build_case_form( isset( $assoc_args['force'] ) );

	if ( ! $id ) {
		WP_CLI::error( $status );
	}

	WP_CLI::log( sprintf( '  case-eval  %-18s id=%d', $status, $id ) );
	WP_CLI::log( sprintf( '  shortcode  [contact-form-7 id="%d" title="Case Evaluation"]', $id ) );
	WP_CLI::log( sprintf( '  emails to  %s', get_option( 'admin_email' ) ) );
	WP_CLI::success( 'Case evaluation form built. Submissions are emailed, not stored.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'glm build-form', 'glm_cli_build_form' );
}
