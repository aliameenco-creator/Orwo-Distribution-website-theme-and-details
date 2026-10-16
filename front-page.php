<?php
/** Site root: the ORWO Distribution homepage (hero, Library titles, 2026 titles). Tiles with a trailer play it in a pop-up. */
get_header();
orwod_theme_part( 'home-hero' );
$orwod_groups = orwod_film_groups();
$orwod_ids = array( 'library' => 'library', '2026' => 'titles-2026' );
foreach ( array( 'library', '2026' ) as $orwod_group ) {
    $orwod_films = orwod_listed_films( $orwod_group );
    if ( ! $orwod_films ) { continue; }
    echo '<section class="section titles" id="' . esc_attr( $orwod_ids[ $orwod_group ] ) . '"><div class="wrap"><div class="section-head" data-reveal><h2 class="display">' . esc_html( $orwod_groups[ $orwod_group ] ) . '</h2><span class="count-label">' . count( $orwod_films ) . ' titles</span></div>';
    echo '<div class="title-grid">' . implode( '', array_map( function ( $film ) { return orwod_film_tile( $film, 'h3', '(max-width: 700px) 50vw, (max-width: 1100px) 50vw, 33vw', true ); }, $orwod_films ) ) . '</div></div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in builder.
}
if ( ! orwod_listed_films() && current_user_can( 'manage_options' ) ) {
    echo '<section class="section"><div class="wrap"><p class="empty-state">The films are created by “Complete ORWO Distribution setup” in wp-admin.</p></div></section>';
}
get_footer();
