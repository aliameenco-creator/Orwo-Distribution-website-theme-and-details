<?php
/**
 * /contact-us: the original ORWO Distribution email form plus the original contact details. Used for a page
 * with slug "contact-us", and also served at /contact-us when no such page exists (/contact redirects here).
 */
get_header();
$orwod_details = orwod_contact_details();
$orwod_page = get_page_by_path( 'contact-us' );
$orwod_page = ( $orwod_page && 'publish' === $orwod_page->post_status ) ? $orwod_page : null;
orwod_page_hero( $orwod_page ? get_the_title( $orwod_page ) : $orwod_details['title'], 'Contact Us', $orwod_details['intro'], $orwod_details['image'] );
$orwod_intro = $orwod_page ? trim( apply_filters( 'the_content', $orwod_page->post_content ) ) : '';
$orwod_map = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $orwod_details['address'] );
?>
<section class="section contact-section"><div class="wrap contact-grid">
    <div class="contact-info" data-reveal>
        <span class="eyebrow">CONTACT US</span>
        <h2 class="display">ORWO Distribution</h2>
        <?php if ( $orwod_intro ) : ?><div class="prose"><?php echo $orwod_intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered post content. ?></div><?php endif; ?>
        <dl class="contact-list">
            <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5a7 7 0 0 0-7 7c0 5.2 7 12 7 12s7-6.8 7-12a7 7 0 0 0-7-7zm0 9.6a2.6 2.6 0 1 1 0-5.2 2.6 2.6 0 0 1 0 5.2z" fill="currentColor"/></svg>Address</dt><dd><a href="<?php echo esc_url( $orwod_map ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $orwod_details['address'] ); ?></a></dd></div>
            <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5.5h18v13H3zm1.6 1.4L12 12.6l7.4-5.7" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>Email</dt><dd><a href="mailto:<?php echo esc_attr( $orwod_details['email'] ); ?>"><?php echo esc_html( $orwod_details['email'] ); ?></a></dd></div>
            <?php if ( $orwod_details['phone'] ) : ?><div><dt>Phone</dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '~[^\d+]~', '', $orwod_details['phone'] ) ); ?>"><?php echo esc_html( $orwod_details['phone'] ); ?></a></dd></div><?php endif; ?>
        </dl>
        <a class="text-link" href="<?php echo esc_url( home_url( user_trailingslashit( 'studio' ) ) ); ?>">ORWO STUDIOS ↗</a>
    </div>
    <div class="contact-form-card" data-reveal><?php orwod_contact_form(); ?></div>
</div></section>
<?php
get_footer();
