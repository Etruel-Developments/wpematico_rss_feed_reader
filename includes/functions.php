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
		// Late on save_post: save_post_wpematico fires before core stores campaign_data.
		add_action('save_post', array(__CLASS__, 'flush_on_save'), 20);
		add_filter('wpematico_fetch_posts_summary', array(__CLASS__, 'reader_fetch_summary'), 10, 3);
		add_filter('wpematico_campaign_count_column', array(__CLASS__, 'reader_count_column'), 10, 3);
		add_filter('wpematico_campaign_count_row_meta_keys', array(__CLASS__, 'reader_count_sort_key'));
		// The media pipeline runs before wpematico_allow_insertpost, so a reader
		// campaign has to opt out of it here.
		add_filter('wpematico_images_options', array(__CLASS__, 'no_media_options'), 99, 3);
		add_filter('wpematico_audios_options', array(__CLASS__, 'no_media_options'), 99, 3);
		add_filter('wpematico_videos_options', array(__CLASS__, 'no_media_options'), 99, 3);
	}

	/**
	 * Every image/audio/video switch off for a reader campaign. Rebuilt from the keys
	 * core supplied, so an option added later is covered too.
	 */
	public static function no_media_options($options, $settings = array(), $campaign = array()){
		if (empty($campaign['campaign_type']) || $campaign['campaign_type'] !== 'rss_reader') {
			return $options;
		}
		return array_fill_keys(array_keys((array) $options), false);
	}

	/**
	 * The reader campaigns of this site, resolved once per request: get_campaigns()
	 * runs the wpematico_check_campaigndata chain per campaign and the callers below
	 * run on every the_content pass.
	 */
	public static function reader_campaigns(){
		static $campaigns = null;
		if ($campaigns === null) {
			$campaigns = array();
			foreach (WPeMatico::get_campaigns() as $campaign) {
				if (!empty($campaign['campaign_type']) && $campaign['campaign_type'] === 'rss_reader') {
					$campaigns[] = $campaign;
				}
			}
		}
		return $campaigns;
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

	/**
	 * Same reason, on the campaigns list: the "Posts" column counts inserted posts,
	 * so a reader campaign sat at 0 however well it was working. Report the items
	 * stored instead. The filter only exists in WPeMatico 2.9+, so on an older core
	 * this callback simply never runs.
	 *
	 * Free to count here: the list table has already primed the post meta cache for
	 * the campaigns it lists.
	 */
	public static function reader_count_column($cell, $campaign, $post_id){
		if (empty($campaign['campaign_type']) || $campaign['campaign_type'] !== 'rss_reader') {
			return $cell;
		}
		// Unformatted on purpose: core prints the other campaigns' figure raw and
		// they share one column.
		$cell['count'] = count(get_post_meta($post_id, 'feed_items'));
		$cell['title'] = __('Feed items stored by this campaign.', 'wpematico-rss-feed-reader');
		return $cell;
	}

	/**
	 * So sorting that column matches what it shows: one `feed_items` row per stored
	 * item is the figure, and core counts the rows in SQL.
	 */
	public static function reader_count_sort_key($keys){
		$keys[] = 'feed_items';
		return $keys;
	}

	public static function wpematico_rss_feed_initiation() {
		$renders = false;

		foreach (self::reader_campaigns() as $campaign) {
			if (empty($campaign['campaign_rss_feed_reader'])) {
				continue;
			}
			$renders = true;

			if ($campaign['campaign_rss_feed_reader'] === 'shortcode') {
				add_shortcode('wpematico-' . $campaign['wpematico_shortcode_name'], array(__CLASS__, 'render_shortcode'));
			}
		}

		if (!$renders) {
			return;
		}

		wp_enqueue_style('wpematico_rss_feed_reader_front', WPEMATICO_RSS_FEED_READER_URL . 'assets/css/reader.css', array('dashicons'), WPEMATICO_RSS_FEED_READER_VER);
		add_filter('the_content', array(__CLASS__, 'append_to_content'), 999);
	}

	/**
	 * Shortcode callback for every reader campaign, resolved from the tag that fired.
	 */
	public static function render_shortcode($atts = array(), $content = '', $tag = ''){
		$name = substr((string) $tag, strlen('wpematico-'));

		foreach (self::reader_campaigns() as $campaign) {
			if (!empty($campaign['wpematico_shortcode_name']) && $campaign['wpematico_shortcode_name'] === $name) {
				return wp_kses_post(self::get_rendered_items($campaign));
			}
		}

		return '';
	}

	/**
	 * Add a campaign's feed to the post or page it targets.
	 *
	 * Registered site-wide, so two rules: append to the page's own content rather than
	 * replace it, and return $content untouched — unfiltered — for every other post.
	 */
	public static function append_to_content($content){
		global $post;

		if (empty($post->ID)) {
			return $content;
		}

		foreach (self::reader_campaigns() as $campaign) {
			if (empty($campaign['campaign_rss_feed_reader']) || $campaign['campaign_rss_feed_reader'] === 'shortcode') {
				continue;
			}

			$target = 0;
			if (!empty($campaign['campaign_post_select']) && $post->post_type === 'post') {
				$target = (int) $campaign['campaign_post_select'];
			} elseif (!empty($campaign['campaign_page_select']) && $post->post_type === 'page') {
				$target = (int) $campaign['campaign_page_select'];
			}

			if ($target !== (int) $post->ID) {
				continue;
			}

			$content .= wp_kses_post(self::get_rendered_items($campaign));
		}

		return $content;
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

		$layout = !empty($campaign['campaign_rss_layout']) ? $campaign['campaign_rss_layout'] : 'list';

		// With no custom template the item HTML follows the layout, so a campaign
		// that only has a layout set (e.g. imported from another plugin) still
		// renders with the right arrangement instead of the plain list default.
		$template = !empty($campaign['campaign_rss_html_content'])
			? $campaign['campaign_rss_html_content']
			: self::get_layout_template($layout);

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
		// A token can land in an attribute: the presets put the title in alt="" and the
		// URLs in href/src. esc_attr() does not re-encode existing entities and its
		// &quot; reads as a quote in a text node, so it is safe in both places.
		$replacements = array(
			'~~~BeginItemsRecord~~~' => '',
			'~~~EndItemsRecord~~~'   => '',
			'~~~ItemPubShortDate~~~' => isset($data['date']) ? esc_attr($data['date']) : '',
			'~~~ItemPubShortTime~~~' => isset($data['time']) ? esc_attr($data['time']) : '',
			// The item's own HTML, meant to render as markup.
			'~~~ItemDescription~~~'  => isset($data['content']) ? $data['content'] : '',
			'~~~ItemLink~~~'         => isset($data['link']) ? esc_url($data['link']) : '',
			'~~~ItemTitle~~~'        => isset($data['title']) ? esc_attr($data['title']) : '',
			'~~~ItemSourceUrl~~~'    => isset($data['source_url']) ? esc_url($data['source_url']) : '',
			'~~~ItemImage~~~'        => isset($data['image_url']) ? esc_url($data['image_url']) : '',
		);

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}

	// Versioned: an upgrade that changes the rendering must not hit a day-old transient.
	public static function cache_key($campaign_id){
		return 'wpe_rss_' . (int) $campaign_id . '_' . substr(md5(WPEMATICO_RSS_FEED_READER_VER), 0, 8);
	}

	public static function flush_cache($campaign_id){
		delete_transient(self::cache_key($campaign_id));
	}

	public static function flush_on_save($post_id){
		if (get_post_type($post_id) !== 'wpematico') {
			return;
		}

		self::flush_cache($post_id);

		$campaign = WPeMatico::get_campaign($post_id);
		if (!empty($campaign['campaign_type']) && $campaign['campaign_type'] === 'rss_reader') {
			// The fetch only trims when it stores an item, so a lowered "Max items to
			// show" is applied here or a campaign with no new items never shrinks.
			self::trim_items($post_id, $campaign['campaign_max_to_show']);
		}
	}

	/**
	 * Keep at most $max_to_show stored items, dropping the oldest first.
	 *
	 * Deletes by meta id: delete_post_meta() given a value removes every row holding
	 * it, and two feed items can be identical.
	 */
	public static function trim_items($campaign_id, $max_to_show){
		global $wpdb;

		$campaign_id = (int) $campaign_id;
		$max_to_show = (int) $max_to_show;
		if ($campaign_id < 1 || $max_to_show < 1) {
			return 0;
		}

		$meta_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'feed_items' ORDER BY meta_id ASC",
			$campaign_id
		));

		$excess = count($meta_ids) - $max_to_show;
		if ($excess < 1) {
			return 0;
		}

		foreach (array_slice($meta_ids, 0, $excess) as $meta_id) {
			delete_metadata_by_mid('post', $meta_id);
		}
		self::flush_cache($campaign_id);

		return $excess;
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
			wp_die( esc_html__('No campaign ID has been supplied!', 'wpematico-rss-feed-reader'));
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