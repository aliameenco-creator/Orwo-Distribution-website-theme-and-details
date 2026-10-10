<?php
/** Search results, category/tag archives and other listings. */
get_header();
global $wp_query;
if ( is_search() ) {
    $orwod_found = (int) $wp_query->found_posts;
    orwod_page_hero( 'Search', 'Search', sprintf( '%d result%s for “%s”', $orwod_found, 1 === $orwod_found ? '' : 's', get_search_query() ) );
    echo '<div class="wrap search-wrap" data-reveal>';
    get_search_form();
    echo '</div>';
    orwod_render_post_list( $wp_query, max( 1, (int) get_query_var( 'paged' ) ), -1, 'No results. Try another title or name.' );
} else {
    orwod_page_hero( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : 'The Blog', is_archive() ? 'Blog' : '' );
    orwod_render_post_list( $wp_query, max( 1, (int) get_query_var( 'paged' ) ), is_category() ? get_queried_object_id() : -1, 'There are no published blog posts yet.' );
}
get_footer();
