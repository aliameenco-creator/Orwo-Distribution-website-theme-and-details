<?php
/** Blog (/blog): designed index and article helpers. The original blog had no published posts yet. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwod_has_posts() {
    static $has = null;
    if ( null === $has ) {
        $has = (bool) get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'no_found_rows' => true ) );
    }
    return $has;
}

function orwod_blog_url() {
    $blog = get_page_by_path( 'blog' );
    return $blog ? get_permalink( $blog ) : orwod_route_url( 'blog' );
}

function orwod_excerpt( $post = null, $words = 26 ) {
    $post = get_post( $post );
    if ( has_excerpt( $post ) ) { return get_the_excerpt( $post ); }
    return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $words );
}

function orwod_reading_time( $post = null ) {
    $words = str_word_count( wp_strip_all_tags( get_post( $post )->post_content ) );
    return max( 1, (int) round( $words / 220 ) ) . ' min read';
}

function orwod_card_image( $post = null, $size = 'large' ) {
    $post = get_post( $post );
    if ( ORWOD_FILM === $post->post_type ) {
        $id = orwod_film_image_id( $post, 'tile' );
        return $id ? wp_get_attachment_image( $id, $size, false, array( 'alt' => '', 'loading' => 'lazy' ) ) : '<span class="tile-blank" aria-hidden="true"></span>';
    }
    if ( has_post_thumbnail( $post ) ) {
        return get_the_post_thumbnail( $post, $size, array( 'loading' => 'lazy', 'alt' => '' ) );
    }
    return '<span class="tile-blank" aria-hidden="true"></span>';
}

function orwod_post_terms( $post = null ) {
    $out = array();
    foreach ( get_the_category( get_post( $post )->ID ) as $term ) {
        if ( 'uncategorized' === $term->slug ) { continue; }
        $out[] = '<a class="chip" href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
    }
    return implode( '', $out );
}

function orwod_card_html( $post = null ) {
    $post = get_post( $post );
    $link = get_permalink( $post );
    $meta = 'post' === $post->post_type
        ? '<time datetime="' . esc_attr( get_the_date( DATE_W3C, $post ) ) . '">' . esc_html( get_the_date( 'j M Y', $post ) ) . '</time><span>' . esc_html( orwod_reading_time( $post ) ) . '</span>'
        : '<span>' . esc_html( ORWOD_FILM === $post->post_type ? 'Film' : 'Page' ) . '</span>';
    return '<article class="post-card" data-reveal><a class="card-media" href="' . esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . orwod_card_image( $post ) . '</a><div class="card-body"><div class="card-meta">' . $meta . '</div><h3><a href="' . esc_url( $link ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h3><p>' . esc_html( orwod_excerpt( $post ) ) . '</p><a class="text-link" href="' . esc_url( $link ) . '">' . ( 'post' === $post->post_type ? 'Read article' : 'View' ) . ' ↗</a></div></article>';
}

function orwod_category_chips( $current = 0 ) {
    $terms = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
    if ( ! $terms ) { return ''; }
    $out = '<nav class="chips" aria-label="Blog categories"><a class="chip' . ( $current ? '' : ' active' ) . '" href="' . esc_url( orwod_blog_url() ) . '">All</a>';
    foreach ( $terms as $term ) {
        $out .= '<a class="chip' . ( (int) $current === (int) $term->term_id ? ' active' : '' ) . '" href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . ' <span>' . (int) $term->count . '</span></a>';
    }
    return $out . '</nav>';
}

/** Listing: newest article featured on page one, then a card grid with pagination. */
function orwod_render_post_list( WP_Query $query, $paged = 1, $current_term = 0, $empty = 'Nothing found.' ) {
    echo '<section class="section blog-index"><div class="wrap">' . orwod_category_chips( $current_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in builder.
    if ( ! $query->have_posts() ) {
        echo '<p class="empty-state">' . esc_html( $empty ) . '</p></div></section>';
        return;
    }
    $posts = $query->posts;
    if ( 1 === $paged && ! $current_term && 'post' === $posts[0]->post_type ) {
        $first = array_shift( $posts );
        echo '<article class="featured-post" data-reveal><a class="card-media" href="' . esc_url( get_permalink( $first ) ) . '" tabindex="-1" aria-hidden="true">' . orwod_card_image( $first, 'full' ) . '</a><div class="card-body"><span class="eyebrow">Latest article</span><div class="card-meta"><time>' . esc_html( get_the_date( 'j M Y', $first ) ) . '</time><span>' . esc_html( orwod_reading_time( $first ) ) . '</span></div><h2><a href="' . esc_url( get_permalink( $first ) ) . '">' . esc_html( get_the_title( $first ) ) . '</a></h2><p>' . esc_html( orwod_excerpt( $first, 40 ) ) . '</p><div class="chip-row">' . orwod_post_terms( $first ) . '</div><a class="button" href="' . esc_url( get_permalink( $first ) ) . '">READ ARTICLE ↗</a></div></article>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ( $posts ) { echo '<div class="post-grid">' . implode( '', array_map( 'orwod_card_html', $posts ) ) . '</div>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $links = paginate_links( array( 'total' => $query->max_num_pages, 'current' => $paged, 'prev_text' => '← Newer', 'next_text' => 'Older →' ) );
    if ( $links ) { echo '<nav class="pagination" aria-label="More pages">' . wp_kses_post( $links ) . '</nav>'; }
    echo '</div></section>';
}

function orwod_related_posts( $post = null, $count = 3 ) {
    $post = get_post( $post );
    return get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => $count, 'post__not_in' => array( $post->ID ) ) );
}
