<?php
/** Blog article. */
get_header();
while ( have_posts() ) :
    the_post();
    $orwod_cover = has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'full' ) : '';
    ?>
<section class="page-hero article-hero"><div class="bg" data-speed="-0.15"<?php echo $orwod_cover ? ' style="background-image:url(' . esc_url( $orwod_cover ) . ')"' : ''; ?> aria-hidden="true"></div><div class="hero-shade" aria-hidden="true"></div><div class="wrap">
    <div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( orwod_blog_url() ); ?>">Blog</a></div>
    <h1 class="page-title reveal-title"><?php the_title(); ?></h1>
    <div class="article-meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time><span><?php echo esc_html( orwod_reading_time() ); ?></span><?php echo orwod_post_terms(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in builder. ?></div>
</div></section>
<article class="article prose" data-reveal><?php the_content(); ?></article>
<nav class="film-nav wrap" aria-label="More articles">
    <?php
    $orwod_prev = get_previous_post();
    $orwod_next = get_next_post();
    if ( $orwod_prev ) { echo '<a class="film-nav-link prev" href="' . esc_url( get_permalink( $orwod_prev ) ) . '"><span>← Previous</span>' . esc_html( get_the_title( $orwod_prev ) ) . '</a>'; }
    if ( $orwod_next ) { echo '<a class="film-nav-link next" href="' . esc_url( get_permalink( $orwod_next ) ) . '"><span>Next →</span>' . esc_html( get_the_title( $orwod_next ) ) . '</a>'; }
    ?>
</nav>
    <?php
    $orwod_related = orwod_related_posts();
    if ( $orwod_related ) {
        echo '<section class="section related"><div class="wrap"><div class="section-head" data-reveal><h2 class="display">More from the blog</h2><a class="text-link" href="' . esc_url( orwod_blog_url() ) . '">ALL ARTICLES ↗</a></div><div class="post-grid">' . implode( '', array_map( 'orwod_card_html', $orwod_related ) ) . '</div></div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
endwhile;
get_footer();
