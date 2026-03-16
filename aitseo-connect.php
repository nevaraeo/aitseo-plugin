<?php
/**
 * Plugin Name: AITSEO Connect
 * Plugin URI: https://aitseo.com
 * Description: Connect your WordPress site to Aitseo for automatic article publishing.
 * Version: 1.0.0
 * Author: AITSEO
 * Author URI: https://aitseo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aitseo-connect
 */

if (!defined('ABSPATH')) {
    exit;
}

class SEOWriterConnect {

    const OPTION_KEY = 'aitseo_connection_key';
    const OPTION_ENABLED = 'aitseo_enabled';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_head', [$this, 'output_schema_markup'], 1);
        register_activation_hook(__FILE__, [$this, 'on_activate']);
    }

    /**
     * Generate connection key on activation
     */
    public function on_activate() {
        if (!get_option(self::OPTION_KEY)) {
            $key = 'swc_' . bin2hex(random_bytes(32));
            update_option(self::OPTION_KEY, $key);
        }
        update_option(self::OPTION_ENABLED, '1');
    }

    /**
     * Admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Aitseo Connect',
            'Aitseo Connect',
            'manage_options',
            'aitseo-connect',
            [$this, 'settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('aitseo_connect', self::OPTION_KEY);
        register_setting('aitseo_connect', self::OPTION_ENABLED);
    }

    /**
     * Settings page
     */
    public function settings_page() {
        $key = get_option(self::OPTION_KEY, '');
        $enabled = get_option(self::OPTION_ENABLED, '1');
        ?>
        <div class="wrap">
            <h1>Aitseo Connect</h1>
            <p>Connect your WordPress site to <strong>Aitseo</strong> for automatic article publishing.</p>

            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin:20px 0;max-width:600px;">
                <h2 style="margin-top:0;">Connection Key</h2>
                <p style="color:#6b7280;font-size:14px;">Copy this key and paste it in Aitseo dashboard → Platform Integration → WordPress.</p>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;font-family:monospace;font-size:14px;word-break:break-all;user-select:all;">
                    <?php echo esc_html($key); ?>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($key); ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy Key',2000);"
                    style="margin-top:12px;background:#1a1a1a;color:#fff;border:none;padding:8px 20px;border-radius:8px;cursor:pointer;font-size:14px;">
                    Copy Key
                </button>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('aitseo_connect'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Connection</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo self::OPTION_ENABLED; ?>" value="1" <?php checked($enabled, '1'); ?>>
                                Allow Aitseo to publish articles to this site
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Regenerate Key</th>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=aitseo-connect&action=regenerate'), 'swc_regenerate'); ?>"
                               class="button" onclick="return confirm('Are you sure? The old key will stop working.');">
                                Regenerate Connection Key
                            </a>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php

        // Handle regenerate
        if (isset($_GET['action']) && $_GET['action'] === 'regenerate' && wp_verify_nonce($_GET['_wpnonce'], 'swc_regenerate')) {
            $new_key = 'swc_' . bin2hex(random_bytes(32));
            update_option(self::OPTION_KEY, $new_key);
            echo '<script>location.reload();</script>';
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('aitseo/v1', '/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_verify'],
            'permission_callback' => [$this, 'check_connection_key'],
        ]);

        register_rest_route('aitseo/v1', '/publish', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_publish'],
            'permission_callback' => [$this, 'check_connection_key'],
        ]);

        register_rest_route('aitseo/v1', '/site-info', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_site_info'],
            'permission_callback' => [$this, 'check_connection_key'],
        ]);

        register_rest_route('aitseo/v1', '/posts', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_get_posts'],
            'permission_callback' => [$this, 'check_connection_key'],
        ]);
    }

    /**
     * Verify connection key from request header
     */
    public function check_connection_key($request) {
        $enabled = get_option(self::OPTION_ENABLED, '1');
        if ($enabled !== '1') {
            return new WP_Error('disabled', 'Aitseo Connect is disabled', ['status' => 403]);
        }

        $provided_key = $request->get_header('X-Connection-Key');
        $stored_key = get_option(self::OPTION_KEY, '');

        if (!$provided_key || !$stored_key || !hash_equals($stored_key, $provided_key)) {
            return new WP_Error('unauthorized', 'Invalid connection key', ['status' => 401]);
        }

        return true;
    }

    /**
     * Handle verify request
     */
    public function handle_verify($request) {
        return rest_ensure_response([
            'success' => true,
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => '1.0.0',
        ]);
    }

    /**
     * Handle site-info request - returns site details, categories, tags, and post stats
     */
    public function handle_site_info($request) {
        // Get all categories
        $categories = get_categories(['hide_empty' => false, 'orderby' => 'name']);
        $cat_list = [];
        foreach ($categories as $cat) {
            $cat_list[] = [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $cat->count,
                'parent' => $cat->parent,
            ];
        }

        // Get all tags
        $tags = get_tags(['hide_empty' => false, 'orderby' => 'name']);
        $tag_list = [];
        foreach ($tags as $tag) {
            $tag_list[] = [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count,
            ];
        }

        // Post counts
        $post_counts = wp_count_posts('post');

        // Get active theme
        $theme = wp_get_theme();

        return rest_ensure_response([
            'success' => true,
            'site' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_site_url(),
                'home_url' => get_home_url(),
                'wp_version' => get_bloginfo('version'),
                'language' => get_bloginfo('language'),
                'timezone' => get_option('timezone_string') ?: get_option('gmt_offset'),
                'theme' => $theme->get('Name'),
                'posts_published' => (int)$post_counts->publish,
                'posts_draft' => (int)$post_counts->draft,
            ],
            'categories' => $cat_list,
            'tags' => $tag_list,
        ]);
    }

    /**
     * Handle get posts request - returns published posts/pages for internal linking
     */
    public function handle_get_posts($request) {
        $params = $request->get_json_params();
        $per_page = min((int)($params['per_page'] ?? 100), 200);
        $post_types = $params['post_types'] ?? ['post', 'page'];

        $posts = get_posts([
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $result = [];
        foreach ($posts as $post) {
            $cats = [];
            if ($post->post_type === 'post') {
                $terms = get_the_category($post->ID);
                foreach ($terms as $term) {
                    $cats[] = $term->name;
                }
            }
            $result[] = [
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'url'        => get_permalink($post->ID),
                'type'       => $post->post_type,
                'date'       => $post->post_date,
                'categories' => $cats,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'total'   => count($result),
            'posts'   => $result,
        ]);
    }

    /**
     * Handle publish request
     */
    public function handle_publish($request) {
        $params = $request->get_json_params();

        $title = sanitize_text_field($params['title'] ?? '');
        $content = wp_kses_post($params['content'] ?? '');
        $meta_description = sanitize_text_field($params['meta_description'] ?? '');
        $schema_markup = $params['schema_markup'] ?? '';
        $status = in_array($params['status'] ?? '', ['publish', 'draft', 'pending', 'future']) ? $params['status'] : 'draft';
        $categories = $params['categories'] ?? [];
        $tags = $params['tags'] ?? [];
        $scheduled_date = sanitize_text_field($params['date'] ?? '');
        $featured_image_url = esc_url_raw($params['featured_image_url'] ?? '');

        if (empty($title)) {
            return new WP_Error('missing_title', 'Title is required', ['status' => 400]);
        }

        // Store schema markup as post meta (will be output in <head> via wp_head hook)
        // Do NOT append to content — WordPress strips <script> tags from post_content

        // Create post
        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
            'post_type' => 'post',
            'post_author' => 1, // Default to admin
        ];

        // Handle scheduled date for future posts
        if ($status === 'future' && !empty($scheduled_date)) {
            $post_data['post_date'] = date('Y-m-d H:i:s', strtotime($scheduled_date));
            $post_data['post_date_gmt'] = get_gmt_from_date($post_data['post_date']);
        }

        // Handle categories (supports both numeric IDs and category names)
        if (!empty($categories)) {
            $cat_ids = [];
            foreach ($categories as $cat_value) {
                if (is_numeric($cat_value)) {
                    // Numeric ID - use directly if the category exists
                    $term = get_term((int) $cat_value, 'category');
                    if ($term && !is_wp_error($term)) {
                        $cat_ids[] = (int) $cat_value;
                    }
                } else {
                    // String name - look up or create
                    $cat = get_cat_ID($cat_value);
                    if ($cat === 0) {
                        $new_cat = wp_create_category($cat_value);
                        if (!is_wp_error($new_cat)) {
                            $cat_ids[] = $new_cat;
                        }
                    } else {
                        $cat_ids[] = $cat;
                    }
                }
            }
            if (!empty($cat_ids)) {
                $post_data['post_category'] = $cat_ids;
            }
        }

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return new WP_Error('insert_failed', $post_id->get_error_message(), ['status' => 500]);
        }

        // Handle tags
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags);
        }

        // Set SEO meta fields (Yoast SEO + RankMath + generic)
        $seo_title = sanitize_text_field($params['seo_title'] ?? '');
        $focus_keyword = sanitize_text_field($params['focus_keyword'] ?? '');

        if (!empty($meta_description)) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            update_post_meta($post_id, 'rank_math_description', $meta_description);
            update_post_meta($post_id, '_aitseo_meta_description', $meta_description);
        }

        if (!empty($seo_title)) {
            update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
            update_post_meta($post_id, 'rank_math_title', $seo_title);
        }

        if (!empty($focus_keyword)) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
        }

        // Ensure robots meta allows indexing
        update_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', '0');
        update_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', '0');
        update_post_meta($post_id, 'rank_math_robots', ['index', 'follow']);

        // Set featured image from URL
        if (!empty($featured_image_url)) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $tmp = download_url($featured_image_url, 30);
            if (!is_wp_error($tmp)) {
                $file_array = [
                    'name'     => sanitize_file_name(basename(parse_url($featured_image_url, PHP_URL_PATH))) ?: 'featured-image.jpg',
                    'tmp_name' => $tmp,
                ];
                $attach_id = media_handle_sideload($file_array, $post_id, $title);
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            } else {
                @unlink($tmp);
            }
        }

        // Store schema markup as post meta for head output
        if (!empty($schema_markup)) {
            update_post_meta($post_id, '_aitseo_schema_markup', $schema_markup);
        }

        // Mark as published by Aitseo
        update_post_meta($post_id, '_aitseo_published', true);
        update_post_meta($post_id, '_aitseo_published_at', current_time('mysql'));

        $post = get_post($post_id);
        $permalink = get_permalink($post_id);

        // Notify search engines about new content
        if ($status === 'publish' && $permalink) {
            $this->ping_indexnow($permalink);
            $this->ping_sitemaps();
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'url' => $permalink,
            'link' => $permalink,
            'status' => $post->post_status,
            'title' => $post->post_title,
        ]);
    }

    /**
     * Output Schema JSON-LD markup in <head> for Aitseo-published posts
     */
    public function output_schema_markup() {
        if (!is_singular('post')) return;

        $post_id = get_the_ID();
        if (!$post_id) return;

        $schema = get_post_meta($post_id, '_aitseo_schema_markup', true);
        if (empty($schema)) return;

        // Validate JSON
        $decoded = json_decode($schema);
        if (json_last_error() !== JSON_ERROR_NONE) return;

        echo '<script type="application/ld+json">' . wp_json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
    }

    /**
     * Ping IndexNow API to notify search engines about new/updated content
     */
    private function ping_indexnow($url) {
        $host = parse_url(get_site_url(), PHP_URL_HOST);
        $key = substr(md5(get_option(self::OPTION_KEY, '')), 0, 32);

        // Store IndexNow key as a text file (required by protocol)
        $key_file = ABSPATH . $key . '.txt';
        if (!file_exists($key_file)) {
            @file_put_contents($key_file, $key);
        }

        // Submit to IndexNow (supported by Google, Bing, Yandex, Naver)
        $endpoints = [
            'https://api.indexnow.org/indexnow',
            'https://www.bing.com/indexnow',
        ];

        foreach ($endpoints as $endpoint) {
            $api_url = add_query_arg([
                'url' => $url,
                'key' => $key,
                'keyLocation' => get_site_url() . '/' . $key . '.txt',
            ], $endpoint);

            wp_remote_get($api_url, [
                'timeout' => 10,
                'blocking' => false,
            ]);
        }

        // Also submit directly to Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($url), [
            'timeout' => 10,
            'blocking' => false,
        ]);
    }

    /**
     * Ping Google and Bing about sitemap updates
     */
    private function ping_sitemaps() {
        $sitemap_url = get_site_url() . '/sitemap_index.xml';

        // Try common sitemap URLs
        $possible_sitemaps = [
            get_site_url() . '/sitemap_index.xml',   // Yoast
            get_site_url() . '/sitemap.xml',          // Generic / RankMath
            get_site_url() . '/wp-sitemap.xml',       // WordPress core
        ];

        foreach ($possible_sitemaps as $sitemap) {
            // Check if sitemap exists (only first one that works)
            $check = wp_remote_head($sitemap, ['timeout' => 5]);
            if (!is_wp_error($check) && wp_remote_retrieve_response_code($check) === 200) {
                $sitemap_url = $sitemap;
                break;
            }
        }

        // Ping Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url), [
            'timeout' => 10,
            'blocking' => false,
        ]);

        // Ping Bing (also covers Yahoo)
        wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url), [
            'timeout' => 10,
            'blocking' => false,
        ]);
    }
}

new SEOWriterConnect();
