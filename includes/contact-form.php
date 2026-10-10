<?php
/**
 * Contact form recreated from the original Strikingly email form (fields, order, labels, submit
 * label and thank-you text in parts/contact-form.json). Submissions are saved as private
 * "Enquiries" in wp-admin and emailed to the configured address. No third-party service is used.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwod_form_definition() {
    static $form = null;
    if ( null === $form ) {
        $file = get_theme_file_path( 'parts/contact-form.json' );
        $form = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return is_array( $form ) ? $form : array();
}

/** Original contact details (address and email from the archived Contact Us page). */
function orwod_contact_details() {
    $details = orwod_data( 'contact' );
    return array_merge( array( 'title' => 'CONTACT US', 'intro' => '', 'address' => '', 'email' => 'hello@orwodistribution.com', 'phone' => '', 'image' => '' ), $details );
}

function orwod_contact_recipient() {
    $email = sanitize_email( (string) get_option( 'orwod_contact_recipient', '' ) );
    return is_email( $email ) ? $email : 'hello@orwodistribution.com';
}

add_action( 'init', function () {
    register_post_type( 'orwod_enquiry', array(
        'labels'          => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'menu_name' => 'Enquiries', 'all_items' => 'All enquiries', 'edit_item' => 'Enquiry' ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
        'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'    => true,
    ) );
} );

/** Recipient address under Settings > General. */
add_action( 'admin_init', function () {
    register_setting( 'general', 'orwod_contact_recipient', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
    add_settings_field( 'orwod_contact_recipient', 'ORWO Distribution contact form recipient', function () {
        echo '<input type="email" class="regular-text" name="orwod_contact_recipient" value="' . esc_attr( get_option( 'orwod_contact_recipient', '' ) ) . '" placeholder="hello@orwodistribution.com"><p class="description">Enquiries from the Contact page are emailed here and always saved under Enquiries. Leave empty for hello@orwodistribution.com.</p>';
    }, 'general' );
} );

add_action( 'add_meta_boxes_orwod_enquiry', function () {
    add_meta_box( 'orwod_enquiry_fields', 'Submitted details', function ( $post ) {
        $values = (array) get_post_meta( $post->ID, '_orwod_fields', true );
        echo '<table class="widefat striped"><tbody>';
        foreach ( orwod_form_definition()['fields'] ?? array() as $field ) {
            echo '<tr><th style="width:180px">' . esc_html( $field['label'] ) . '</th><td style="white-space:pre-wrap">' . esc_html( $values[ $field['key'] ] ?? '' ) . '</td></tr>';
        }
        $mail = get_post_meta( $post->ID, '_orwod_mail', true );
        echo '<tr><th>Email notification</th><td>' . esc_html( $mail ? $mail : 'unknown' ) . '</td></tr><tr><th>Page</th><td>' . esc_html( get_post_meta( $post->ID, '_orwod_page', true ) ) . '</td></tr></tbody></table>';
    }, 'orwod_enquiry', 'normal', 'high' );
} );
add_filter( 'manage_orwod_enquiry_posts_columns', function ( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Name', 'orwod_email' => 'Email', 'orwod_mail' => 'Email sent', 'date' => 'Received' );
} );
add_action( 'manage_orwod_enquiry_posts_custom_column', function ( $column, $id ) {
    $values = (array) get_post_meta( $id, '_orwod_fields', true );
    if ( 'orwod_email' === $column ) { echo esc_html( $values['email'] ?? '' ); }
    if ( 'orwod_mail' === $column ) { echo esc_html( get_post_meta( $id, '_orwod_mail', true ) ); }
}, 10, 2 );

function orwod_form_respond( $ok, $message, $errors = array(), $values = array() ) {
    if ( str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' ) ) {
        wp_send_json( array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), $ok ? 200 : 422 );
    }
    $back = wp_get_referer() ? wp_get_referer() : home_url( '/contact-us' );
    $back = remove_query_arg( array( 'orwod_form', 'orwod_token' ), $back );
    if ( $ok ) {
        wp_safe_redirect( add_query_arg( 'orwod_form', 'sent', $back ) . '#enquiry' );
        exit;
    }
    $token = wp_generate_password( 20, false );
    set_transient( 'orwod_form_' . $token, array( 'message' => $message, 'errors' => $errors, 'values' => $values ), 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( array( 'orwod_form' => 'error', 'orwod_token' => $token ), $back ) . '#enquiry' );
    exit;
}

function orwod_handle_contact() {
    $form = orwod_form_definition();
    $values = array();
    $errors = array();
    foreach ( $form['fields'] ?? array() as $field ) {
        $raw = isset( $_POST[ 'orwod_' . $field['key'] ] ) ? wp_unslash( $_POST[ 'orwod_' . $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidationSanitization.InputNotSanitized -- Public form; sanitised below; see anti-spam checks.
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        $values[ $field['key'] ] = $value;
        if ( $field['required'] && '' === trim( $value ) ) { $errors[ $field['key'] ] = $field['label'] . ' is required.'; }
        elseif ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) { $errors[ $field['key'] ] = 'Please enter a valid email address.'; }
        elseif ( mb_strlen( $value ) > (int) $field['max'] ) { $errors[ $field['key'] ] = $field['label'] . ' is too long (maximum ' . (int) $field['max'] . ' characters).'; }
    }
    // Spam protection that survives page caching: honeypot, minimum time on page (JavaScript), per-visitor rate limit.
    if ( ! empty( $_POST['orwod_website'] ) ) { orwod_form_respond( true, $form['thanks'] ?? '' ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
    // Milliseconds the visitor spent on the page, measured in the browser (independent of clock differences).
    $elapsed = isset( $_POST['orwod_elapsed'] ) ? (int) $_POST['orwod_elapsed'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $elapsed < 3000 ) {
        orwod_form_respond( false, 'Your message could not be verified. Please wait a moment and submit again, or email hello@orwodistribution.com.', array(), $values );
    }
    $visitor = 'orwod_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    $count = (int) get_transient( $visitor );
    if ( $count >= 5 ) { orwod_form_respond( false, 'Too many messages in a short time. Please try again later or email hello@orwodistribution.com.', array(), $values ); }
    if ( $errors ) { orwod_form_respond( false, 'Please check the highlighted fields.', $errors, $values ); }
    set_transient( $visitor, $count + 1, 10 * MINUTE_IN_SECONDS );

    $page = esc_url_raw( (string) wp_get_referer() );
    $id = wp_insert_post( array( 'post_type' => 'orwod_enquiry', 'post_status' => 'private', 'post_title' => $values['name'] ?? 'Enquiry' ), true );
    if ( ! is_wp_error( $id ) ) {
        update_post_meta( $id, '_orwod_fields', $values );
        update_post_meta( $id, '_orwod_page', $page );
    }
    $lines = array();
    foreach ( $form['fields'] as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $values[ $field['key'] ] ? '-' : $values[ $field['key'] ] ); }
    $lines[] = '';
    $lines[] = 'Sent from: ' . $page;
    $headers = array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $values['name'] ?? '' ) . ' <' . $values['email'] . '>' );
    $sent = wp_mail( orwod_contact_recipient(), 'ORWO Distribution website enquiry from ' . ( $values['name'] ?? '' ), implode( "\n", $lines ), $headers );
    if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_orwod_mail', $sent ? 'sent to ' . orwod_contact_recipient() : 'FAILED - check site email settings' ); }
    orwod_form_respond( true, $form['thanks'] ?? 'Thank you.' );
}
add_action( 'admin_post_nopriv_orwod_contact', 'orwod_handle_contact' );
add_action( 'admin_post_orwod_contact', 'orwod_handle_contact' );

function orwod_contact_form() {
    $form = orwod_form_definition();
    if ( ! $form ) { return; }
    $state = array( 'message' => '', 'errors' => array(), 'values' => array() );
    $status = isset( $_GET['orwod_form'] ) ? sanitize_key( wp_unslash( $_GET['orwod_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'error' === $status && isset( $_GET['orwod_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = get_transient( 'orwod_form_' . sanitize_key( wp_unslash( $_GET['orwod_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_array( $saved ) ) { $state = $saved; }
    }
    echo '<form class="orwod-form" id="enquiry" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-orwod-form>';
    echo '<div class="form-status" role="status" aria-live="polite">';
    if ( 'sent' === $status ) { echo '<p class="form-success">' . esc_html( $form['thanks'] ) . '</p>'; }
    elseif ( $state['message'] ) { echo '<p class="form-error">' . esc_html( $state['message'] ) . '</p>'; }
    echo '</div><input type="hidden" name="action" value="orwod_contact"><input type="hidden" name="orwod_elapsed" value="" data-elapsed>';
    echo '<div class="hp" aria-hidden="true"><label>Website <input type="text" name="orwod_website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">';
    foreach ( $form['fields'] as $field ) {
        $name = 'orwod_' . $field['key'];
        $value = $state['values'][ $field['key'] ] ?? '';
        $error = $state['errors'][ $field['key'] ] ?? '';
        $attrs = ' id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" maxlength="' . (int) $field['max'] . '"' . ( $field['required'] ? ' required aria-required="true"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );
        $auto = array( 'name' => 'name', 'email' => 'email', 'phone' => 'tel' );
        if ( isset( $auto[ $field['key'] ] ) ) { $attrs .= ' autocomplete="' . esc_attr( $auto[ $field['key'] ] ) . '"'; }
        echo '<div class="field field-' . esc_attr( $field['key'] ) . ( 'textarea' === $field['type'] ? ' wide' : '' ) . '"><label for="' . esc_attr( $name ) . '">' . esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="req" aria-hidden="true">*</span>' : '' ) . '</label>';
        if ( 'textarea' === $field['type'] ) { echo '<textarea rows="6"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.
        else { echo '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attrs . '>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="field-error">' . esc_html( $error ) . '</span></div>';
    }
    echo '</div><div class="form-actions"><button class="button" type="submit">' . esc_html( strtoupper( $form['submit'] ) ) . ' ↗</button><span class="form-note">* required</span></div>';
    echo '<noscript><p class="form-error">Please enable JavaScript to send this form, or email <a href="mailto:hello@orwodistribution.com">hello@orwodistribution.com</a>.</p></noscript></form>';
}
