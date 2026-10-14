# Changelog

## 0.3.1 — 2026-10-14

- Colours: the bright navy blue is replaced by a dark charcoal grey (header, hero and tile overlays, contact band,
  buttons' text, footer), with a soft silver accent instead of the lavender-blue. Gold laurels unchanged.

## 0.3.0 — 2026-10-14

ORWO's catalogue update.

- Trailers: clicking a poster/tile, or a title in the hero's "Now showing" panel, plays the film's trailer in a
  pop-up (closing stops it; the link still opens the film page with ctrl/cmd-click or without JavaScript). Film
  pages show a "Watch trailer" button and the trailer. 18 official trailers added. Edit under Films → Film details
  → "Trailer link" (YouTube or Vimeo).
- Laurels: new "Laurels / awards" field (one per line: award | festival), shown on the film page and as a badge on
  the tile. THE FALLOW FEW (Boston Film Festival: Best Actor, Best Story, Emerging Filmmaker; Kingston; Sedona),
  MILAREPA (Boston International Film Festival) and MRAZ / FROST (Sitges 2026, Official Competition).
- New films: MRAZ / FROST (first in Library), MILAREPA, FLUSHY, and the 2026 titles AMANI CHRONICLES (dir. Dror
  Soref) and ALL ABOUT HER (dir. Kunal Kohli).
- Library order: MRAZ / FROST, THE FALLOW FEW, MILAREPA first; ALL THE LITTLE THINGS WE KILL, YOU ARE HERE and
  SKYPATHS last. The hero's "Now showing" follows this order.
- "Selected 2024 titles" is now "2026 TITLES" (menu and homepage): AMANI CHRONICLES, THE WELL, ALL ABOUT HER. The
  other 2024 titles are drafts (kept under Films, not public).
- Nothing links to the Studio or About Jake Seal pages any more (menu, footer, contact page, homepage ORWO Studios
  band removed). Both pages stay live at their addresses and in the sitemap for search engines, and are left out of
  the site's own search results.
- Existing sites: the order, groups, drafts, trailers and laurels are applied once automatically after updating
  (trailers/laurels only where a film has none). Click "Complete ORWO Distribution setup" to add the new films.

## 0.2.0 — 2026-10-10

- New homepage hero: full-colour stills from the films' own banners crossfade with a slow zoom, a "Now showing"
  panel lists those titles (hover to preview, click to open the film), soft gradient instead of the dark navy flood.
  Phones get a swipeable now-showing strip. Stills do not rotate for visitors who prefer reduced motion.
- Header: flat menu without a dropdown — DISTRIBUTION, 2024 TITLES, STUDIO, (BLOG once posts exist), search,
  CONTACT US. About Jake Seal is no longer in the top menu (still in the footer).
- Pages: the original pages now appear under Pages and are editable — Distribution (homepage; its text is the hero
  tagline), Studio (intro text; the services, quotes and gallery stay designed), About Jake Seal (text + portrait),
  CONTACT US, Blog and Cookie Policy (still at /pages/cookie-policy). Existing sites: click "Complete ORWO
  Distribution setup" once after updating to create them. Existing pages are never overwritten.

## 0.1.0 — 2026-10-10

First release of the ORWO Distribution theme, rebuilt from the archived www.orwodistribution.com (Strikingly).

- Original logo, favicon, navy brand colour (#000066) and fonts (Oswald, Cardo, Source Sans Pro; bundled, SIL OFL).
- Homepage: animated hero with the original tagline over a drifting strip of film stills, "Library titles" and
  "Selected 2024 titles" grids in the original order, ORWO Studios band, contact band.
- Films: editable "Films" post type (47 titles) at the original root addresses (/blaze, /77 …). One-click setup creates
  them with poster, banner, synopsis, credits, IMDB link and gallery; re-running never overwrites edits or restores
  deleted films. Film pages: banner hero, sticky poster, credits, gallery lightbox, contact line, previous/next, more titles.
- Original pages at their original URLs: /studio, /about-jake-seal, /contact-us, /blog, /search, /pages/cookie-policy.
  /contact, /distribution and /capelli-code redirect (301).
- Contact form recreated from the original (Name, Email, Message, "Submit", "Thanks for your submission!"): saved under
  Enquiries and emailed to hello@orwodistribution.com (configurable), honeypot/time/rate-limit spam protection.
- SEO: original titles and descriptions, canonical, Open Graph/Twitter, Organization and Movie structured data,
  theme routes and films in the WordPress sitemap. Yoast/Rank Math take over when installed.
- Media Library import of all 152 images with descriptive file names and alt text.
- GitHub release updater (Appearance → ORWO Distribution Updates).
