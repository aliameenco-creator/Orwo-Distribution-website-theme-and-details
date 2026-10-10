<?php
/** Studio page: the original ORWO Studios layout; the intro ("Hollywood South" and text) is the page's editable content. */
get_header();
$orwod_html = orwod_theme_part( 'pages/studio', true );
while ( have_posts() ) {
    the_post();
    $orwod_intro = trim( (string) get_the_content() );
    if ( '' !== $orwod_intro ) {
        $orwod_content = '<div class="prose studio-copy" data-reveal>' . apply_filters( 'the_content', $orwod_intro ) . '</div>';
        $orwod_html = preg_replace_callback( '~<!--orwod:intro-->.*?<!--/orwod:intro-->~s', function () use ( $orwod_content ) { return $orwod_content; }, $orwod_html, 1 );
    }
}
echo $orwod_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme layout and filtered post content.
get_footer();
