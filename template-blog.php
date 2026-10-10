<?php
/** /blog: the original blog address ("The Blog — Thoughts, musings, and ruminations."), listing published posts. */
get_header();
orwod_page_hero( 'The Blog', 'Blog', 'Thoughts, musings, and ruminations.' );
$orwod_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ), isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$orwod_query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 13, 'paged' => $orwod_paged ) );
orwod_render_post_list( $orwod_query, $orwod_paged, 0, 'There are no published blog posts yet.' );
wp_reset_postdata();
get_footer();
