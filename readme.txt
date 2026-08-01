=== WPeMatico RSS Feed Reader ===
Contributors: etruel, khaztiel, gerarjos14
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B8V39NWK3NFQU
Tags: wpematico, rss reader, autoblog, rss, feed
Requires PHP: 7.0
Requires at least: 5.9
Tested up to: 7.0
Stable tag: 2.0.0
License: GPLv2 or later.

Add On for WPeMatico plugin. Adds a feature to print pre-formatted feeds contents directly on your pages, posts, widgets, etc. 

== Description ==
The RSS Feed Reader addon further enhances the functionality of WPeMatico by allowing to read and display the RSS feed results using shortcodes, page templates or the get_the_content function on your WordPress site without creating the posts.

= How it works =

The RSS Feed Reader plugin simplifies RSS feed integration and allows you to customise the way feed results are dynamically displayed on your WordPress site with flexible options such as shortcodes, page templates and WordPress’ internal function to change the content with get_the_content filter on each selected Custom Post Type.

In addition many options of the WPeMatico standard campaigns are available to parse and filter the content displayed in the feeds.
 
= Features =

The most important characteristics that makes this add-on the first choice comparing to others are the next ones:

> * Allows to display RSS feed results dynamically, without create new post types on your WordPress site.
> * Set a maximum limit on how many feed items will be shown in the selected post type, regardless of the method you have chosen.
> * Choose a specific post or post-type previously created to display the feed content using the WordPress function get_the_content.
> * Page template for pages only, select a previously created page to display the feed content, you can also choose the default page template from the plugin or theme’s page templates.
> * Generate a [shortcode] using the campaign slug and insert it anywhere on your website (posts, pages, cpt, widgets, and more) to display the feed content.
> * Choose a ready-made display layout — List, Grid, or Excerpt & Thumbnail — from a visual picker in the campaign editor.
> * Customize the HTML structure where the feed items will be displayed, adjust the layout, styles and visual components to perfectly fit your site.
 
= Requirements =
As WPeMatico Add-on requires WPeMatico base plugin installed and activated.  
PHP 7.0 or higher
Works with Professional, Better Excerpts and Full Content add-ons but there are not required.

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

= Using the Plugin Manager =

1. Click Plugins
2. Click Add New
3. Search for `wpematico_rss_feed_reader`
4. Click Install
5. Click Install Now
6. Click Activate Plugin
7. Now you use the addon features in WPeMatico's campaigns

= Manually =

1. Upload `wpematico_rss_feed_reader` folder to the `/wp-content/plugins/` directory
2. Activate the plugin from the WPeMatico Extensions menu.

== Screenshots ==

1. RSS Feed Reader Campaign details.
2. Metabox details.

== Frequently Asked Questions ==

= Can I use this plug-in without WPeMatico plugin? =

No. Plugin requires WPeMatico Free Version installed and activated.

== Changelog ==

= 2.0.0 =
* New: Display layouts — choose List, Grid, or Excerpt & Thumbnail from a visual picker in the campaign editor.
* New: "Customize HTML" advanced mode to edit the per-item template without changing the selected layout.
* New: ItemImage template tag to show each item's image (from the source feed) in the image-based layouts.
* Improved: feed items are stored as structured data and rendered on display, so layout or template changes apply immediately without re-fetching. Rendered output is cached for performance.
* Improved: a campaign with a layout selected but no custom template now renders that layout's preset (Grid / Excerpt & Thumbnail) instead of the plain list template — so campaigns imported from other plugins keep the right arrangement.

= 1.0.0 =
* First release

== Upgrade Notice ==

= 2.0.0 =
Adds selectable display layouts (List, Grid, Excerpt & Thumbnail) and an advanced HTML customization mode. Existing campaigns keep working; their items refresh into the new format on the next fetch.

= 1.0.0 =
Initial version