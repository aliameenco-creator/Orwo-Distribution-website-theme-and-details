<?php
/** Cookie Policy page, served at the original address /pages/cookie-policy/. */
get_header();
while ( have_posts() ) :
    the_post();
    orwod_page_hero( get_the_title(), get_the_title() );
    echo '<article class="article prose" data-reveal>';
    if ( '' === trim( (string) get_the_content() ) ) { orwod_theme_part( 'pages/cookie-policy' ); } else { the_content(); }
    echo '</article>';
endwhile;
get_footer();
