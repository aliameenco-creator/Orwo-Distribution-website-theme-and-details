<?php
/** One film: banner, title and year, poster beside synopsis/credits/links, gallery, contact line, more titles. */
get_header();
while ( have_posts() ) :
    the_post();
    $orwod_film = get_post();
    $orwod_banner = orwod_film_image_id( $orwod_film, 'banner' );
    $orwod_poster = orwod_film_image_id( $orwod_film, 'poster' );
    $orwod_year = (string) get_post_meta( $orwod_film->ID, '_orwod_year', true );
    $orwod_display = (string) get_post_meta( $orwod_film->ID, '_orwod_display_title', true );
    $orwod_display = $orwod_display ? $orwod_display : get_the_title();
    $orwod_sub = orwod_film_subtitle( $orwod_film );
    $orwod_email = orwod_film_contact( $orwod_film );
    ?>
<section class="film-hero">
    <div class="film-hero-media" aria-hidden="true"><?php echo $orwod_banner ? wp_get_attachment_image( $orwod_banner, 'full', false, array( 'alt' => '', 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '100vw', 'class' => 'film-banner' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <div class="hero-shade" aria-hidden="true"></div><div class="grain" aria-hidden="true"></div>
    <div class="wrap film-hero-inner">
        <div class="crumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( home_url( '/#library' ) ); ?>">Distribution</a></div>
        <h1 class="film-title reveal-title"><?php the_title(); ?></h1>
        <?php if ( $orwod_year || $orwod_sub ) : ?>
        <p class="film-meta"><?php echo $orwod_year ? '<span class="chip">' . esc_html( $orwod_year ) . '</span>' : ''; ?><?php echo $orwod_sub ? '<span class="film-sub">' . esc_html( $orwod_sub ) . '</span>' : ''; ?></p>
        <?php endif; ?>
    </div>
</section>
<section class="section film-body"><div class="wrap film-grid<?php echo $orwod_poster ? '' : ' no-poster'; ?>">
    <?php if ( $orwod_poster ) : ?>
    <figure class="film-poster" data-reveal><a href="<?php echo esc_url( wp_get_attachment_image_url( $orwod_poster, 'full' ) ); ?>" data-lightbox><?php echo wp_get_attachment_image( $orwod_poster, 'large', false, array( 'sizes' => '(max-width: 800px) 70vw, 420px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></figure>
    <?php endif; ?>
    <div class="film-text">
        <h2 class="film-heading" data-reveal><?php echo esc_html( $orwod_display ); ?><?php echo $orwod_year ? ' <span>(' . esc_html( $orwod_year ) . ')</span>' : ''; ?></h2>
        <div class="prose film-content" data-reveal><?php the_content(); ?></div>
        <div class="film-contact" data-reveal><p><strong>Contact us for more details:</strong> <a href="mailto:<?php echo esc_attr( $orwod_email ); ?>"><?php echo esc_html( $orwod_email ); ?></a></p>
        <div class="actions"><a class="button" href="<?php echo esc_url( home_url( user_trailingslashit( 'contact-us' ) ) ); ?>">CONTACT US ↗</a></div></div>
    </div>
</div></section>
    <?php
    list( $orwod_prev, $orwod_next ) = orwod_adjacent_films( $orwod_film );
    if ( $orwod_prev && $orwod_next ) :
        ?>
<nav class="film-nav wrap" aria-label="More titles">
    <a class="film-nav-link prev" href="<?php echo esc_url( get_permalink( $orwod_prev ) ); ?>"><span>← Previous title</span><?php echo esc_html( get_the_title( $orwod_prev ) ); ?></a>
    <a class="film-nav-link next" href="<?php echo esc_url( get_permalink( $orwod_next ) ); ?>"><span>Next title →</span><?php echo esc_html( get_the_title( $orwod_next ) ); ?></a>
</nav>
        <?php
    endif;
    $orwod_more = array_values( array_filter( orwod_listed_films(), function ( $f ) use ( $orwod_film ) { return $f->ID !== $orwod_film->ID; } ) );
    if ( $orwod_more ) {
        $orwod_start = array_search( $orwod_next ? $orwod_next->ID : 0, wp_list_pluck( $orwod_more, 'ID' ), true );
        $orwod_pick = array_slice( array_merge( $orwod_more, $orwod_more ), false === $orwod_start ? 0 : (int) $orwod_start, 4 );
        echo '<section class="section more-titles"><div class="wrap"><div class="section-head" data-reveal><h2 class="display">More titles</h2><a class="text-link" href="' . esc_url( home_url( '/#library' ) ) . '">ALL TITLES ↗</a></div><div class="title-grid four">' . implode( '', array_map( 'orwod_film_tile', $orwod_pick ) ) . '</div></div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
endwhile;
get_footer();
