<?php get_header(); orwod_page_hero( 'Page not found' ); ?>
<section class="section"><div class="wrap"><p>The page you were looking for has moved or no longer exists.</p><div class="actions"><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">DISTRIBUTION ↗</a><a class="button outline" href="<?php echo esc_url( home_url( user_trailingslashit( 'search' ) ) ); ?>">SEARCH ↗</a></div></div></section>
<?php get_footer(); ?>
