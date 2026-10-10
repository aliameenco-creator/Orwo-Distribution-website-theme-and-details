<?php
/**
 * One-click setup: pretty permalinks (needed for the original URLs), the original site title, the website
 * images in the Media Library, then the editable films. Safe to run again: nothing existing is overwritten.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwod_site_title() {
    return orwod_data( 'seo' )['site'] ?? 'ORWO Distribution | Boutique world sales agency';
}

function orwod_setup_missing() {
    $missing = array();
    if ( '' === (string) get_option( 'permalink_structure' ) ) { $missing['permalinks'] = 'turn on pretty permalinks so the original addresses (/blaze, /contact-us …) work'; }
    if ( orwod_site_title() !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "' . orwod_site_title() . '" (the original title)'; }
    if ( orwod_media_pending() ) { $missing['media'] = 'add the website images to the Media Library with descriptive file names and alt text (SEO)'; }
    if ( orwod_sample_post() ) { $missing['sample'] = 'move WordPress’s unedited sample post "Hello world!" to the trash (the menu shows Blog once you publish your own posts)'; }
    $films = count( orwod_films_pending() );
    if ( $films ) { $missing['films'] = 'create the ' . $films . ' films from the original website (editable under Films)'; }
    return $missing;
}

/** WordPress's default "Hello world!" post, only while it is still exactly as installed (never edited). */
function orwod_sample_post() {
    $post = get_page_by_path( 'hello-world', OBJECT, 'post' );
    if ( ! $post || 'publish' !== $post->post_status || 'Hello world!' !== $post->post_title || $post->post_modified_gmt !== $post->post_date_gmt ) { return 0; }
    return (int) $post->ID;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $missing = orwod_setup_missing();
    if ( ! $missing ) { return; }
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=orwod_setup' ), 'orwod_setup' );
    echo '<div class="notice notice-warning"><p><strong>ORWO Distribution setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete ORWO Distribution setup</a></p></div>';
} );

add_action( 'admin_post_orwod_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'orwod_setup' );
    if ( '' === (string) get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules( true );
    }
    if ( orwod_site_title() !== get_option( 'blogname' ) ) {
        update_option( 'blogname', orwod_site_title() );
        update_option( 'blogdescription', '' );
    }
    $sample = orwod_sample_post();
    if ( $sample ) { wp_trash_post( $sample ); }
    // Images first (films use them as poster, banner and gallery); the import then creates the films.
    if ( orwod_media_pending() ) { wp_safe_redirect( orwod_media_import_url() ); exit; }
    orwod_films_seed();
    wp_safe_redirect( admin_url( 'edit.php?post_type=' . ORWOD_FILM . '&orwod_media=done' ) );
    exit;
} );

/** Titles like the original site: "BLAZE - ORWO Distribution | Boutique world sales agency". */
add_filter( 'document_title_separator', function () { return '-'; } );
