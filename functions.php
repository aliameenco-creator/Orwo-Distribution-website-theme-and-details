<?php
/** ORWO Distribution theme. Films are editable posts; page layouts are generated from the archived site; settings and enquiries live in WordPress. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/github-updater.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/films.php';
require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/contact-form.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_editor_style( array( 'assets/orwod-editor.css' ) );
    add_image_size( 'orwod-tile', 900, 600, true );
} );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'orwod-theme', get_theme_file_uri( 'assets/orwod.css' ), array(), $version );
    wp_enqueue_script( 'orwod-theme', get_theme_file_uri( 'assets/orwod.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/** Mark the document before first paint so animations never hide content when JavaScript is off; preload the brand fonts. */
add_action( 'wp_head', function () {
    echo "<script>document.documentElement.classList.add('js')</script>\n";
    foreach ( array( 'oswald', 'cardo', 'source-sans' ) as $font ) {
        echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
    if ( ! has_site_icon() ) {
        $icon = esc_url( get_theme_file_uri( 'assets/orwod-favicon.png' ) );
        echo '<link rel="icon" type="image/png" href="' . $icon . '"><link rel="apple-touch-icon" href="' . $icon . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
    }
}, 1 );

/** The original site's fonts (Oswald titles, Cardo headings, Source Sans Pro text), bundled with the theme (SIL Open Font License). */
function orwod_font_faces() {
    $dir = esc_url( get_theme_file_uri( 'assets/fonts/' ) );
    $faces = array(
        array( 'Oswald', 'normal', '200 700', 'oswald' ),
        array( 'Cardo', 'normal', '400', 'cardo' ),
        array( 'Cardo', 'normal', '700', 'cardo-bold' ),
        array( 'Source Sans Pro', 'normal', '300', 'source-sans-light' ),
        array( 'Source Sans Pro', 'normal', '400', 'source-sans' ),
        array( 'Source Sans Pro', 'italic', '400', 'source-sans-italic' ),
        array( 'Source Sans Pro', 'normal', '600 700', 'source-sans-bold' ),
    );
    $css = '';
    foreach ( $faces as $f ) {
        $css .= "@font-face{font-family:'{$f[0]}';font-style:{$f[1]};font-weight:{$f[2]};font-display:swap;src:url({$dir}{$f[3]}.woff2) format('woff2')}";
    }
    return $css;
}
add_action( 'wp_head', function () {
    echo '<style id="orwod-fonts">' . orwod_font_faces() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped URL.
}, 2 );
add_action( 'admin_head', function () {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( $screen && $screen->is_block_editor() ) { echo '<style id="orwod-fonts">' . orwod_font_faces() . "</style>\n"; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} );

add_filter( 'body_class', function ( $classes ) {
    if ( is_front_page() ) { $classes[] = 'orwod-home'; }
    if ( ! empty( $GLOBALS['orwod_route'] ) ) { $classes[] = 'route-' . sanitize_html_class( $GLOBALS['orwod_route'] ); }
    return $classes;
} );

/** Content uses root-relative links; keep them valid on subdirectory installs. */
add_filter( 'the_content', function ( $content ) {
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $content );
}, 20 );
