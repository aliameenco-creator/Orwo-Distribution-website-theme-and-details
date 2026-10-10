<?php
/** About Jake Seal page: editable biography beside the portrait (featured image). */
get_header();
while ( have_posts() ) :
    the_post();
    orwod_page_hero( get_the_title(), get_the_title() );
    if ( '' === trim( (string) get_the_content() ) ) { orwod_theme_part( 'pages/about-jake-seal' ); continue; }
    ?>
<section class="section jake"><div class="wrap split<?php echo has_post_thumbnail() ? '' : ' single'; ?>">
    <div class="split-text prose" data-reveal><?php the_content(); ?></div>
    <?php if ( has_post_thumbnail() ) : ?><div class="sticky-media" data-reveal><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
</div></section>
    <?php
endwhile;
get_footer();
