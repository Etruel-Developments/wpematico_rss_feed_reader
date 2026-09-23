<?php
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

add_action('admin_init', 'wpematico_rss_feed_reader_admin_init');
function wpematico_rss_feed_reader_admin_init(){
	add_filter(	'plugin_row_meta',	'wpematico_rss_feed_reader_init_row_meta',10,2);
	add_filter(	'plugin_action_links_' . plugin_basename( WPEMATICO_RSS_FEED_READER_ROOT_FILE ), 'wpematico_rss_feed_reader_init_action_links');
}

/** * Deactivate RSS Feed Reader on Deactivate Plugin  */
register_deactivation_hook( plugin_basename( WPEMATICO_RSS_FEED_READER_ROOT_FILE ), 'wpematico_rss_feed_reader_deactivate' );
function wpematico_rss_feed_reader_deactivate() {
	if(class_exists('WPeMatico')) {
		$notice = esc_html__('RSS Feed Reader DEACTIVATED.',  'wpematico-rss-feed-reader');
		WPeMatico::add_wp_notice( array('text' => $notice , 'below-h2'=>false ) );
	}
}

/*
register_uninstall_hook( plugin_basename( __FILE__ ), 'rss_feed_reader_uninstall' );
function rss_feed_reader_uninstall() {
	
}
*/



/**
* Actions-Links del Plugin
*
* @param   array   $data  Original Links
* @return  array   $data  modified Links
*/
function wpematico_rss_feed_reader_init_action_links($data)	{
	if ( !current_user_can('manage_options') ) {
		return $data;
	}
	return array_merge(
		$data,
		array(
			'<a href="'.  admin_url('edit.php?post_type=wpematico&campaign_type=rss_reader').'" title="' . esc_html__('Go to your RSS Feed Reader campaigns', 'wpematico-rss-feed-reader') . '">' . esc_html__('Campaigns', 'wpematico-rss-feed-reader') . '</a>',
		)
	);
}

/**
* Meta-Links del Plugin
*
* @param   array   $data  Original Links
* @param   string  $page  plugin actual
* @return  array   $data  modified Links
*/

function wpematico_rss_feed_reader_init_row_meta($data, $page)	{
	if ( basename($page) != 'wpematico_rss_feed_reader.php' ) {
		return $data;
	}
	return array_merge(
		$data,
		array(
		'<a href="https://etruel.com/" target="_blank">' . esc_html('etruel Store') . '</a>',
		'<a href="https://etruel.com/my-account/support/" target="_blank">' . esc_html__('Support', 'wpematico-rss-feed-reader') . '</a>',
		'<a href="https://wordpress.org/support/plugin/wpematico-rss-feed-reader/reviews/?filter=5#new-post" target="_blank" title="Rate 5 stars on WordPress.org">' . esc_html__('Rate Plugin', 'wpematico-rss-feed-reader') . '</a>'
		)
	);
}	

