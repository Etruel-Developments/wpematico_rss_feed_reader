<?php
// don't load directly 
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

add_filter('wpematico_help_campaign', 'wpematico_helpcampaign_rss_feed_reader');

function wpematico_helpcampaign_rss_feed_reader($helpcampaign) {
	$helpcampaignRss = array(
		'RSS Feed Reader' => array(
			'max_to_show' => array(
				'title' => esc_html__('Max items to show.', 'wpematico-rss-feed-reader'),
				'tip' => esc_html__('Maximum items to be shown in each displayed feed: it sets a limit on how many feed items are shown in the selected post type, whichever method you have chosen. Also, make sure that this value is not less than the "Max items to create on each fetch" field, which controls the items processed during the campaign run.', 'wpematico-rss-feed-reader')
			),
			'get_the_content' => array(
				// translators: %s: shows WP function name inside html 'code' tags
				'title' => sprintf(
						esc_html__('Use %s WordPress Filter.', 'wpematico-rss-feed-reader'),
						'<code>get_the_content</code>'
					),
				// translators: %s: shows WP function name inside html 'code' tags
				'tip' => sprintf(
						esc_html__('Lets you choose the post type where the feed content will be displayed. For example, if you select "Page", "Post" or another custom post type, you will need to select the specific post where the content will be displayed, using the WordPress %s function.', 'wpematico-rss-feed-reader'),
						'<code>get_the_content</code>'
					),
			),

			'rss_page_template' => array(
				'title' => esc_html__('RSS Page Template.', 'wpematico-rss-feed-reader'),
				'tip' => esc_html__('Only for pages: it lets you select a page you already created, and its page template, to display the feed content. You can choose the default template of the plugin or one of the theme templates.', 'wpematico-rss-feed-reader')
			),
			
			'shortcode' => array(
				'title' => esc_html__('Use Shortcode.', 'wpematico-rss-feed-reader'),
				'tip' => esc_html__('Generate a shortcode from the campaign slug. This shortcode can be inserted anywhere on your website to display the content of the feed set up in the campaign, and editing the campaign slug changes it.', 'wpematico-rss-feed-reader')
			),

			'rss_page_template_html' => array(
				'title' => esc_html__('Template feed.', 'wpematico-rss-feed-reader'),
				'tip' => esc_html__('Lets you customize the HTML structure the feed items are displayed in, and adjust the layout, styles and visual components to fit your site. Be careful not to delete the variables that carry each content.', 'wpematico-rss-feed-reader')
			),
		)
	);

	return array_merge($helpcampaign, $helpcampaignRss);
}