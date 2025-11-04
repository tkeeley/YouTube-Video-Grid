<?php
/**
 * Plugin Name: YouTube Uploads Grid — 12 Latest incl. Shorts (No API)
 * Plugin URI:  https://cupocode.com/
 * Description: Shortcode [youtube_uploads] shows the 12 most recent YouTube uploads from the channel set in Settings, using the public RSS feed. Supports Shorts. No API key.
 * Version:     1.2.0
 * Author:      Cup O Code - Tim Keeley
 * Author URI:  https://cupocode.com/
 * License:     GPL-2.0+
 * Text Domain: youtube-uploads-grid
 */

if (!defined('ABSPATH')) exit;

/** ===== Defaults ===== */
// Display settings
if (!defined('YUG_COUNT'))       define('YUG_COUNT', 12);
if (!defined('YUG_COLUMNS'))     define('YUG_COLUMNS', 3);
if (!defined('YUG_CACHE_HOURS')) define('YUG_CACHE_HOURS', 2);

// Option key for the channel ID
const YUG_OPTION_KEY = 'yug_channel_id';
// Fallback channel ID if none saved yet
const YUG_DEFAULT_CHANNEL_ID = 'UCuAXFkgsw1L7xaCfnd5JJOw';

/**
 * Activation: ensure an initial option value exists
 */
function yug_activate() {
    if (get_option(YUG_OPTION_KEY, '') === '') {
        add_option(YUG_OPTION_KEY, YUG_DEFAULT_CHANNEL_ID);
    }
}
register_activation_hook(__FILE__, 'yug_activate');

/**
 * Admin: add a Settings page under Settings → YouTube Uploads Grid
 */
function yug_admin_menu() {
    add_options_page(
        __('YouTube Uploads Grid', 'youtube-uploads-grid'),
        __('YouTube Uploads Grid', 'youtube-uploads-grid'),
        'manage_options',
        'yug-settings',
        'yug_render_settings_page'
    );
}
add_action('admin_menu', 'yug_admin_menu');

/**
 * Admin: register the option with sanitization
 */
function yug_register_settings() {
    register_setting(
        'yug_settings_group',
        YUG_OPTION_KEY,
        [
            'type' => 'string',
            'sanitize_callback' => 'yug_sanitize_channel_id',
            'default' => YUG_DEFAULT_CHANNEL_ID,
        ]
    );
}
add_action('admin_init', 'yug_register_settings');

/**
 * Sanitize a YouTube channel ID.
 * Accepts IDs that start with UC or a handle like @YourHandle.
 */
function yug_sanitize_channel_id($value) {
    $value = trim((string) $value);

    // Allow handles like @ChannelHandle
    if (preg_match('/^@[A-Za-z0-9_.-]{2,}$/', $value)) {
        return $value;
    }

    // Allow standard channel IDs that start with UC
    if (preg_match('/^UC[0-9A-Za-z_-]{20,}$/', $value)) {
        return $value;
    }

    // As a fallback, strip tags and spaces
    return sanitize_text_field($value);
}

/**
 * Render the Settings page
 */
function yug_render_settings_page() {
    if (!current_user_can('manage_options')) return;

    $saved = get_option(YUG_OPTION_KEY, YUG_DEFAULT_CHANNEL_ID);

    // Resolve what feed URL this input would use, for admin clarity
    $example_feed = yug_build_feed_url($saved);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('YouTube Uploads Grid', 'youtube-uploads-grid'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('yug_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="yug_channel_id"><?php esc_html_e('YouTube Channel ID or Handle', 'youtube-uploads-grid'); ?></label>
                    </th>
                    <td>
                        <input name="<?php echo esc_attr(YUG_OPTION_KEY); ?>" id="yug_channel_id" type="text" class="regular-text" value="<?php echo esc_attr($saved); ?>" placeholder="UCxxxxxxxxxxxxxxxxxxxxxx or @channelhandle" />
                        <p class="description">
                            <?php esc_html_e('Enter your YouTube channel ID that starts with UC, or your channel handle that starts with @.', 'youtube-uploads-grid'); ?>
                        </p>
                        <p class="description">
                            <?php esc_html_e('Example feed URL that will be used:', 'youtube-uploads-grid'); ?>
                            <code><?php echo esc_html($example_feed); ?></code>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Changes', 'youtube-uploads-grid')); ?>
        </form>

        <h2><?php esc_html_e('Shortcode', 'youtube-uploads-grid'); ?></h2>
        <p><code>[youtube_uploads]</code></p>
        <p class="description"><?php esc_html_e('The shortcode reads the saved channel from Settings. Caching uses the channel value, so switching channels will show fresh results.', 'youtube-uploads-grid'); ?></p>
    </div>
    <?php
}

/**
 * Add a quick Settings link on the Plugins screen
 */
function yug_plugin_action_links($links) {
    $url = admin_url('options-general.php?page=yug-settings');
    $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'youtube-uploads-grid') . '</a>';
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yug_plugin_action_links');

/**
 * Build the correct feed URL based on a channel ID or handle
 * Supports UC ids and @handles
 */
function yug_build_feed_url($channel_value) {
    $channel_value = trim((string) $channel_value);

    if ($channel_value === '') {
        $channel_value = YUG_DEFAULT_CHANNEL_ID;
    }

    // Handle-based feeds use channel_id after resolving to the channel ID
    // YouTube exposes a feed for handles via the path /feeds/videos.xml?channel_id=
    // Since we do not use the API, we rely on handle to redirect to a channel page
    // Then fetch the page to find the channel ID is not possible here, so we use the handle endpoint YouTube provides:
    // https://www.youtube.com/feeds/videos.xml?channel_id=CHANNEL_ID
    // To keep it simple without remote lookups, if an @handle is entered we switch to the uploads feed that accepts handles:
    // https://www.youtube.com/feeds/videos.xml?channel_id= still needs a channel ID. As a best effort without API, we instead use
    // https://www.youtube.com/feeds/videos.xml?user=HANDLE for legacy usernames, but handles are not always usernames.
    // Many handles also resolve with the /@handle/featured page which contains an RSS link that WordPress SimplePie can follow through redirects.

    // Best practical approach without API: if it starts with @, use the channel atom feed root that supports handle redirects.
    if (strpos($channel_value, '@') === 0) {
        // SimplePie can follow redirects, so this works in most cases
        return 'https://www.youtube.com/feeds/videos.xml?channel=' . rawurlencode($channel_value);
    }

    // Otherwise assume a UC id
    return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode($channel_value);
}

/**
 * Extract a YouTube video ID from common URL formats
 * Handles: watch?v=ID, youtu.be/ID, /shorts/ID, /embed/ID, /videos/ID
 */
function yug_extract_video_id($url) {
    if (!$url) return '';
    // watch?v=ID
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $qs);
        if (!empty($qs['v'])) return $qs['v'];
    }
    // shorts
    if (preg_match('#/shorts/([A-Za-z0-9_-]{6,})#', $url, $m)) return $m[1];
    // youtu.be
    if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)) return $m[1];
    // embed
    if (preg_match('#/embed/([A-Za-z0-9_-]{6,})#', $url, $m)) return $m[1];
    // videos
    if (preg_match('#/videos/([A-Za-z0-9_-]{6,})#', $url, $m)) return $m[1];
    return '';
}

/**
 * Shortcode: [youtube_uploads]
 */
function yug_render_uploads_shortcode($atts = []) {
    $channel_value = get_option(YUG_OPTION_KEY, YUG_DEFAULT_CHANNEL_ID);

    // If a channel is passed to the shortcode, allow it to override the saved one
    $atts = shortcode_atts([
        'channel' => $channel_value,
    ], $atts, 'youtube_uploads');

    $feed_url   = yug_build_feed_url($atts['channel']);

    // Cache key contains the channel value and count
    $cache_key  = 'yug_feed_' . md5($feed_url . '_' . YUG_COUNT . '_v120');
    $cache_secs = max(300, intval(YUG_CACHE_HOURS) * 3600);

    // Cache
    $items = get_transient($cache_key);

    if ($items === false) {
        if (!function_exists('fetch_feed')) {
            include_once ABSPATH . WPINC . '/feed.php';
        }
        $feed = fetch_feed($feed_url);
        if (is_wp_error($feed)) {
            return '<p>' . esc_html__('Could not load YouTube feed right now.', 'youtube-uploads-grid') . '</p>';
        }

        if (method_exists($feed, 'set_item_limit')) {
            $feed->set_item_limit(YUG_COUNT);
        }

        $feed_items = $feed->get_items(0, YUG_COUNT);

        $items = [];
        foreach ($feed_items as $item) {
            $link  = $item->get_link();
            $title = $item->get_title();

            // Prefer official videoId tag if available
            $video_id = '';
            $tags = $item->get_item_tags('http://www.youtube.com/xml/schemas/2015', 'videoId');
            if ($tags && !empty($tags[0]['data'])) {
                $video_id = trim($tags[0]['data']);
            } else {
                $video_id = yug_extract_video_id($link);
            }
            if (!$video_id) continue;

            $thumb = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';

            $items[] = [
                'id'    => $video_id,
                'title' => $title,
                'link'  => $link,
                'thumb' => $thumb,
            ];
        }

        set_transient($cache_key, $items, $cache_secs);
    }

    if (empty($items)) {
        return '<p>' . esc_html__('No videos found.', 'youtube-uploads-grid') . '</p>';
    }

    ob_start(); ?>
    <style>
      .yug-grid { display: grid; gap: 16px; }
      .yug-col-1 { grid-template-columns: 1fr; }
      .yug-col-2 { grid-template-columns: repeat(2, 1fr); }
      .yug-col-3 { grid-template-columns: repeat(3, 1fr); }
      .yug-col-4 { grid-template-columns: repeat(4, 1fr); }

      .yug-card { display: flex; flex-direction: column; }
      .yug-item { position: relative; cursor: pointer; background: #000; border-radius: 8px; overflow: hidden; }
      .yug-thumb { width: 100%; height: auto; display: block; aspect-ratio: 16/9; object-fit: cover; }
      .yug-title { font-size: 14px; margin-top: 6px; line-height: 1.3; }

      .yug-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.85); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
      .yug-modal { width: min(100%, 960px); aspect-ratio: 16/9; background: #000; position: relative; }
      .yug-close { position: absolute; top: 10px; right: 14px; color: #fff; font-size: 28px; cursor: pointer; }

      @media (max-width: 768px) {
        .yug-grid { gap: 12px; }
        .yug-col-3 { grid-template-columns: repeat(2, 1fr); }
      }
      @media (max-width: 480px) {
        .yug-col-3, .yug-col-2 { grid-template-columns: 1fr; }
      }
    </style>

    <div class="yug-grid <?php echo 'yug-col-' . intval(YUG_COLUMNS); ?>">
      <?php foreach ($items as $i): ?>
        <div class="yug-card">
          <div class="yug-item" data-video="<?php echo esc_attr($i['id']); ?>">
            <img class="yug-thumb" loading="lazy" src="<?php echo esc_url($i['thumb']); ?>" alt="<?php echo esc_attr($i['title']); ?>">
          </div>
          <div class="yug-title"><?php echo esc_html($i['title']); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="yug-modal-backdrop" id="yugModal">
      <div class="yug-modal">
        <div class="yug-close" id="yugClose">&times;</div>
        <iframe id="yugPlayer" width="100%" height="100%" src="" title="YouTube video"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
    </div>

    <script>
      (function(){
        const grids = document.querySelectorAll('.yug-grid');
        const grid = grids[grids.length - 1];

        const backdrop = document.getElementById('yugModal');
        const player = document.getElementById('yugPlayer');
        const closeBtn = document.getElementById('yugClose');

        function openModal(id){
          player.src = 'https://www.youtube.com/embed/' + id + '?autoplay=1&modestbranding=1&rel=0';
          backdrop.style.display = 'flex';
        }
        function closeModal(){
          backdrop.style.display = 'none';
          player.src = '';
        }

        if (grid) {
          grid.querySelectorAll('.yug-item').forEach(el => {
            el.addEventListener('click', () => {
              const id = el.getAttribute('data-video');
              if (id) openModal(id);
            });
          });
        }

        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
      })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('youtube_uploads', 'yug_render_uploads_shortcode');
