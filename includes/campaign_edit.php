<?php
/**
 *  @package WPeMatico RSS Feed Reader
 * 	functions to add metaboxes in campaign editing
 * */
if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

add_action('init', array('Wpematico_feed_reader_edit', 'init'));

class Wpematico_feed_reader_edit {

	public static function init() {
		new self();
	}

	function __construct() {
		add_action('admin_print_scripts-post.php', array(__CLASS__, 'admin_scripts'));
		add_action('admin_print_scripts-post-new.php', array(__CLASS__, 'admin_scripts'));
		add_action('wpematico_print_additional_options', array(__CLASS__, 'wpematico_rss_print_addicional'));
		add_action('admin_print_styles-post.php', array(__CLASS__, 'admin_styles'));
		add_action('admin_print_styles-post-new.php', array(__CLASS__, 'admin_styles'));
	}

	public static function hooks() {
		// Metabox campaigns 
		add_filter('wpematico_campaign_type_options', array(__CLASS__, 'campaign_type_options'), 16);
		// Saving campaigns (just check fields, saved in Free version
		add_filter('pro_check_campaigndata', array(__CLASS__, 'wpematico_rss_feed_reader_check_campaigndata'), 15, 2);
		add_action('wpematico_create_metaboxes_before', array(__CLASS__, 'wpematico_rss_feed_reader_metaboxes'), 15, 2);

		add_filter('wpematico_campaign_type_validate_feed_before_save', array(__CLASS__, 'campaign_validate_before_save'));
	}
	
	
	public static function campaign_type_options($options)
	{
		$options[] = array(
			'value' => 'rss_reader',
			'text' => esc_html__('RSS Feed Reader', 'wpematico-rss-feed-reader'),
			'show' => array('feeds-box', 'wpematico-rss-page-feed-url-save'),
			'hide' => array(
				//WPeMatico boxes
				'audios-box','videos-box','cron-box','template-box',
			));

		return $options;
	}

	public static function campaign_validate_before_save($campaign_types) {
		return array_merge($campaign_types, array('rss_reader'));
	}

	public static function wpematico_rss_feed_reader_metaboxes() {  // chequea y agrega campos a campaign y graba en free
		global $pagenow, $post;
		if (!(($pagenow == 'post.php' || $pagenow == 'post-new.php') && $post->post_type == 'wpematico'))
			return false;

		add_meta_box('wpematico-rss-page-feed-url-save', '<span class="dashicons dashicons-list-view"></span> '.esc_html__('RSS Contents', 'wpematico-rss-feed-reader'), array(__CLASS__, 'wpematico_rss_feed_reader_box'), 'wpematico', 'normal', 'default');
	}

	public static function admin_styles() { // load javascript 
		global $post;

		if ($post->post_type != 'wpematico')
			return $post->ID;

		wp_enqueue_style('wpematico_rss_feed_reader_campaign_edit', WPEMATICO_RSS_FEED_READER_URL . 'assets/css/styles.css', array(), WPEMATICO_RSS_FEED_READER_VER, 'all');
	}

	public static function admin_scripts() { // load javascript 
		global $post;

		if ($post->post_type != 'wpematico')
			return $post->ID;

		wp_enqueue_script('wpematico_rss_feed_reader_campaign_edit', WPEMATICO_RSS_FEED_READER_URL . 'assets/js/campaign_edit.js', array('jquery'), WPEMATICO_RSS_FEED_READER_VER, true);

		wp_localize_script('wpematico_rss_feed_reader_campaign_edit', 'backend_object_rss', array(
			'error_message'     => esc_html__('Max to fetch items value must be equal to the max to show items.', 'wpematico-rss-feed-reader'),
			'presets'           => array(
				'list'              => wpematico_rss_feed_functions::get_layout_template('list'),
				'grid'              => wpematico_rss_feed_functions::get_layout_template('grid'),
				'excerpt_thumbnail' => wpematico_rss_feed_functions::get_layout_template('excerpt_thumbnail'),
			),
			'confirm_overwrite' => esc_html__('This replaces your custom HTML with the selected layout template. Continue?', 'wpematico-rss-feed-reader'),
			'show_html'         => esc_html__('Show advanced Layout editor.', 'wpematico-rss-feed-reader'),
			'hide_html'         => esc_html__('Hide advanced Layout editor.', 'wpematico-rss-feed-reader'),
		));
	}

	public static function wpematico_rss_feed_reader_box() {
		global $post, $campaign_data, $helptip;

		$campaign_max_to_show = empty($campaign_data['campaign_max_to_show']) ? 0 : $campaign_data['campaign_max_to_show'];

		$campaign_rss_feed_reader  = empty($campaign_data['campaign_rss_feed_reader']) ? '' : $campaign_data['campaign_rss_feed_reader'];
		$campaign_rss_html_content = (!empty($campaign_data['campaign_rss_html_content'])) ? $campaign_data['campaign_rss_html_content'] : wpematico_rss_feed_functions::wpematico_rss_get_default_template();
		$campaign_rss_layout = empty($campaign_data['campaign_rss_layout']) ? 'list' : $campaign_data['campaign_rss_layout'];
		?>
		<div class="wpe_rss-max-items">
			<input name="campaign_max_to_show" type="number" min="0" size="3" value="<?php echo esc_attr($campaign_max_to_show); ?>" class="small-text" id="campaign_max_to_show" />
			<label for="campaign_max_to_show"><?php esc_html_e('Max items to show in each read feed.', 'wpematico-rss-feed-reader'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo esc_attr($helptip['max_to_show']); ?>"></span><br />
			<p class="description"><?php esc_html_e('Set a limit on how many feed items will be displayed, make sure this value is not less than the value of "Max items to create on each fetch".', 'wpematico-rss-feed-reader'); ?></p>
		</div>
		<div class="wpe_rss-display">
			<p><b><?php esc_html_e('How to display the feed content:',  'rss_feed_reader') ?></b></p>
			<label><input type="radio" name="campaign_rss_feed_reader" <?php echo checked('the_content', $campaign_rss_feed_reader, false); ?> value="the_content" /> <span class="wpe_rss_code">get_the_content()</span> <?php esc_html_e('Wordpress filter', 'wpematico-rss-feed-reader'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo esc_attr($helptip['get_the_content']); ?>"></span><br />
			<p class="description"><?php esc_html_e('Use the WordPress function "get_the_content()" to display the content of the feed in the selected post type.', 'wpematico-rss-feed-reader'); ?></p>
			<label><input type="radio" name="campaign_rss_feed_reader" <?php echo checked('page_template', $campaign_rss_feed_reader, false); ?> value="page_template" />
			<?php esc_html_e('RSS Page Template.', 'wpematico-rss-feed-reader'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo esc_attr($helptip['rss_page_template']); ?>"></span><br />
			<p class="description"><?php esc_html_e('Works only with "pages" you must choose the page and page template where the feed content will be displayed.', 'wpematico-rss-feed-reader'); ?></p>
			<label><input type="radio" name="campaign_rss_feed_reader" <?php echo checked('shortcode', $campaign_rss_feed_reader, false); ?> value="shortcode" /> <span class="wpe_rss_code"><?php echo "[wpematico-" . esc_html($post->post_name) . "]" ?></span> <?php esc_html_e('Shortcode', 'wpematico-rss-feed-reader'); ?></label><span class="dashicons dashicons-warning help_tip" title="<?php echo esc_attr($helptip['shortcode']); ?>"></span><br />
			<input type="hidden" name="wpematico_shortcode_name" value="<?php echo esc_attr($post->post_name); ?>">
			<p class="description"><?php esc_html_e('Generates a shortcode that can be used in any place of the website to display the feed content.', 'wpematico-rss-feed-reader'); ?></p>
		</div>
		<div class="wpe_rss-layout">
			<p class="wpe_rss-layout-heading"><b><?php esc_html_e('Layout', 'wpematico-rss-feed-reader'); ?></b></p>
			<p class="description"><?php esc_html_e('Pick how each feed item is arranged. You can fine-tune the item HTML below without changing the arrangement.', 'wpematico-rss-feed-reader'); ?></p>
			<div class="wpe_rss-layout-cards">
				<?php
				$layout_names = array(
					'list'              => esc_html__('List', 'wpematico-rss-feed-reader'),
					'grid'              => esc_html__('Grid', 'wpematico-rss-feed-reader'),
					'excerpt_thumbnail' => esc_html__('Excerpt & Thumbnail', 'wpematico-rss-feed-reader'),
				);
				foreach ($layout_names as $layout_key => $layout_label) :
					$is_selected = ($campaign_rss_layout === $layout_key);
					?>
					<label class="wpe_rss-layout-card<?php echo $is_selected ? ' is-selected' : ''; ?>" data-layout="<?php echo esc_attr($layout_key); ?>">
						<input type="radio" class="wpe_rss-layout-radio" name="campaign_rss_layout" value="<?php echo esc_attr($layout_key); ?>" <?php checked($is_selected); ?> />
						<?php echo self::layout_skeleton($layout_key); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup ?>
						<span class="wpe_rss-layout-name"><?php echo $layout_label; ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<p class="wpe_rss-layout-custom-note" style="display:none;"><span class="dashicons dashicons-edit"></span> <?php esc_html_e('Custom template in use. Pick a layout above to reset it.', 'wpematico-rss-feed-reader'); ?></p>
			<p class="wpe_rss-image-note"><span class="dashicons dashicons-format-image"></span> <?php echo wp_kses( __('<strong>Grid and Excerpt &amp; Thumbnail</strong> show each item image from the source feed, loaded remotely (not saved to your Media Library).', 'wpematico-rss-feed-reader'), array('strong' => array()) ); ?></p>
		</div>
		<p class="wpe_rss-template-toggle">
			<button type="button" class="button wpe_rss-advanced-toggle" aria-expanded="false" aria-controls="wpe_rss-template-panel">
				<span class="dashicons dashicons-arrow-right-alt2"></span> <span class="wpe_rss-advanced-label"><?php esc_html_e('Show advanced Layout editor.', 'wpematico-rss-feed-reader'); ?></span>
			</button>
		</p>
		<div class="wpe_rss-template" id="wpe_rss-template-panel" style="display:none;">
			<p><label for="campaign_rss_html_content"><b><?php esc_html_e('Item template', 'wpematico-rss-feed-reader') ?></b></label><span class="dashicons dashicons-warning help_tip" title="<?php echo esc_attr($helptip['rss_page_template_html']); ?>"></span></p>
			<p class="description"><?php esc_html_e('Edit the HTML used for each feed item. Editing keeps the selected layout arrangement.', 'wpematico-rss-feed-reader'); ?></p>
			<textarea id="campaign_rss_html_content" name="campaign_rss_html_content" rows="10" cols="100"><?php echo esc_textarea($campaign_rss_html_content); ?></textarea>
			<div class="wpe_rss-tags-help">
				<p class="description"><b><?php esc_html_e('Available tags:', 'wpematico-rss-feed-reader'); ?></b></p>
				<ul class="wpe_rss-tags-list">
					<li><code>~~~BeginItemsRecord~~~</code> / <code>~~~EndItemsRecord~~~</code> — <?php esc_html_e('wrap the block that repeats for each feed item.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemTitle~~~</code> — <?php esc_html_e('item title.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemLink~~~</code> — <?php esc_html_e('URL to the original item.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemDescription~~~</code> — <?php esc_html_e('item content or description.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemImage~~~</code> — <?php esc_html_e('item image URL from the source feed (remote, not saved to Media).', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemPubShortDate~~~</code> — <?php esc_html_e('item publication date.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemPubShortTime~~~</code> — <?php esc_html_e('item publication time.', 'wpematico-rss-feed-reader'); ?></li>
					<li><code>~~~ItemSourceUrl~~~</code> — <?php esc_html_e('source site URL.', 'wpematico-rss-feed-reader'); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * CSS-only skeleton mockup for a layout card (no images).
	 */
	private static function layout_skeleton($layout) {
		switch ($layout) {
			case 'grid':
				$cells = str_repeat('<span class="wpe_rss-skel-cell"><i class="b"></i><i class="l"></i></span>', 6);
				return '<span class="wpe_rss-skel wpe_rss-skel--grid" aria-hidden="true">' . $cells . '</span>';

			case 'excerpt_thumbnail':
				$rows = str_repeat('<span class="wpe_rss-skel-erow"><i class="t"></i><span class="ll"><i class="l"></i><i class="l"></i><i class="l s"></i></span></span>', 2);
				return '<span class="wpe_rss-skel wpe_rss-skel--excerpt" aria-hidden="true">' . $rows . '</span>';

			case 'list':
			default:
				$rows = str_repeat('<span class="wpe_rss-skel-row"><i class="d"></i><i class="l"></i></span>', 4);
				return '<span class="wpe_rss-skel wpe_rss-skel--list" aria-hidden="true">' . $rows . '</span>';
		}
	}

	public static function wpematico_rss_print_addicional($campaign_data) {
		// Dropdown for available posts
		$custom_posts = get_posts(array(
			'post_type'		 => 'post',
			'posts_per_page' => -1,
			'orderby'		 => 'title',
			'order'			 => 'ASC',
		));

		if ($custom_posts) {
			echo '<div class="custom-dropdown wpe_rss_dropdown hidden" id="custom-dropdown-posts">';
			echo '<select name="campaign_post_select">';
			echo '<option value="">Select a Post</option>';
			foreach ($custom_posts as $custom_post) {
				$selected = (!empty($campaign_data['campaign_post_select']) && $campaign_data['campaign_post_select'] == $custom_post->ID) ? 'selected' : '';
				echo '<option value="' . esc_attr($custom_post->ID) . '" ' . $selected . '>' . esc_html($custom_post->post_title) . '</option>';
			}
			echo '</select>';
			echo '</div>';
		}

		$args = array(
			'name'			   => 'campaign_page_select',
			'show_option_none' => 'Select a Page',
			'sort_column'	   => 'menu_order',
			'echo'			   => 1,
			'selected'		   => !empty($campaign_data['campaign_page_select']) ? $campaign_data['campaign_page_select'] : '',
		);

		echo '<div class="custom-dropdown wpe_rss_dropdown hidden" id="custom-dropdown-pages">';
		wp_dropdown_pages($args);
		echo '</div>';

		$page_templates = get_page_templates();

		$page_id					= !empty($campaign_data['campaign_page_select']) ? $campaign_data['campaign_page_select'] : '';
		$campaign_rss_page_template = get_post_meta($page_id, '_wp_page_template', true);

		if (empty($campaign_rss_page_template))
			$campaign_rss_page_template = WPEMATICO_RSS_FEED_READER_DIR . 'templates/wpematico-rss-template.php';

		echo '<div id="rss_page_template" class="wpe_rss_page_template hidden">
			<select name="campaign_rss_page_template" >';
		foreach ($page_templates as $template_name => $template_filename) :

			echo '<option value="' . esc_attr($template_filename) . '"' . selected($campaign_rss_page_template, $template_filename) . '>' . esc_html($template_name) . '</option>';
		endforeach;
		echo '</select>
		</div>';
	}

	public static function wpematico_rss_feed_reader_check_campaigndata($campaign_data = array(), $post_data = array()) {
		global $post;
		// chequea y agrega campos a campaign y graba en free
		$default_template = wpematico_rss_feed_functions::wpematico_rss_get_default_template();

		$campaign_data['campaign_max_to_show'] = (!isset($post_data['campaign_max_to_show']) || empty($post_data['campaign_max_to_show'])) ? 5 : (($post_data['campaign_max_to_show'] != 0) ? $post_data['campaign_max_to_show'] : 5);

		$campaign_data['campaign_rss_feed_reader'] = (!isset($post_data['campaign_rss_feed_reader']) || empty($post_data['campaign_rss_feed_reader'])) ? '' : (($post_data['campaign_rss_feed_reader'] != '') ? $post_data['campaign_rss_feed_reader'] : '');

		$campaign_data['wpematico_shortcode_name'] = (!isset($post_data['wpematico_shortcode_name']) || empty($post_data['wpematico_shortcode_name'])) ? sanitize_title($campaign_data['campaign_title']) : (($post_data['wpematico_shortcode_name'] != '') ? $post_data['wpematico_shortcode_name'] : sanitize_title($campaign_data['campaign_title']));

		if (empty($campaign_data['wpematico_shortcode_name'])) {
			if (isset($post->post_name))
				$campaign_data['wpematico_shortcode_name'] = "$post->post_name";
		}

		$campaign_data['campaign_rss_html_content'] = (!isset($post_data['campaign_rss_html_content']) || empty($post_data['campaign_rss_html_content'])) ? $default_template : (($post_data['campaign_rss_html_content'] != '') ? $post_data['campaign_rss_html_content'] : $default_template);

		$allowed_layouts = wpematico_rss_feed_functions::get_layouts();
		$layout = isset($post_data['campaign_rss_layout']) ? sanitize_key($post_data['campaign_rss_layout']) : 'list';
		$campaign_data['campaign_rss_layout'] = in_array($layout, $allowed_layouts, true) ? $layout : 'list';

		$campaign_data['campaign_rss_page_template'] = (!isset($post_data['campaign_rss_page_template']) || empty($post_data['campaign_rss_page_template'])) ? '' : (($post_data['campaign_rss_page_template'] != '') ? $post_data['campaign_rss_page_template'] : '');

		if ($campaign_data['campaign_customposttype'] == 'post') {
			if (!empty($post_data['campaign_post_select'])) {
				// If 'campaign_post_select' has a value, set 'campaign_page_select' to empty
				$campaign_data['campaign_post_select'] = $post_data['campaign_post_select'];
				$campaign_data['campaign_page_select'] = '';
			}
		} else {
			if ($campaign_data['campaign_customposttype'] == 'page') {
				if (!empty($post_data['campaign_page_select'])) {
					// If 'campaign_page_select' has a value, set 'campaign_post_select' to empty
					$campaign_data['campaign_page_select'] = $post_data['campaign_page_select'];
					$campaign_data['campaign_post_select'] = '';
				}
			}
		}

		if (!empty($post_data['campaign_page_select']) && $post_data['campaign_rss_feed_reader'] == 'page_template' && !empty($post_data['campaign_rss_page_template'])) {
			update_post_meta($post_data['campaign_page_select'], '_wp_page_template', addslashes($post_data['campaign_rss_page_template']));
		}

		return $campaign_data;
	}
}

Wpematico_feed_reader_edit::hooks();
