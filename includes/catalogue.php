<?php
/**
 * ORWO's 2026 catalogue: trailers (pop-up player and film page), laurels, and a one-time update that brings sites set
 * up with an earlier theme version to the new homepage (Library order, 2026 titles, 2024 titles drafted).
 * The Studio and About Jake Seal pages stay live at their addresses (and in the sitemap) but nothing links to them.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const ORWOD_CATALOGUE = 3;

/** Privacy-friendly embed address for a YouTube or Vimeo link ('' for anything else). */
function orwod_trailer_embed( $url ) {
    $url = trim( (string) $url );
    if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1';
    }
    if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1';
    }
    return '';
}

function orwod_film_trailer( $film ) {
    return orwod_trailer_embed( get_post_meta( get_post( $film )->ID, '_orwod_trailer', true ) );
}

/** Attributes that make a link open the film's trailer pop-up (empty without a trailer). */
function orwod_trailer_attrs( $film ) {
    $embed = orwod_film_trailer( $film );
    return $embed ? ' data-trailer="' . esc_url( $embed ) . '" data-trailer-title="' . esc_attr( get_the_title( $film ) ) . '"' : '';
}

/** Laurels: one per line, "award | festival" (or just the festival). */
function orwod_film_laurels( $film ) {
    $out = array();
    foreach ( preg_split( '~\R~', (string) get_post_meta( get_post( $film )->ID, '_orwod_laurels', true ) ) as $line ) {
        $parts = array_map( 'trim', explode( '|', $line, 2 ) );
        if ( '' === $parts[0] ) { continue; }
        $out[] = isset( $parts[1] ) && '' !== $parts[1] ? array( $parts[0], $parts[1] ) : array( '', $parts[0] );
    }
    return $out;
}

function orwod_laurel_branch( $side ) {
    $flip = 'right' === $side ? ' transform="matrix(-1 0 0 1 24 0)"' : '';
    return '<svg class="laurel-branch" viewBox="0 0 24 64" aria-hidden="true"><g fill="currentColor"' . $flip . '><path d="M17 62C7 52 4 38 8 22l1.6.5C6 37.6 9 50.8 18.4 60.6z"/><ellipse cx="5" cy="50" rx="2.4" ry="5.4" transform="rotate(-40 5 50)"/><ellipse cx="3.6" cy="39" rx="2.3" ry="5.2" transform="rotate(-20 3.6 39)"/><ellipse cx="4" cy="28" rx="2.2" ry="5" transform="rotate(-5 4 28)"/><ellipse cx="6.4" cy="17.5" rx="2.1" ry="4.8" transform="rotate(12 6.4 17.5)"/><ellipse cx="10" cy="9" rx="2" ry="4.4" transform="rotate(30 10 9)"/><ellipse cx="13.6" cy="54" rx="2.2" ry="4.8" transform="rotate(-80 13.6 54)"/><ellipse cx="11.4" cy="43" rx="2.1" ry="4.6" transform="rotate(-65 11.4 43)"/><ellipse cx="11" cy="32" rx="2" ry="4.4" transform="rotate(-50 11 32)"/></g></svg>';
}

/** Festival laurels (film page). */
function orwod_laurels_html( $film ) {
    $items = '';
    foreach ( orwod_film_laurels( $film ) as list( $award, $festival ) ) {
        $items .= '<li class="laurel">' . orwod_laurel_branch( 'left' ) . '<span class="laurel-text">' . ( $award ? '<span class="laurel-award">' . esc_html( $award ) . '</span>' : '' ) . '<span class="laurel-fest">' . esc_html( $festival ) . '</span></span>' . orwod_laurel_branch( 'right' ) . '</li>';
    }
    return $items ? '<ul class="laurels" aria-label="Awards and festivals">' . $items . '</ul>' : '';
}

/** Short laurel for tiles: "3 AWARDS" when it won, otherwise the first festival honour. */
function orwod_laurel_badge( $film ) {
    $laurels = orwod_film_laurels( $film );
    if ( ! $laurels ) { return ''; }
    $wins = count( array_filter( $laurels, function ( $l ) { return 0 === stripos( $l[0], 'winner' ); } ) );
    $label = $wins ? ( 1 === $wins ? 'AWARD WINNER' : $wins . ' AWARDS' ) : ( $laurels[0][0] ? $laurels[0][0] : 'OFFICIAL SELECTION' );
    return orwod_laurel_branch( 'left' ) . '<span>' . esc_html( $label ) . '</span>' . orwod_laurel_branch( 'right' );
}

/**
 * One-time update for sites set up before 0.3.0: homepage groups and order, the 2024 titles drafted (except the ones
 * now in 2026 titles), trailers and laurels where a film has none yet. New films are added by the setup button
 * (their images go to the Media Library first). Films editors added themselves are left alone.
 */
function orwod_catalogue_update() {
    foreach ( orwod_data( 'films' ) as $film ) {
        $id = orwod_film_by_key( $film['key'] );
        $post = $id ? get_post( $id ) : null;
        if ( ! $post || 'trash' === $post->post_status ) { continue; }
        update_post_meta( $id, '_orwod_group', $film['group'] );
        $changes = array();
        if ( (int) $post->menu_order !== (int) $film['order'] ) { $changes['menu_order'] = (int) $film['order']; }
        if ( 'draft' === ( $film['status'] ?? '' ) && 'publish' === $post->post_status ) { $changes['post_status'] = 'draft'; }
        if ( $changes ) { wp_update_post( array( 'ID' => $id ) + $changes ); }
        if ( ! empty( $film['trailer'] ) && ! get_post_meta( $id, '_orwod_trailer', true ) ) { update_post_meta( $id, '_orwod_trailer', esc_url_raw( $film['trailer'] ) ); }
        if ( ! empty( $film['laurels'] ) && ! get_post_meta( $id, '_orwod_laurels', true ) ) { update_post_meta( $id, '_orwod_laurels', implode( "\n", $film['laurels'] ) ); }
    }
    update_option( 'orwod_catalogue', ORWOD_CATALOGUE, false );
}

add_action( 'admin_init', function () {
    if ( (int) get_option( 'orwod_catalogue', 0 ) >= ORWOD_CATALOGUE || ! current_user_can( 'manage_options' ) ) { return; }
    orwod_catalogue_update();
} );

/** Pages kept for search engines only: never listed in the site's own search results. */
function orwod_hidden_page_ids() {
    $ids = array();
    foreach ( array( 'studio', 'about-jake-seal' ) as $slug ) {
        $page = get_page_by_path( $slug );
        if ( $page ) { $ids[] = (int) $page->ID; }
    }
    return $ids;
}
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) { return; }
    $hidden = orwod_hidden_page_ids();
    if ( $hidden ) { $query->set( 'post__not_in', array_merge( (array) $query->get( 'post__not_in' ), $hidden ) ); }
} );
