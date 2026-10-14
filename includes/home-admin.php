<?php
/**
 * Films → Homepage titles: rename the homepage sections and drag films between Library titles, 2026 titles and
 * "Not on the homepage", in the order they should appear (the hero's "Now showing" follows the Library order).
 * New films added under Films → Add film go to the end of Library titles unless another section is chosen.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
    add_submenu_page( 'edit.php?post_type=' . ORWOD_FILM, 'Homepage titles', 'Homepage titles', 'edit_others_posts', 'orwod-homepage', 'orwod_homepage_screen' );
} );

/** Films for the screen: every published or draft film, grouped and in homepage order. */
function orwod_homepage_columns() {
    $columns = array_fill_keys( array_keys( orwod_film_groups() ), array() );
    $films = get_posts( array( 'post_type' => ORWOD_FILM, 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => 500, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
    foreach ( $films as $film ) {
        $group = (string) get_post_meta( $film->ID, '_orwod_group', true );
        // Only published films are on the homepage: drafts are listed under "Not on the homepage".
        if ( 'publish' !== $film->post_status || ! isset( $columns[ $group ] ) ) { $group = ''; }
        $columns[ $group ][] = $film;
    }
    return $columns;
}

function orwod_homepage_screen() {
    if ( ! current_user_can( 'edit_others_posts' ) ) { wp_die( 'Not allowed.' ); }
    $groups = orwod_film_groups();
    $headings = array( 'library' => 'Library section', '2026' => 'Second section', '' => 'Not on the homepage' );
    echo '<div class="wrap orwod-home-admin"><h1 class="wp-heading-inline">Homepage titles</h1> <a class="page-title-action" href="' . esc_url( admin_url( 'post-new.php?post_type=' . ORWOD_FILM ) ) . '">Add film</a><hr class="wp-header-end">';
    if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>Homepage saved.</p></div>'; } // phpcs:ignore WordPress.Security.NonceVerification
    echo '<p>Drag films to reorder them or to move them between sections. The first Library titles with a wide banner image are also shown in the homepage hero ("Now showing"). Moving a draft into a section publishes it; moving a film to "Not on the homepage" keeps its page online.</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="orwod_homepage_save">';
    wp_nonce_field( 'orwod_homepage_save' );
    echo '<div class="orwod-columns">';
    foreach ( orwod_homepage_columns() as $group => $films ) {
        echo '<div class="orwod-column"><h2>' . esc_html( $headings[ $group ] ?? $group ) . '</h2>';
        if ( '' !== $group ) {
            echo '<p><label>Heading on the homepage' . ( '2026' === $group ? ' and in the menu' : '' ) . '<br><input type="text" class="widefat" name="orwod_titles[' . esc_attr( $group ) . ']" value="' . esc_attr( $groups[ $group ] ) . '"></label></p>';
        }
        echo '<ol class="orwod-sort" data-group="' . esc_attr( $group ) . '">';
        foreach ( $films as $film ) {
            $thumb = orwod_film_image_id( $film, 'tile' );
            $state = 'publish' === $film->post_status ? '' : ' <span class="orwod-state">' . esc_html( get_post_status_object( $film->post_status )->label ?? $film->post_status ) . '</span>';
            $extras = array();
            if ( orwod_film_trailer( $film ) ) { $extras[] = 'trailer'; }
            if ( orwod_film_laurels( $film ) ) { $extras[] = 'laurels'; }
            echo '<li><input type="hidden" name="orwod_order[' . esc_attr( '' === $group ? 'none' : $group ) . '][]" value="' . (int) $film->ID . '">'
                . '<span class="orwod-handle" aria-hidden="true">⋮⋮</span>'
                . ( $thumb ? wp_get_attachment_image( $thumb, 'thumbnail', false, array( 'alt' => '' ) ) : '<span class="orwod-nothumb"></span>' )
                . '<span class="orwod-name"><strong>' . esc_html( get_the_title( $film ) ) . '</strong>' . $state . '<small>' . esc_html( orwod_film_subtitle( $film ) . ( $extras ? ' · ' . implode( ', ', $extras ) : '' ) ) . '</small></span>'
                . '<a href="' . esc_url( get_edit_post_link( $film ) ) . '">Edit</a></li>';
        }
        echo '</ol></div>';
    }
    echo '</div>';
    submit_button( 'Save homepage' );
    echo '</form></div>';
}

add_action( 'admin_post_orwod_homepage_save', function () {
    if ( ! current_user_can( 'edit_others_posts' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'orwod_homepage_save' );
    $groups = orwod_film_groups();
    $titles = array();
    foreach ( (array) wp_unslash( $_POST['orwod_titles'] ?? array() ) as $group => $title ) {
        if ( isset( $groups[ $group ] ) && '' !== $group ) { $titles[ $group ] = sanitize_text_field( $title ); }
    }
    update_option( 'orwod_group_titles', $titles, false );
    $offsets = array( 'library' => 0, '2026' => 100, 'none' => 1000 );
    foreach ( (array) ( $_POST['orwod_order'] ?? array() ) as $group => $ids ) {
        if ( ! isset( $offsets[ $group ] ) ) { continue; }
        foreach ( array_values( array_map( 'absint', (array) $ids ) ) as $n => $id ) {
            $film = get_post( $id );
            if ( ! $film || ORWOD_FILM !== $film->post_type || ! current_user_can( 'edit_post', $id ) ) { continue; }
            update_post_meta( $id, '_orwod_group', 'none' === $group ? '' : $group );
            $changes = array();
            if ( (int) $film->menu_order !== $offsets[ $group ] + $n + 1 ) { $changes['menu_order'] = $offsets[ $group ] + $n + 1; }
            if ( 'none' !== $group && 'publish' !== $film->post_status && current_user_can( 'publish_post', $id ) ) { $changes['post_status'] = 'publish'; }
            if ( $changes ) { wp_update_post( array( 'ID' => $id ) + $changes ); }
        }
    }
    wp_safe_redirect( admin_url( 'edit.php?post_type=' . ORWOD_FILM . '&page=orwod-homepage&saved=1' ) );
    exit;
} );

add_action( 'admin_enqueue_scripts', function () {
    if ( ! isset( $_GET['page'] ) || 'orwod-homepage' !== $_GET['page'] ) { return; } // phpcs:ignore WordPress.Security.NonceVerification
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_add_inline_script( 'jquery-ui-sortable', "jQuery(function($){ $('.orwod-sort').sortable({ connectWith: '.orwod-sort', handle: '.orwod-handle, img, .orwod-name', placeholder: 'orwod-placeholder', update: function(){ var g = $(this).data('group') || 'none'; $(this).find('input[type=hidden]').attr('name', 'orwod_order[' + g + '][]'); } }); });" );
    wp_register_style( 'orwod-home-admin', false, array(), wp_get_theme()->get( 'Version' ) );
    wp_enqueue_style( 'orwod-home-admin' );
    wp_add_inline_style( 'orwod-home-admin', '.orwod-columns{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;align-items:start}.orwod-column{background:#fff;border:1px solid #dcdcde;padding:0 14px 14px}.orwod-sort{list-style:none;margin:0;min-height:60px;padding:6px;background:#f6f7f7}.orwod-sort li{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #dcdcde;margin:0 0 6px;padding:6px 8px;cursor:grab}.orwod-sort li img,.orwod-nothumb{width:56px;height:38px;object-fit:cover;background:#2c3338;flex:none}.orwod-handle{color:#8c8f94;letter-spacing:-2px}.orwod-name{flex:1;min-width:0;display:flex;flex-direction:column}.orwod-name small{color:#646970}.orwod-state{align-self:flex-start;margin:2px 0;padding:0 6px;background:#f0f0f1;border-radius:3px;font-size:11px}.orwod-placeholder{height:48px;border:2px dashed #2271b1;background:#f0f6fc;margin:0 0 6px}@media(max-width:1100px){.orwod-columns{grid-template-columns:1fr}}' );
} );

/** New films: Library titles by default, added at the end of their section. */
add_action( 'save_post_' . ORWOD_FILM, function ( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status || (int) $post->menu_order > 0 ) { return; }
    $group = (string) get_post_meta( $post_id, '_orwod_group', true );
    if ( '' === $group ) { return; }
    $last = get_posts( array( 'post_type' => ORWOD_FILM, 'post_status' => 'any', 'numberposts' => 1, 'orderby' => 'menu_order', 'order' => 'DESC', 'fields' => 'ids', 'post__not_in' => array( $post_id ), 'meta_key' => '_orwod_group', 'meta_value' => $group ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    global $wpdb;
    $wpdb->update( $wpdb->posts, array( 'menu_order' => $last ? (int) get_post_field( 'menu_order', $last[0] ) + 1 : ( '2026' === $group ? 101 : 1 ) ), array( 'ID' => $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    clean_post_cache( $post_id );
}, 20, 3 );
