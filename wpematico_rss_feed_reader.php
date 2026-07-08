<?php
/**
 * Plugin Name:      WPeMatico RSS Feed Reader
 * Plugin URI:       https://etruel.com/downloads/wpematico-rss-feed-reader/
 * Requires Plugins: wpematico
 * Description:      RSS Feed Reader print pre-formatted feeds contents directly on your pages, posts, widgets, etc. 
 * Version:          2.0.0
 * Author:			 Etruel Developments LLC
 * Author URI:		 https://etruel.com/
 * Text Domain:      wpematico-rss-feed-reader
 * License:          GPL v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package          \RSS Feed Reader
 * @author           Etruel Developments LLC
 * @copyright       Copyright (c) 2024
 * 
 * @Release	C4
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
	exit;

if (!class_exists('WPeMatico_RSS_Feed_Reader')) {

	// Plugin version
	if (!defined('WPEMATICO_RSS_FEED_READER_VER')) {
		define('WPEMATICO_RSS_FEED_READER_VER', '2.0.0');
	}

	/**
	 * Main RSS Feed Reader class
	 *
	 * @since       1.0.0
	 */
	class WpeMatico_RSS_Feed_Reader {

		/**
		 * @var         WPeMatico_RSS_Feed_Reader $instance The one true RSS Feed Reader
		 * @since       1.0.0
		 */
		private static $instance;

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      object|bool self::$instance The one true RSS Feed Reader
		 */
		public static function instance() {
			if (!wpematico_rss_requirements())
				return false;

			if (!self::$instance) {
				self::$instance = new self();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public static function setup_constants() {
			// Plugin root file
			if (!defined('WPEMATICO_RSS_FEED_READER_ROOT_FILE')) {
				define('WPEMATICO_RSS_FEED_READER_ROOT_FILE', __FILE__);
			}
			// Plugin path
			if (!defined('WPEMATICO_RSS_FEED_READER_DIR')) {
				define('WPEMATICO_RSS_FEED_READER_DIR', plugin_dir_path(__FILE__));
			}
			// Plugin URL
			if (!defined('WPEMATICO_RSS_FEED_READER_URL')) {
				define('WPEMATICO_RSS_FEED_READER_URL', plugin_dir_url(__FILE__));
			}
			if (!defined('WPEMATICO_RSS_FEED_READER_STORE_URL')) {
				define('WPEMATICO_RSS_FEED_READER_STORE_URL', 'https://etruel.com');
			}
			if (!defined('WPEMATICO_RSS_FEED_READER_ITEM_NAME')) {
				define('WPEMATICO_RSS_FEED_READER_ITEM_NAME', 'WPeMatico RSS Feed Reader');
			}
		}

		/**
		 * Include necessary files
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public static function includes() {
			// Include scripts
			require_once WPEMATICO_RSS_FEED_READER_DIR . 'includes/plugin_functions.php';
			require_once WPEMATICO_RSS_FEED_READER_DIR . 'includes/functions.php';
			require_once WPEMATICO_RSS_FEED_READER_DIR . 'includes/campaign_edit.php';
			require_once WPEMATICO_RSS_FEED_READER_DIR . 'includes/campaign_help.php';
			require_once WPEMATICO_RSS_FEED_READER_DIR . 'includes/processing.php';
		}

		/**
		 * Run action and filter hooks
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 *
		 */
		public static function hooks() {
			add_action('init', array('wpematico_rss_feed_functions', 'init'));
		}

		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public static function load_textdomain() {
			$lang_dir = WPEMATICO_RSS_FEED_READER_DIR . '/languages/';
			load_plugin_textdomain('wpematico-rss-feed-reader', false, $lang_dir);
		}
	}

	// End if class_exists check

	/**
	 * The main function responsible for returning the one true RSS Feed Reader
	 * instance to functions everywhere
	 *
	 * @since       1.0.0
	 * @return      \WPeMatico_RSS_Feed_Reader The one true RSS Feed Reader
	 *
	 * @todo        Inclusion of the activation code below isn't mandatory, but
	 *              can prevent any number of errors, including fatal errors, in
	 *              situations where your extension is activated but EDD is not
	 *              present.
	 */
	function WPematico_rss_feed_reader_load() {
		if (!class_exists('WPeMatico')) {
			if (!class_exists('WPeMatico_Extension_Activation')) {
				require_once 'includes/class.extension-activation.php';
			}

			$activation = new WPeMatico_Extension_Activation(plugin_dir_path(__FILE__), basename(__FILE__));
			$activation = $activation->run();
		} else {
			return WPeMatico_RSS_Feed_Reader::instance();
		}
	}

	add_action('plugins_loaded', 'WPematico_rss_feed_reader_load', 999);

	/**
	 * The activation hook is called outside of the singleton because WordPress doesn't
	 * register the call from within the class, since we are preferring the plugins_loaded
	 * hook for compatibility, we also can't reference a function inside the plugin class
	 * for the activation function. If you need an activation function, put it here.
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	function wpematico_rss_feed_reader_activation() {
		/* Activation functions here */
		if (class_exists('WPeMatico')) {
			$notice = esc_html__('WPeMatico RSS Feed Reader Activated.', 'wpematico-rss-feed-reader');
			WPeMatico::add_wp_notice(array('text' => $notice, 'below-h2' => false));
		}
	}

	register_activation_hook(__FILE__, 'wpematico_rss_feed_reader_activation');

	function wpematico_rss_requirements() {
		$message			  = $wperss_admin_message = '';
		$checks				  = true;
		// Core is old. 
		if (class_exists('WPeMatico') && version_compare(WPEMATICO_VERSION, '2.7', '<')) {
			$message .= sprintf(esc_html__('The current version WPeMatico RSS Feed Reader %s needs WPeMatico %s', 'wpematico-rss-feed-reader'), WPEMATICO_RSS_FEED_READER_VER, '2.7') . '<br />';
			$message .= sprintf(
					esc_html__('Please %s to the last version ASAP to avoid errors.', 'wpematico-rss-feed-reader'),
					' <a href="' . esc_url(admin_url('plugins.php')) . '#wpematico">update "WPeMatico"</a>'
			);
			$checks	 = false;
		}

		if (!empty($message))
			$wperss_admin_message = '<div id="message" class="error fade"><strong>WPeMatico RSS Feed Reader:</strong><br />' . $message . '</div>';

		if (!empty($wperss_admin_message)) {
			//send response to admin notice
			add_action('admin_notices', function () use ($wperss_admin_message) {
				echo wp_kses_post($wperss_admin_message);
			});
		}

		return $checks;
	}

}