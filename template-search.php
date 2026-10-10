<?php
/** /search: the original search address. Results are WordPress search (films, pages and posts) at /?s=. */
get_header();
orwod_page_hero( 'Search', 'Search' );
echo '<section class="section"><div class="wrap search-wrap" data-reveal>';
get_search_form();
echo '</div></section>';
get_footer();
