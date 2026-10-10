<?php
/**
 * Films: an editable post type for the distribution catalogue. Each film keeps its original address at
 * the site root (/blaze/, /77/ ...), exactly like the old site. The one-click setup creates the films once
 * from parts/films.json (archived site content) and never overwrites a film that already exists.
 *
 * Per film: title, content (synopsis, credits, IMDB link, gallery: block editor), featured image (poster),
 * and "Film details" (year, homepage group/order, homepage subtitle, banner and homepage tile images, contact email).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const ORWOD_FILM = 'orwod_film';

function orwod_film_groups() {
    $titles = orwod_data( 'home' )['titles'] ?? array();
    return array(
        'library' => $titles['library'] ?? 'Library titles',
        '2024'    => $titles['2024'] ?? 'Selected 2024 titles',
        ''        => 'Not shown on the homepage',
    );
}

add_action( 'init', function () {
    register_post_type( ORWOD_FILM, array(
        'labels'        => array(
            'name' => 'Films', 'singular_name' => 'Film', 'menu_name' => 'Films', 'all_items' => 'All films', 'add_new' => 'Add film',
            'add_new_item' => 'Add film', 'edit_item' => 'Edit film', 'new_item' => 'New film', 'view_item' => 'View film', 'search_items' => 'Search films',
            'not_found' => 'No films found.', 'featured_image' => 'Poster', 'set_featured_image' => 'Set poster', 'remove_featured_image' => 'Remove poster', 'use_featured_image' => 'Use as poster',
        ),
        'description'   => 'ORWO Distribution titles. Each film is served at the site root, e.g. /blaze/.',
        'public'        => true,
        'show_in_rest'  => true,
        'menu_position' => 5,
        'menu_icon'     => 'dashicons-format-video',
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions' ),
        'has_archive'   => false,
        // Root URLs (/blaze/) are added below; with plain permalinks ?orwod_film=blaze works.
        'rewrite'       => false,
        'query_var'     => ORWOD_FILM,
    ) );
    foreach ( array( '_orwod_year', '_orwod_group', '_orwod_subtitle', '_orwod_contact', '_orwod_display_title' ) as $key ) {
        register_post_meta( ORWOD_FILM, $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'edit_posts' ); } ) );
    }
    foreach ( array( '_orwod_banner', '_orwod_tile' ) as $key ) {
        register_post_meta( ORWOD_FILM, $key, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'edit_posts' ); } ) );
    }
} );

/** Root permalinks for published films: /blaze/ (when pretty permalinks are on). */
add_filter( 'post_type_link', function ( $link, $post ) {
    if ( ORWOD_FILM !== $post->post_type || ! get_option( 'permalink_structure' ) || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) || '' === $post->post_name ) { return $link; }
    return home_url( user_trailingslashit( $post->post_name ) );
}, 10, 2 );

/**
 * A single-segment address that is not a page or post resolves to the film with that slug.
 * Pages and posts always win, so nothing existing is ever hidden by a film.
 */
add_filter( 'request', function ( $vars ) {
    if ( is_admin() || isset( $vars['post_type'] ) || isset( $vars['p'] ) ) { return $vars; }
    $slug = $vars['name'] ?? ( $vars['pagename'] ?? '' );
    if ( ! is_string( $slug ) || '' === $slug || str_contains( $slug, '/' ) || isset( $vars['feed'] ) ) { return $vars; }
    if ( isset( $vars['pagename'] ) && get_page_by_path( $slug ) ) { return $vars; }
    $existing = get_posts( array( 'name' => $slug, 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) );
    if ( $existing ) { return $vars; }
    $film = get_posts( array( 'name' => $slug, 'post_type' => ORWOD_FILM, 'post_status' => array( 'publish', 'private' ), 'numberposts' => 1, 'fields' => 'ids' ) );
    if ( ! $film ) { return $vars; }
    $out = array( ORWOD_FILM => $slug, 'post_type' => ORWOD_FILM, 'name' => $slug );
    if ( ! empty( $vars['page'] ) ) { $out['page'] = $vars['page']; }
    return $out;
} );

/** Old ?orwod_film=slug and /orwod_film/slug addresses go to the root address. */
add_action( 'template_redirect', function () {
    if ( ! is_singular( ORWOD_FILM ) || ! get_option( 'permalink_structure' ) || is_preview() ) { return; }
    $wanted = get_permalink();
    $path = (string) wp_parse_url( $wanted, PHP_URL_PATH );
    if ( trailingslashit( orwod_request_path() ) !== trailingslashit( trim( substr( $path, strlen( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) ), '/' ) ) ) {
        wp_safe_redirect( $wanted, 301 );
        exit;
    }
}, 1 );

/** Film slugs must not collide with the theme's own original addresses. */
add_filter( 'wp_unique_post_slug_is_bad_flat_slug', function ( $bad, $slug, $post_type ) {
    if ( ORWOD_FILM !== $post_type ) { return $bad; }
    return $bad || isset( orwod_routes()[ $slug ] ) || in_array( $slug, array( 'contact', 'distribution', 'pages', 'wp-admin', 'feed' ), true );
}, 10, 3 );

// ----------------------------------------------------------------- helpers ---

/** Films shown on the homepage, in the original order (group "library" first, then "2024"). */
function orwod_listed_films( $group = null ) {
    static $cache = array();
    $key = null === $group ? '*' : $group;
    if ( isset( $cache[ $key ] ) ) { return $cache[ $key ]; }
    $args = array( 'post_type' => ORWOD_FILM, 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'no_found_rows' => true );
    $films = get_posts( $args );
    $groups = array_keys( orwod_film_groups() );
    $films = array_values( array_filter( $films, function ( $film ) use ( $group, $groups ) {
        $g = (string) get_post_meta( $film->ID, '_orwod_group', true );
        return null === $group ? ( '' !== $g && in_array( $g, $groups, true ) ) : $g === $group;
    } ) );
    usort( $films, function ( $a, $b ) use ( $groups ) {
        $ga = array_search( (string) get_post_meta( $a->ID, '_orwod_group', true ), $groups, true );
        $gb = array_search( (string) get_post_meta( $b->ID, '_orwod_group', true ), $groups, true );
        return array( $ga, $a->menu_order, $a->post_title ) <=> array( $gb, $b->menu_order, $b->post_title );
    } );
    $cache[ $key ] = $films;
    return $films;
}

/** Attachment ID for a film image: tile (homepage), banner (page header) or poster; each falls back sensibly. */
function orwod_film_image_id( $film, $role ) {
    $film = get_post( $film );
    $banner = (int) get_post_meta( $film->ID, '_orwod_banner', true );
    $tile = (int) get_post_meta( $film->ID, '_orwod_tile', true );
    $poster = (int) get_post_thumbnail_id( $film );
    $order = array(
        'tile'   => array( $tile, $banner, $poster ),
        'banner' => array( $banner, $tile, $poster ),
        'poster' => array( $poster ),
    )[ $role ] ?? array();
    foreach ( $order as $id ) {
        if ( $id && wp_attachment_is_image( $id ) ) { return $id; }
    }
    return 0;
}

function orwod_film_subtitle( $film ) {
    return trim( (string) get_post_meta( get_post( $film )->ID, '_orwod_subtitle', true ) );
}

function orwod_film_contact( $film ) {
    $email = sanitize_email( (string) get_post_meta( get_post( $film )->ID, '_orwod_contact', true ) );
    return is_email( $email ) ? $email : orwod_contact_details()['email'];
}

/** Homepage/related title tile. */
function orwod_film_tile( $film, $heading = 'h3', $sizes = '(max-width: 700px) 50vw, (max-width: 1100px) 50vw, 33vw' ) {
    $film = get_post( $film );
    $id = orwod_film_image_id( $film, 'tile' );
    $img = $id ? wp_get_attachment_image( $id, 'orwod-tile', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes ) ) : '<span class="tile-blank" aria-hidden="true"></span>';
    $sub = orwod_film_subtitle( $film );
    return '<a class="title-tile" href="' . esc_url( get_permalink( $film ) ) . '" data-reveal><span class="tile-media">' . $img . '</span><span class="tile-text"><' . $heading . ' class="tile-title">' . esc_html( get_the_title( $film ) ) . '</' . $heading . '>' . ( $sub ? '<span class="tile-sub">' . esc_html( $sub ) . '</span>' : '' ) . '<span class="tile-more" aria-hidden="true">VIEW TITLE ↗</span></span></a>';
}

/** Previous/next film in homepage order (unlisted films use all published films in title order). */
function orwod_adjacent_films( $film ) {
    $film = get_post( $film );
    $list = orwod_listed_films();
    $ids = wp_list_pluck( $list, 'ID' );
    $pos = array_search( $film->ID, $ids, true );
    if ( false === $pos ) {
        $list = get_posts( array( 'post_type' => ORWOD_FILM, 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
        $ids = wp_list_pluck( $list, 'ID' );
        $pos = array_search( $film->ID, $ids, true );
    }
    if ( false === $pos || count( $list ) < 2 ) { return array( null, null ); }
    $n = count( $list );
    return array( $list[ ( $pos - 1 + $n ) % $n ], $list[ ( $pos + 1 ) % $n ] );
}

// -------------------------------------------------------------- seeding ---

/** Block markup for a film record from parts/films.json (synopsis, credits, links, gallery). */
function orwod_film_content( $film, $media ) {
    $blocks = array();
    $p = function ( $html, $class = '' ) {
        $attrs = $class ? ' {"className":"' . $class . '"}' : '';
        return '<!-- wp:paragraph' . $attrs . ' --><p' . ( $class ? ' class="' . $class . '"' : '' ) . '>' . $html . '</p><!-- /wp:paragraph -->';
    };
    foreach ( $film['synopsis'] as $html ) { $blocks[] = $p( wp_kses( $html, orwod_inline_tags() ) ); }
    foreach ( $film['credits'] as $credit ) {
        $blocks[] = $p( '<strong>' . esc_html( $credit['label'] ) . ':</strong> ' . wp_kses( $credit['html'], orwod_inline_tags() ), 'film-credit' );
    }
    if ( $film['links'] ) {
        $links = array();
        foreach ( $film['links'] as $link ) {
            $links[] = '<a href="' . esc_url( $link['url'] ) . '" target="_blank" rel="noreferrer noopener">' . esc_html( $link['label'] ) . '</a>';
        }
        $blocks[] = $p( '<strong>Links:</strong> ' . implode( ' · ', $links ), 'film-links' );
    }
    $images = array();
    foreach ( $film['gallery'] as $key ) {
        $id = (int) ( $media[ $key ] ?? 0 );
        if ( ! $id ) { continue; }
        $full = wp_get_attachment_image_url( $id, 'full' );
        $large = wp_get_attachment_image_url( $id, 'large' );
        $alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
        $images[] = '<!-- wp:image {"id":' . (int) $id . ',"sizeSlug":"large","linkDestination":"media"} --><figure class="wp-block-image size-large"><a href="' . esc_url( $full ) . '"><img src="' . esc_url( $large ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . (int) $id . '"/></a></figure><!-- /wp:image -->';
    }
    if ( $images ) {
        $blocks[] = '<!-- wp:gallery {"linkTo":"media"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped">' . implode( '', $images ) . '</figure><!-- /wp:gallery -->';
    }
    return implode( "\n\n", $blocks );
}

function orwod_inline_tags() {
    return array( 'strong' => array(), 'em' => array(), 'br' => array(), 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) );
}

/** Film records still to be created (keys from parts/films.json without an existing film). */
function orwod_films_pending() {
    $pending = array();
    foreach ( orwod_data( 'films' ) as $film ) {
        if ( ! orwod_film_by_key( $film['key'] ) ) { $pending[] = $film; }
    }
    return $pending;
}

function orwod_film_by_key( $key ) {
    // Any status including the trash: a film deleted by an editor must not come back on a later setup run.
    $ids = get_posts( array( 'post_type' => ORWOD_FILM, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ), 'meta_key' => '_orwod_film_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    return $ids ? (int) $ids[0] : 0;
}

/**
 * Create every missing film (published, original slug, content, poster, banner, tile, SEO).
 * Existing films (matched by their archive key, including ones moved to the trash) are never changed.
 */
function orwod_films_seed() {
    if ( ! current_user_can( 'edit_posts' ) ) { return 0; }
    $created = 0;
    // Read fresh: the Media Library import that triggers seeding has just added the last images in this request.
    $media = array_filter( array_map( 'intval', (array) get_option( 'orwod_media_ids', array() ) ), function ( $id ) { return 'attachment' === get_post_type( $id ); } );
    $site = orwod_data( 'seo' )['site'] ?? get_bloginfo( 'name' );
    foreach ( orwod_films_pending() as $film ) {
        $id = wp_insert_post( array(
            'post_type'    => ORWOD_FILM,
            'post_status'  => 'publish',
            'post_title'   => $film['title'],
            'post_name'    => $film['key'],
            'menu_order'   => (int) $film['order'],
            'post_content' => orwod_film_content( $film, $media ),
        ), true );
        if ( is_wp_error( $id ) ) { continue; }
        update_post_meta( $id, '_orwod_film_key', $film['key'] );
        update_post_meta( $id, '_orwod_year', $film['year'] );
        update_post_meta( $id, '_orwod_group', $film['group'] );
        update_post_meta( $id, '_orwod_subtitle', $film['subtitle'] );
        update_post_meta( $id, '_orwod_contact', $film['contact'] );
        if ( $film['display_title'] !== $film['title'] ) { update_post_meta( $id, '_orwod_display_title', $film['display_title'] ); }
        if ( ! empty( $media[ $film['poster'] ] ) ) { set_post_thumbnail( $id, $media[ $film['poster'] ] ); }
        if ( ! empty( $media[ $film['banner'] ] ) ) { update_post_meta( $id, '_orwod_banner', (int) $media[ $film['banner'] ] ); }
        if ( $film['tile'] && ! empty( $media[ $film['tile'] ] ) && $film['tile'] !== $film['banner'] ) { update_post_meta( $id, '_orwod_tile', (int) $media[ $film['tile'] ] ); }
        // Original SEO title/description, also stored for Yoast SEO so it shows the same values when installed.
        $default_title = $film['title'] . ' - ' . $site;
        if ( ! empty( $film['seo']['title'] ) && $film['seo']['title'] !== $default_title ) {
            update_post_meta( $id, '_orwod_seo_title', $film['seo']['title'] );
            update_post_meta( $id, '_yoast_wpseo_title', $film['seo']['title'] );
        }
        if ( ! empty( $film['seo']['description'] ) ) {
            update_post_meta( $id, '_orwod_seo_description', $film['seo']['description'] );
            update_post_meta( $id, '_yoast_wpseo_metadesc', $film['seo']['description'] );
        }
        if ( $film['source_url'] ) { update_post_meta( $id, '_orwod_source_url', $film['source_url'] ); }
        ++$created;
    }
    return $created;
}

// --------------------------------------------------------- admin screens ---

add_action( 'add_meta_boxes_' . ORWOD_FILM, function () {
    add_meta_box( 'orwod_film_details', 'Film details', 'orwod_film_details_box', ORWOD_FILM, 'side', 'high' );
} );

function orwod_film_details_box( $post ) {
    wp_nonce_field( 'orwod_film_details', 'orwod_film_nonce' );
    $year = (string) get_post_meta( $post->ID, '_orwod_year', true );
    $group = (string) get_post_meta( $post->ID, '_orwod_group', true );
    $subtitle = (string) get_post_meta( $post->ID, '_orwod_subtitle', true );
    $contact = (string) get_post_meta( $post->ID, '_orwod_contact', true );
    echo '<p><label for="orwod_year"><strong>Year</strong></label><br><input type="text" id="orwod_year" name="orwod_year" value="' . esc_attr( $year ) . '" class="widefat" inputmode="numeric" maxlength="12"></p>';
    echo '<p><label for="orwod_group"><strong>Homepage</strong></label><br><select id="orwod_group" name="orwod_group" class="widefat">';
    foreach ( orwod_film_groups() as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $group, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select><span class="description">Order: Page Attributes → Order.</span></p>';
    echo '<p><label for="orwod_subtitle"><strong>Homepage tile line</strong></label><br><input type="text" id="orwod_subtitle" name="orwod_subtitle" value="' . esc_attr( $subtitle ) . '" class="widefat" placeholder="Director …"></p>';
    echo '<p><label for="orwod_contact"><strong>Contact email</strong></label><br><input type="email" id="orwod_contact" name="orwod_contact" value="' . esc_attr( $contact ) . '" class="widefat" placeholder="' . esc_attr( orwod_contact_details()['email'] ) . '"></p>';
    foreach ( array( '_orwod_banner' => 'Banner image (page header)', '_orwod_tile' => 'Homepage tile image (optional)' ) as $key => $label ) {
        $id = (int) get_post_meta( $post->ID, $key, true );
        $field = ltrim( $key, '_' );
        echo '<div class="orwod-image-field" data-field="' . esc_attr( $field ) . '"><p><strong>' . esc_html( $label ) . '</strong></p><div class="orwod-image-preview">' . ( $id ? wp_get_attachment_image( $id, 'medium', false, array( 'style' => 'max-width:100%;height:auto' ) ) : '' ) . '</div>';
        echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . ( $id ? (int) $id : '' ) . '"><p><button type="button" class="button orwod-image-choose">Choose image</button> <button type="button" class="button-link orwod-image-remove"' . ( $id ? '' : ' hidden' ) . '>Remove</button></p></div>';
    }
    echo '<p class="description">Poster: Featured image (“Poster”) below. Synopsis, credits, IMDB link and gallery are in the editor.</p>';
}

add_action( 'save_post_' . ORWOD_FILM, function ( $post_id ) {
    if ( ! isset( $_POST['orwod_film_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['orwod_film_nonce'] ) ), 'orwod_film_details' ) ) { return; }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    update_post_meta( $post_id, '_orwod_year', sanitize_text_field( wp_unslash( $_POST['orwod_year'] ?? '' ) ) );
    $group = sanitize_text_field( wp_unslash( $_POST['orwod_group'] ?? '' ) );
    update_post_meta( $post_id, '_orwod_group', array_key_exists( $group, orwod_film_groups() ) ? $group : '' );
    update_post_meta( $post_id, '_orwod_subtitle', sanitize_text_field( wp_unslash( $_POST['orwod_subtitle'] ?? '' ) ) );
    update_post_meta( $post_id, '_orwod_contact', sanitize_email( wp_unslash( $_POST['orwod_contact'] ?? '' ) ) );
    foreach ( array( 'orwod_banner', 'orwod_tile' ) as $field ) {
        $id = absint( $_POST[ $field ] ?? 0 );
        if ( $id && wp_attachment_is_image( $id ) ) { update_post_meta( $post_id, '_' . $field, $id ); }
        else { delete_post_meta( $post_id, '_' . $field ); }
    }
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || ORWOD_FILM !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
    wp_enqueue_media();
    wp_enqueue_script( 'orwod-film-admin', get_theme_file_uri( 'assets/orwod-admin.js' ), array( 'jquery' ), wp_get_theme()->get( 'Version' ), true );
} );

add_filter( 'manage_' . ORWOD_FILM . '_posts_columns', function ( $columns ) {
    $out = array( 'cb' => $columns['cb'], 'orwod_poster' => 'Poster', 'title' => $columns['title'], 'orwod_group' => 'Homepage', 'orwod_year' => 'Year', 'menu_order' => 'Order', 'date' => $columns['date'] );
    return $out;
} );
add_action( 'manage_' . ORWOD_FILM . '_posts_custom_column', function ( $column, $id ) {
    if ( 'orwod_poster' === $column ) { echo get_the_post_thumbnail( $id, array( 48, 72 ), array( 'style' => 'width:48px;height:auto' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    if ( 'orwod_group' === $column ) { echo esc_html( orwod_film_groups()[ (string) get_post_meta( $id, '_orwod_group', true ) ] ?? '' ); }
    if ( 'orwod_year' === $column ) { echo esc_html( (string) get_post_meta( $id, '_orwod_year', true ) ); }
    if ( 'menu_order' === $column ) { echo (int) get_post_field( 'menu_order', $id ); }
}, 10, 2 );
add_filter( 'manage_edit-' . ORWOD_FILM . '_sortable_columns', function ( $columns ) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
} );
add_action( 'pre_get_posts', function ( $query ) {
    if ( is_admin() && $query->is_main_query() && ORWOD_FILM === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
        $query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
    }
} );
