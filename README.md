# ORWO Distribution WordPress theme

Custom theme for www.orwodistribution.com. Install the `orwo-distribution.zip` asset from the latest release
(Appearance → Themes → Add New → Upload), activate it, then click **Complete ORWO Distribution setup** in wp-admin.
Later versions install from Appearance → ORWO Distribution Updates.

Films are edited under **Films** (title, editor content, poster = featured image, Film details box for year,
homepage group/order, tile line, trailer link, laurels, banner/tile images and contact email). A film with a trailer
link (YouTube/Vimeo) plays it in a pop-up when its poster or "Now showing" title is clicked. Homepage groups: Library
titles and 2026 titles; set a film to draft to take it off the site. Enquiries from /contact-us are under **Enquiries**.

The Studio and About Jake Seal pages are kept for search engines only: live at /studio and /about-jake-seal and in the
sitemap, but not linked from the menu, footer or other pages and not shown in the site search.

Layouts, images and film data are generated from the site archive by `tools/orwo_distribution_theme_content.py`
in the migration workspace; content never contains credentials.
