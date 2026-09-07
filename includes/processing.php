<?php
/** 
 *  @package WPeMatico wpematico_googlo_news
 *	functions to add filters and parsers on campaign running
**/
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class Wpematico_feed_reader_process {
	
	function __construct() {
		add_action('wpematico_allow_insertpost', array(__CLASS__, 'allow_insertpost'), 10, 3); //hook to add actions and filter on init fetching
		add_filter('wpematico_custom_simplepie', array(__CLASS__,'wpematico_rss_feed_reader_process'), 10, 4);
	}

	public static function wpematico_rss_feed_reader_process($simplepie, $class, $feed, $kf){
		
		if ($class->campaign['campaign_type'] == 'rss_reader') {

			$wpe_url_feed = apply_filters('wpematico_simplepie_url', $feed, $kf, $class->campaign);
			/**
			 * @since 1.0.0
			 * Added @fetch_feed_params to change parameters values before fetch the feed.
			 */
			$fetch_feed_params = array(
				'url' => $wpe_url_feed,
				'stupidly_fast' => $class->cfg['set_stupidly_fast'],
				'max' => $class->campaign['campaign_max'],
				'order_by_date' => $class->campaign['campaign_feed_order_date'],
				'force_feed' => false,
			);
			$fetch_feed_params = apply_filters('wpematico_fetch_feed_params', $fetch_feed_params, $kf, $class->campaign);
			$simplepie = WPeMatico::fetchFeed($fetch_feed_params);
		}
		
		return $simplepie;
	}

	public static function allow_insertpost($allow, $fetch, $args){
		global $post;

		$campaign = $fetch->campaign;
		$current_item = $fetch->current_item;

		if ($campaign['campaign_type'] == 'rss_reader') {
			$campaign_id = $campaign['ID'];
			if (self::wpematico_set_rss_data($campaign_id, $current_item)) {
				$allow = false;
				wpematico_rss_feed_functions::trim_items($campaign_id, $campaign['campaign_max_to_show']);
				wpematico_rss_feed_functions::flush_cache($campaign_id);
				return $allow;
			}
		}

		return $allow;
	}

	/**
	 * Store one feed item as structured data. Rendering happens at display time
	 * (wpematico_rss_feed_functions::get_rendered_items) so a template/layout change
	 * is reflected without re-fetching.
	 */
	public static function wpematico_set_rss_data($campaign_id, $item){
		if (!$campaign_id) {
			return false;
		}

		$content = isset($item['content']) ? $item['content'] : '';
		$image   = wpematico_rss_feed_functions::first_image_url($content);
		if (empty($image) && !empty($item['featured_image']) && filter_var($item['featured_image'], FILTER_VALIDATE_URL)) {
			$image = $item['featured_image'];
		}

		// $item['date'] is the feed item's UTC timestamp (campaign_fetch.php). wp_date() renders it in the site timezone.
		$timestamp = !empty($item['date']) ? (int) $item['date'] : 0;

		$record = array(
			'title'      => isset($item['title']) ? $item['title'] : '',
			'link'       => isset($item['permalink']) ? $item['permalink'] : '',
			'content'    => $content,
			'date'       => $timestamp ? wp_date('d-m-Y', $timestamp) : wp_date('d-m-Y'),
			'time'       => $timestamp ? wp_date('H:i', $timestamp) : wp_date('H:i'),
			'source_url' => isset($item['meta']['wpe_sourcepermalink']) ? $item['meta']['wpe_sourcepermalink'] : '',
			'image_url'  => $image,
		);

		return add_post_meta($campaign_id, 'feed_items', $record);
	}
}

$wpematico_feed_reader_process = new Wpematico_feed_reader_process();