<?php
/**
 * Editable WordPress pages for the original site's pages (parts/pages.json): Distribution (homepage; its text is the
 * hero tagline), Studio (intro text), About Jake Seal, CONTACT US, Blog and Cookie Policy. Created once by the setup
 * button at their original addresses and never overwritten. Until a page exists the theme serves its original layout.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Original-site pages that do not exist yet (matched by slug in any status, including the trash). */
function orwod_pages_pending() {
    $pending = array();
    foreach ( orwod_data( 'pages' ) as $page ) {
        if ( orwod_page_by_slug( $page['slug'] ) ) { continue; }
        if ( get_option( 'orwod_page_' . $page['slug'] ) ) { continue; } // created before and deleted by an editor
        $pending[] = $page;
    }
    return $pending;
}

function orwod_page_by_slug( $slug ) {
    $ids = get_posts( array( 'post_type' => 'page', 'name' => $slug, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ), 'numberposts' => 1, 'fields' => 'ids' ) );
    if ( ! $ids ) { $ids = get_posts( array( 'post_type' => 'page', 'name' => $slug . '__trashed', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids' ) ); }
    return $ids ? (int) $ids[0] : 0;
}

function orwod_pages_seed() {
    if ( ! current_user_can( 'edit_pages' ) ) { return 0; }
    $media = array_filter( array_map( 'intval', (array) get_option( 'orwod_media_ids', array() ) ) );
    $created = 0;
    foreach ( orwod_pages_pending() as $page ) {
        $id = wp_insert_post( array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_content' => implode( "\n\n", $page['blocks'] ),
        ), true );
        if ( is_wp_error( $id ) ) { continue; }
        update_option( 'orwod_page_' . $page['slug'], (int) $id, false );
        if ( ! empty( $page['thumbnail'] ) && ! empty( $media[ $page['thumbnail'] ] ) ) { set_post_thumbnail( $id, $media[ $page['thumbnail'] ] ); }
        $seo = orwod_seo_data( $page['seo'] );
        if ( ! empty( $seo['title'] ) ) { update_post_meta( $id, '_yoast_wpseo_title', $seo['title'] ); }
        if ( ! empty( $seo['description'] ) ) { update_post_meta( $id, '_yoast_wpseo_metadesc', $seo['description'] ); }
        if ( ! empty( $page['front'] ) && 'page' !== get_option( 'show_on_front' ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', (int) $id );
        }
        ++$created;
    }
    return $created;
}

/** The cookie policy keeps its original address /pages/cookie-policy/. */
add_filter( 'page_link', function ( $link, $id ) {
    if ( get_option( 'permalink_structure' ) && 'cookie-policy' === get_post_field( 'post_name', $id ) && ! wp_get_post_parent_id( $id ) ) {
        return home_url( user_trailingslashit( 'pages/cookie-policy' ) );
    }
    return $link;
}, 10, 2 );
add_filter( 'request', function ( $vars ) {
    if ( is_admin() || 'pages/cookie-policy' !== orwod_request_path() ) { return $vars; }
    $page = get_page_by_path( 'cookie-policy' );
    return ( $page && 'publish' === $page->post_status ) ? array( 'pagename' => 'cookie-policy' ) : $vars;
}, 5 );

/** Homepage tagline: the first paragraph of the Distribution page (falls back to the original text). */
function orwod_home_tagline() {
    $id = (int) get_option( 'page_on_front' );
    if ( 'page' !== get_option( 'show_on_front' ) || ! $id ) { return ''; }
    $content = (string) get_post_field( 'post_content', $id );
    if ( ! preg_match( '~<p[^>]*>(.*?)</p>~s', $content, $m ) ) { return ''; }
    return trim( wp_strip_all_tags( $m[1] ) );
}
