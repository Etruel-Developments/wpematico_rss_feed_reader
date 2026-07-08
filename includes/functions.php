<?php

/**
 * Helper Functions
 *
 * @package     WPeMatico\PluginName\Functions
 * @since       1.0.0
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

class wpematico_rss_feed_functions {
	public static function init(){
		add_action('template_redirect', array(__CLASS__, 'wpematico_rss_feed_initiation'), 999999);
		add_filter('theme_page_templates', array(__CLASS__,'wpematico_add_custom_template'));
		add_action('admin_action_wpematico_reset_campaign', array(__CLASS__, 'wpematico_reset_campaign'), 1);
		add_action('save_post_wpematico', array(__CLASS__, 'flush_on_save'));
		add_filter('wpematico_fetch_posts_summary', array(__CLASS__, 'reader_fetch_summary'), 10, 3);
	}

	/**
	 * Reader campaigns insert no posts, so core's "Processed Posts: 0" is misleading.
	 * Report the number of feed items currently stored (what the shortcode will show).
	 */
	public static function reader_fetch_summary($summary, $campaign, $fetched_posts){
		if (empty($campaign['campaign_type']) || $campaign['campaign_type'] !== 'rss_reader') {
			return $summary;
		}
		$campaign_id = empty($campaign['ID']) ? 0 : $campaign['ID'];
		$count = $campaign_id ? count(get_post_meta($campaign_id, 'feed_items')) : 0;
		/* translators: %s number of stored feed items */
		return sprintf(_n('%s item stored', '%s items stored', $count, 'wpematico-rss-feed-reader'), number_format_i18n($count));
	}

	public static function wpematico_rss_feed_initiation() {
		global $wpematico_stopwith;
		$campaigns = WpeMatico::get_campaigns();
		
		$wpematico_stopwith = array();
		
		foreach ($campaigns as $campaign) {
			if ($campaign['campaign_type'] == 'rss_reader') {

				if (!empty($campaign['campaign_rss_feed_reader'])) {
					wp_enqueue_style('wpematico_rss_feed_reader_front', WPEMATICO_RSS_FEED_READER_URL . 'assets/css/reader.css', array('dashicons'), WPEMATICO_RSS_FEED_READER_VER);
					switch ($campaign['campaign_rss_feed_reader']) {
						case 'shortcode':
							add_shortcode('wpematico-' . $campaign['wpematico_shortcode_name'], array(__CLASS__, 'wpematico_rss_get_content'));
							break;
						
						case 'page_template':
							add_action('the_content', array(__CLASS__, 'wpematico_rss_get_content'), 999);
							break;

						case 'the_content':
							add_action('the_content', array(__CLASS__, 'wpematico_rss_get_content'), 999);
							break;

						default:
							break;
					}
				}
			}
		}
	}

	public static function wpematico_rss_get_content($content = ''){
		global $post, $wpematico_stopwith;

		$campaigns = WpeMatico::get_campaigns();

		foreach($campaigns as $campaign){
			if ($campaign['campaign_type'] != 'rss_reader') {
				continue;
			}

			$continue = false;
			if (!empty($campaign['campaign_post_select']) && $post->post_type == 'post') {
				$continue = ($campaign['campaign_post_select'] == $post->ID);
			} elseif (!empty($campaign['campaign_page_select']) && $post->post_type == 'page') {
				$continue = ($campaign['campaign_page_select'] == $post->ID);
			}

			if ($continue) {
				$content = self::get_rendered_items($campaign);
			} elseif (isset($campaign['campaign_rss_feed_reader']) && $campaign['campaign_rss_feed_reader'] == 'shortcode'
					&& has_shortcode($post->post_content, "wpematico-" . $campaign['wpematico_shortcode_name'])
					&& !in_array($campaign['wpematico_shortcode_name'], $wpematico_stopwith)) {
				$wpematico_stopwith[] = $campaign['wpematico_shortcode_name'];
				$content = self::get_rendered_items($campaign);
			}
		}

		return wp_kses_post($content);
	}

	/**
	 * Render (and cache) a reader campaign's stored items.
	 *
	 * Items are stored as structured data and rendered here at display time, so a
	 * template/layout change shows without re-fetching. The result is cached in a
	 * per-campaign transient flushed on fetch, campaign save and reset. Legacy string
	 * rows (baked HTML from < 2.0.0) are emitted as-is for backward compatibility.
	 */
	public static function get_rendered_items($campaign){
		$campaign_id = $campaign['ID'];
		if (!$campaign_id) {
			return '';
		}

		$cached = get_transient(self::cache_key($campaign_id));
		if ($cached !== false) {
			return $cached;
		}

		$items = get_post_meta($campaign_id, 'feed_items');
		$items = array_reverse($items);
		$items = array_slice($items, 0, $campaign['campaign_max_to_show']);

		$template = !empty($campaign['campaign_rss_html_content'])
			? $campaign['campaign_rss_html_content']
			: self::wpematico_rss_get_default_template();

		$layout = !empty($campaign['campaign_rss_layout']) ? $campaign['campaign_rss_layout'] : 'list';

		$html = '';
		foreach ($items as $item) {
			$html .= is_array($item) ? self::render_item($item, $template) : $item;
		}

		$html = '<div class="wpe_rss-feed wpe_rss-layout-' . esc_attr($layout) . '">' . $html . '</div>';

		set_transient(self::cache_key($campaign_id), $html, DAY_IN_SECONDS);

		return $html;
	}

	/**
	 * Replace the per-item tokens in the template with one stored item's data.
	 */
	public static function render_item($data, $template){
		$replacements = array(
			'~~~BeginItemsRecord~~~' => '',
			'~~~EndItemsRecord~~~'   => '',
			'~~~ItemPubShortDate~~~' => isset($data['date']) ? $data['date'] : '',
			'~~~ItemPubShortTime~~~' => isset($data['time']) ? $data['time'] : '',
			'~~~ItemDescription~~~'  => isset($data['content']) ? $data['content'] : '',
			'~~~ItemLink~~~'         => isset($data['link']) ? $data['link'] : '',
			'~~~ItemTitle~~~'        => isset($data['title']) ? $data['title'] : '',
			'~~~ItemSourceUrl~~~'    => isset($data['source_url']) ? $data['source_url'] : '',
			'~~~ItemImage~~~'        => isset($data['image_url']) ? $data['image_url'] : '',
		);

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}

	public static function cache_key($campaign_id){
		return 'wpe_rss_' . (int) $campaign_id;
	}

	public static function flush_cache($campaign_id){
		delete_transient(self::cache_key($campaign_id));
	}

	public static function flush_on_save($post_id){
		self::flush_cache($post_id);
	}

	public static function wpematico_rss_get_default_template(){

		return 
		"~~~BeginItemsRecord~~~  
		  <div class='wpe_rss-item'>
			<div class='wpe_rss-title'>
			  <h2><a href='~~~ItemLink~~~' target='_blank'>~~~ItemTitle~~~</a></h2>
			</div>
			<div class='wpe_rss-metadata'>
			  <div class='wpe_rss-metadata-item'><span class='dashicons dashicons-calendar'></span> <span>~~~ItemPubShortDate~~~ ~~~ItemPubShortTime~~~</span></div>
			</div>
			<div class='wpe_rss-description'>
			  ~~~ItemDescription~~~
			  <br /> 
			  <a href='~~~ItemSourceUrl~~~' class='wpe_rss-btn'>Go to source</a>
			</div>
		  </div>
		~~~EndItemsRecord~~~";
	}

	public static function get_layouts(){
		return array('list', 'grid', 'excerpt_thumbnail');
	}

	/**
	 * Preset per-item template for a layout. The layout only sets the container
	 * arrangement; a user can edit the template to "custom" without changing it.
	 */
	public static function get_layout_template($layout = 'list'){
		switch ($layout) {
			case 'grid':
				return "~~~BeginItemsRecord~~~\n  <div class=\"wpe_rss-item\">\n    <div class=\"wpe_rss-thumb\"><a href=\"~~~ItemLink~~~\" target=\"_blank\"><img src=\"~~~ItemImage~~~\" alt=\"~~~ItemTitle~~~\" /></a></div>\n    <div class=\"wpe_rss-title\"><h3><a href=\"~~~ItemLink~~~\" target=\"_blank\">~~~ItemTitle~~~</a></h3></div>\n    <div class=\"wpe_rss-metadata\"><span class=\"dashicons dashicons-calendar\"></span> <span>~~~ItemPubShortDate~~~</span></div>\n    <div class=\"wpe_rss-description\">~~~ItemDescription~~~</div>\n  </div>\n~~~EndItemsRecord~~~";

			case 'excerpt_thumbnail':
				return "~~~BeginItemsRecord~~~\n  <div class=\"wpe_rss-item\">\n    <div class=\"wpe_rss-thumb\"><a href=\"~~~ItemLink~~~\" target=\"_blank\"><img src=\"~~~ItemImage~~~\" alt=\"~~~ItemTitle~~~\" /></a></div>\n    <div class=\"wpe_rss-body\">\n      <div class=\"wpe_rss-title\"><h3><a href=\"~~~ItemLink~~~\" target=\"_blank\">~~~ItemTitle~~~</a></h3></div>\n      <div class=\"wpe_rss-metadata\"><span class=\"dashicons dashicons-calendar\"></span> <span>~~~ItemPubShortDate~~~</span></div>\n      <div class=\"wpe_rss-description\">~~~ItemDescription~~~</div>\n    </div>\n  </div>\n~~~EndItemsRecord~~~";

			case 'list':
			default:
				return self::wpematico_rss_get_default_template();
		}
	}

	/**
	 * First <img> URL found in a piece of HTML, or '' if none.
	 */
	public static function first_image_url($content){
		if (empty($content) || strpos($content, '<img') === false) {
			return '';
		}
		if (preg_match('/<img[^>]+src=(["\'])(.*?)\1/i', $content, $m)) {
			return $m[2];
		}
		return '';
	}

	public static function wpematico_add_custom_template($templates){
		$templates[WPEMATICO_RSS_FEED_READER_DIR . 'templates/wpematico-rss-template.php'] = esc_html__('Feed reader template', 'wpematico-rss-feed-reader');

		return $templates;
	}

	public static function wpematico_reset_campaign($status = '') {
		if (!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wpematico_reset_campaign' == $_REQUEST['action'] ) )) {
			wp_die( esc_html__('No campaign ID has been supplied!', 'wpematico'));
		}
		$nonce = '';
		if (isset($_REQUEST['nonce'])) {
			$nonce = sanitize_text_field($_REQUEST['nonce']);
		}
		if (!wp_verify_nonce($nonce, 'wpe-action-nonce')) {
			wp_die('Are you sure?');
		}
		// Get the original post
		$id = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']) );

		delete_post_meta($id, 'feed_items');
		self::flush_cache($id);
	}

}