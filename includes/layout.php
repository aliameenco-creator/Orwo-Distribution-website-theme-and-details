<?php
/** Theme-owned layout parts generated from the archived ORWO Distribution site (tools/build_orwo_distribution_theme.py). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Generated JSON data in parts/ (fixed names only). */
function orwod_data( $name ) {
    static $cache = array();
    if ( ! isset( $cache[ $name ] ) ) {
        $file = preg_match( '~^[a-z-]+$~D', $name ) ? get_theme_file_path( 'parts/' . $name . '.json' ) : '';
        $cache[ $name ] = ( $file && is_readable( $file ) ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $cache[ $name ];
}

/** Resolve our controlled /assets/ tokens and root-relative links, on any (sub)directory install. */
function orwod_theme_fragment( $html ) {
    $html = orwod_media_swap( $html );
    $html = preg_replace_callback( '~(src|href)="(/assets/[^"<>]+)"~', function ( $m ) {
        return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"';
    }, $html );
    $html = preg_replace_callback( '~url\((/assets/[^)"\'<>]+)\)~', function ( $m ) {
        return 'url(' . esc_url( get_theme_file_uri( ltrim( $m[1], '/' ) ) ) . ')';
    }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $html );
}

/** Path of a generated part. Only fixed names are accepted, never user-controlled paths. */
function orwod_part_file( $name ) {
    if ( ! preg_match( '~^(?:header|footer|cta|home-hero|pages/[a-z0-9-]+)$~D', $name ) ) { return ''; }
    $file = get_theme_file_path( 'parts/' . $name . '.html' );
    return is_readable( $file ) ? $file : '';
}

function orwod_theme_part( $name, $return = false ) {
    $file = orwod_part_file( $name );
    if ( ! $file ) { return ''; }
    $html = (string) file_get_contents( $file );
    // Copyright year is always the current year (never a stale year copied from the old site).
    $html = str_replace( '<!--orwod:year-->', esc_html( wp_date( 'Y' ) ), $html );
    if ( str_contains( $html, '<!--orwod:blog-->' ) ) {
        $blog = orwod_has_posts() ? '<li><a href="/blog" data-nav="blog">BLOG</a></li>' : '';
        $html = str_replace( '<!--orwod:blog-->', $blog, $html );
    }
    if ( 'home-hero' === $name && orwod_home_tagline() ) {
        // The Distribution page's text is the tagline (animated in two lines, like the original).
        $words = preg_split( '~\s+~', orwod_home_tagline() );
        $half = (int) ceil( count( $words ) / 2 );
        $lines = '<span class="line"><span>' . esc_html( implode( ' ', array_slice( $words, 0, $half ) ) ) . '</span></span> <span class="line"><span>' . esc_html( implode( ' ', array_slice( $words, $half ) ) ) . '</span></span>';
        $html = preg_replace( '~(<p class="hero-lead reveal-lines">).*?(</p>)~s', '$1' . str_replace( '$', '\$', $lines ) . '$2', $html, 1 );
    }
    if ( str_contains( $html, '<!--orwod:hero-slides-->' ) ) {
        list( $slides, $now ) = orwod_hero_slides();
        $html = str_replace( array( '<!--orwod:hero-slides-->', '<!--orwod:hero-now-->' ), array( $slides, $now ), $html );
    }
    $html = orwod_theme_fragment( $html );
    if ( $return ) { return $html; }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML, not user input.
    return '';
}

/** Page hero: breadcrumb, title, optional lead and the original ORWO Studios aerial photograph. */
function orwod_page_hero( $title, $trail = '', $lead = '', $image = '' ) {
    if ( ! $image ) {
        $file = get_theme_file_path( 'parts/page-hero.txt' );
        $image = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : '';
    }
    $style = '';
    if ( preg_match( '~^/assets/[a-z0-9/._-]+$~', $image ) ) { $style = ' style="background-image:url(' . $image . ')"'; }
    elseif ( preg_match( '~^https?://~', $image ) ) { $style = ' style="background-image:url(' . esc_url( $image ) . ')"'; }
    $html = '<section class="page-hero"><div class="bg" data-speed="-0.15"' . $style . ' aria-hidden="true"></div><div class="hero-shade" aria-hidden="true"></div><div class="wrap"><div class="crumb"><a href="/">Home</a>' . ( $trail ? ' / ' . esc_html( $trail ) : '' ) . '</div><h1 class="page-title reveal-title">' . esc_html( $title ) . '</h1>' . ( $lead ? '<p class="page-lead">' . esc_html( $lead ) . '</p>' : '' ) . '</div></section>';
    echo orwod_theme_fragment( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above; fixed theme tokens.
}

/** Request path relative to the site root, e.g. "contact-us" or "pages/cookie-policy". */
function orwod_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/** Original URLs the theme serves until (or unless) a WordPress page with that address exists. */
function orwod_routes() {
    return array(
        'contact-us'          => 'contact',
        'studio'              => 'studio',
        'about-jake-seal'     => 'about-jake-seal',
        'blog'                => 'blog',
        'search'              => 'search',
        'pages/cookie-policy' => 'cookie-policy',
    );
}

/** Addresses that pointed at these pages on the old site (or are commonly typed) and now redirect permanently. */
function orwod_legacy_redirects() {
    return array(
        'contact'      => '/contact-us',
        'distribution' => '/',
        // The live homepage's REVENGERS CODE tile linked to this missing page.
        'capelli-code' => '/revengers-code',
    );
}

add_action( 'template_redirect', function () {
    if ( ! is_404() ) { return; }
    $path = orwod_request_path();
    $redirects = orwod_legacy_redirects();
    if ( isset( $redirects[ $path ] ) ) {
        wp_safe_redirect( home_url( user_trailingslashit( $redirects[ $path ] ) ), 301 );
        exit;
    }
    $routes = orwod_routes();
    if ( ! isset( $routes[ $path ] ) ) { return; }
    $GLOBALS['orwod_route'] = $routes[ $path ];
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 0 );

add_filter( 'template_include', function ( $template ) {
    $route = $GLOBALS['orwod_route'] ?? '';
    if ( ! $route ) { return $template; }
    $files = array(
        'contact'         => 'page-contact-us.php',
        'studio'          => 'template-studio.php',
        'about-jake-seal' => 'template-about-jake-seal.php',
        'blog'            => 'template-blog.php',
        'search'          => 'template-search.php',
        'cookie-policy'   => 'template-cookie-policy.php',
    );
    return get_theme_file_path( $files[ $route ] );
} );

/** Route key of the current view (theme route, homepage, or a WordPress page using one of the original addresses). */
function orwod_current_route() {
    if ( is_front_page() ) { return 'home'; }
    if ( ! empty( $GLOBALS['orwod_route'] ) ) { return $GLOBALS['orwod_route']; }
    if ( is_page() ) {
        $routes = orwod_routes();
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        if ( 'cookie-policy' === $slug ) { return 'cookie-policy'; }
        return $routes[ $slug ] ?? '';
    }
    return '';
}

function orwod_route_url( $route ) {
    $paths = array_flip( orwod_routes() );
    $path = 'home' === $route ? '' : ( $paths[ $route ] ?? '' );
    // Match the address WordPress actually serves (trailing slash per the permalink setting), so canonical never points at a redirect.
    return '' === $path ? home_url( '/' ) : home_url( user_trailingslashit( $path ) );
}

/** Films for the homepage hero: listed films with their own (unshared, large) banner still, in homepage order. */
function orwod_hero_films( $limit = 6 ) {
    // A stock photo used as the banner of several films is not a film still: skip banners shared between films.
    $uses = array();
    $films = get_posts( array( 'post_type' => ORWOD_FILM, 'post_status' => 'publish', 'numberposts' => 300, 'fields' => 'ids' ) );
    foreach ( $films as $id ) {
        $banner = orwod_film_image_id( $id, 'banner' );
        if ( $banner ) { $uses[ $banner ] = ( $uses[ $banner ] ?? 0 ) + 1; }
    }
    $out = array();
    foreach ( orwod_listed_films() as $film ) {
        $id = orwod_film_image_id( $film, 'banner' );
        $meta = $id ? wp_get_attachment_metadata( $id ) : array();
        if ( ! $id || 1 !== ( $uses[ $id ] ?? 0 ) || (int) ( $meta['width'] ?? 0 ) < 1200 ) { continue; }
        $out[] = array( $film, $id );
        if ( count( $out ) >= $limit ) { break; }
    }
    return $out;
}

/** Hero slideshow (crossfading, slow zoom) and the "now showing" panel with thumbnails (a click plays the trailer when the film has one). */
function orwod_hero_slides() {
    $films = orwod_hero_films();
    if ( ! $films ) { return array( '', '' ); }
    $slides = '';
    $thumbs = '';
    foreach ( $films as $n => list( $film, $id ) ) {
        $first = 0 === $n;
        $slides .= '<div class="hero-slide' . ( $first ? ' active' : '' ) . '">' . wp_get_attachment_image( $id, 'full', false, array( 'alt' => '', 'sizes' => '100vw', 'loading' => $first ? 'eager' : 'lazy', 'fetchpriority' => $first ? 'high' : 'low', 'decoding' => 'async' ) ) . '</div>';
        $thumb = orwod_film_image_id( $film, 'tile' );
        $sub = orwod_film_subtitle( $film );
        $thumbs .= '<li><a class="now-item' . ( $first ? ' active' : '' ) . '" href="' . esc_url( get_permalink( $film ) ) . '" data-slide="' . (int) $n . '"' . orwod_trailer_attrs( $film ) . '>'
            . ( $thumb ? wp_get_attachment_image( $thumb, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'class' => 'now-thumb' ) ) : '' )
            . '<span class="now-text"><span class="now-title">' . esc_html( get_the_title( $film ) ) . '</span>' . ( $sub ? '<span class="now-sub">' . esc_html( $sub ) . '</span>' : '' ) . '</span><span class="now-bar" aria-hidden="true"></span></a></li>';
    }
    $now = '<div class="hero-now" data-reveal><span class="eyebrow">NOW SHOWING</span><ol class="now-list">' . $thumbs . '</ol></div>';
    return array( $slides, $now );
}
