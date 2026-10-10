<?php
/**
 * Original SEO data. Theme-served routes (/contact-us, /studio, /about-jake-seal, /blog, /search,
 * /pages/cookie-policy) and the homepage use parts/seo.json; films use their original title/description
 * (stored on each film) or the start of the synopsis. When an SEO plugin (Yoast, Rank Math, AIOSEO,
 * SEOPress) is active it owns the tags of WordPress content; the theme still titles the routes it serves itself.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwod_seo_data( $route ) {
    return orwod_data( 'seo' )[ $route ] ?? array();
}

function orwod_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/** Title, description, canonical and share image for the current view (empty when not ours). */
function orwod_current_seo() {
    $route = orwod_current_route();
    if ( $route ) {
        $seo = orwod_seo_data( $route );
        return array(
            'title'       => $seo['title'] ?? '',
            'description' => ( $seo['description'] ?? '' ) ? $seo['description'] : ( orwod_seo_data( 'home' )['description'] ?? '' ),
            'url'         => orwod_route_url( $route ),
            'image'       => get_theme_file_uri( 'assets/orwod-share.jpg' ),
            'virtual'     => ! empty( $GLOBALS['orwod_route'] ),
        );
    }
    if ( is_singular( ORWOD_FILM ) ) {
        $film = get_queried_object();
        $title = (string) get_post_meta( $film->ID, '_orwod_seo_title', true );
        // Original format "BLAZE - ORWO Distribution | Boutique world sales agency" (a plain hyphen; WordPress would texturize it to a dash).
        if ( '' === $title ) { $title = wp_strip_all_tags( get_the_title( $film ) ) . ' - ' . orwod_site_title(); }
        $desc = (string) get_post_meta( $film->ID, '_orwod_seo_description', true );
        if ( '' === $desc ) { $desc = has_excerpt( $film ) ? get_the_excerpt( $film ) : wp_trim_words( wp_strip_all_tags( $film->post_content ), 28, '…' ); }
        if ( '' === trim( $desc ) ) { $desc = get_the_title( $film ) . ' – ' . ( orwod_seo_data( 'home' )['description'] ?? '' ); }
        $image = orwod_film_image_id( $film, 'banner' );
        return array( 'title' => $title, 'description' => $desc, 'url' => get_permalink( $film ), 'image' => $image ? wp_get_attachment_image_url( $image, 'large' ) : get_theme_file_uri( 'assets/orwod-share.jpg' ), 'virtual' => false );
    }
    return array();
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $seo = orwod_current_seo();
    if ( ! $seo || ( orwod_seo_plugin_active() && ! $seo['virtual'] ) ) { return $title; }
    return '' !== $seo['title'] ? $seo['title'] : $title;
}, 99 );

add_action( 'wp_head', function () {
    if ( orwod_seo_plugin_active() ) { return; }
    $seo = orwod_current_seo();
    if ( ! $seo ) { return; }
    $title = '' !== $seo['title'] ? $seo['title'] : wp_get_document_title();
    echo '<meta name="description" content="' . esc_attr( $seo['description'] ) . '">' . "\n";
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $seo['url'] ) . '">' . "\n"; }
    echo '<meta property="og:type" content="' . ( is_singular( ORWOD_FILM ) ? 'video.movie' : 'website' ) . '"><meta property="og:site_name" content="' . esc_attr( orwod_site_title() ) . '"><meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:description" content="' . esc_attr( $seo['description'] ) . '"><meta property="og:url" content="' . esc_url( $seo['url'] ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $seo['image'] ) . '"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . esc_attr( $title ) . '"><meta name="twitter:description" content="' . esc_attr( $seo['description'] ) . '"><meta name="twitter:image" content="' . esc_url( $seo['image'] ) . '">' . "\n";
}, 3 );

/** Structured data: the organisation on the homepage, Movie for each film (from the film's own credits). */
add_action( 'wp_head', function () {
    if ( is_front_page() && ! orwod_seo_plugin_active() ) {
        $contact = orwod_contact_details();
        $data = array( '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'ORWO Distribution', 'url' => home_url( '/' ), 'logo' => get_theme_file_uri( 'assets/orwod-favicon.png' ), 'email' => $contact['email'], 'address' => $contact['address'] );
        echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
    if ( ! is_singular( ORWOD_FILM ) ) { return; }
    $film = get_queried_object();
    $data = array( '@context' => 'https://schema.org', '@type' => 'Movie', 'name' => get_the_title( $film ), 'url' => get_permalink( $film ) );
    $year = (string) get_post_meta( $film->ID, '_orwod_year', true );
    if ( preg_match( '~^\d{4}$~', $year ) ) { $data['dateCreated'] = $year; }
    $poster = orwod_film_image_id( $film, 'poster' ) ? orwod_film_image_id( $film, 'poster' ) : orwod_film_image_id( $film, 'banner' );
    if ( $poster ) { $data['image'] = wp_get_attachment_image_url( $poster, 'large' ); }
    $text = wp_strip_all_tags( $film->post_content );
    if ( preg_match( '~Directors?:\s*([^\n]+)~', $text, $m ) ) {
        $data['director'] = array_map( function ( $n ) { return array( '@type' => 'Person', 'name' => $n ); }, orwod_split_names( $m[1] ) );
    }
    if ( preg_match( '~(?:Featuring|Starring|Cast):\s*([^\n]+)~', $text, $m ) ) {
        $data['actor'] = array_map( function ( $n ) { return array( '@type' => 'Person', 'name' => $n ); }, orwod_split_names( $m[1] ) );
    }
    if ( preg_match_all( '~href="(https?://(?:www\.)?imdb\.com/[^"]+)"~', $film->post_content, $m ) ) { $data['sameAs'] = array_values( array_unique( $m[1] ) ); }
    echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}, 4 );

function orwod_split_names( $list ) {
    $names = preg_split( '~\s*(?:,|\band\b|&)\s*~u', trim( html_entity_decode( $list ) ) );
    return array_values( array_filter( array_map( 'trim', $names ) ) );
}

/** Add the theme-served original URLs to WordPress's XML sitemap (films are listed by WordPress itself). */
add_action( 'init', function () {
    if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }
    wp_register_sitemap_provider( 'orwod', new class() extends WP_Sitemaps_Provider {
        public function __construct() { $this->name = 'orwod'; $this->object_type = 'orwod'; }
        public function get_url_list( $page_num, $object_subtype = '' ) {
            $urls = array( array( 'loc' => home_url( '/' ) ) );
            foreach ( orwod_routes() as $path => $route ) {
                if ( 'search' === $route || get_page_by_path( $path ) ) { continue; }
                $urls[] = array( 'loc' => orwod_route_url( $route ) );
            }
            return $urls;
        }
        public function get_max_num_pages( $object_subtype = '' ) { return 1; }
    } );
} );

/** Search results and the search page are not indexed. */
add_filter( 'wp_robots', function ( $robots ) {
    if ( is_search() || 'search' === ( $GLOBALS['orwod_route'] ?? '' ) ) { $robots['noindex'] = true; $robots['follow'] = true; }
    return $robots;
} );
