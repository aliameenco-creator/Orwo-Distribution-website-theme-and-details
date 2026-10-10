<?php
/** Blog page (/blog): published posts; any text in the page is shown above the list. */
get_header();
the_post();
orwod_page_hero( 'Blog' === get_the_title() ? 'The Blog' : get_the_title(), 'Blog', 'Thoughts, musings, and ruminations.' );
if ( '' !== trim( (string) get_the_content() ) ) { echo '<div class="article prose" data-reveal>'; the_content(); echo '</div>'; }
$orwod_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$orwod_query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 13, 'paged' => $orwod_paged ) );
orwod_render_post_list( $orwod_query, $orwod_paged, 0, 'There are no published blog posts yet.' );
wp_reset_postdata();
get_footer();
