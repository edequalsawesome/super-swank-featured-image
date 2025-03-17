<?php
/**
 * Plugin Name: Super-Swank Featured Image
 * Plugin URI: https://edequalsaweso.me/super-swank-featured-image
 * Description: Sets a default featured image for posts, pages, and custom post types when no featured image is set.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 8.1
 * Author: eD! Thomas
 * Author URI: https://edequalsaweso.me
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: super-swank-featured-image
 * Domain Path: /languages
 *
 * @package SuperSwankFeaturedImage
 */

declare(strict_types=1);

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

// Define plugin constants
define( 'SSFI_VERSION', '1.0.0' );
define( 'SSFI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define image size constants
define( 'SSFI_FB_WIDTH', 1200 );
define( 'SSFI_FB_HEIGHT', 630 );
define( 'SSFI_TWITTER_WIDTH', 1200 );
define( 'SSFI_TWITTER_HEIGHT', 600 );
define( 'SSFI_INSTAGRAM_SIZE', 1080 );
define( 'SSFI_PINTEREST_WIDTH', 1000 );
define( 'SSFI_PINTEREST_HEIGHT', 1500 );

// Main plugin class
class Super_Swank_Featured_Image {
    /**
     * Instance of this class.
     *
     * @var ?self
     */
    private static ?self $instance = null;

    /**
     * Get an instance of this class.
     *
     * @return self
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Only initialize if using a block theme
        if ( ! wp_is_block_theme() ) {
            add_action( 'admin_notices', array( $this, 'block_theme_requirement_notice' ) );
            return;
        }

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_settings' ) );
        add_action( 'init', array( $this, 'register_image_sizes' ) );
        add_filter( 'get_post_thumbnail_id', array( $this, 'set_default_thumbnail' ), 10, 2 );
        
        // Add social media meta tag hooks
        add_action( 'wp_head', array( $this, 'add_social_meta_tags' ), 5 );
        
        // SEO Plugin compatibility
        add_filter( 'wpseo_opengraph_image', array( $this, 'maybe_set_social_image' ) ); // Yoast SEO compatibility
        add_filter( 'wpseo_twitter_image', array( $this, 'maybe_set_social_image' ) ); // Yoast SEO compatibility
        add_filter( 'rank_math/opengraph/image', array( $this, 'maybe_set_social_image' ) ); // Rank Math SEO compatibility
        add_filter( 'rank_math/twitter/image', array( $this, 'maybe_set_social_image' ) ); // Rank Math SEO compatibility
        
        // Jetpack compatibility
        add_filter( 'jetpack_open_graph_image_default', array( $this, 'maybe_set_jetpack_default_image' ) );
        add_filter( 'jetpack_images_get_images', array( $this, 'maybe_add_default_to_jetpack_images' ), 10, 3 );
        add_filter( 'jetpack_sharing_twitter_image', array( $this, 'maybe_set_social_image' ) );

        // Add image editing support
        add_filter( 'admin_post_thumbnail_html', array( $this, 'add_image_editing_button' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_image_editing_scripts' ) );
    }

    /**
     * Display notice if not using a block theme.
     *
     * @return void
     */
    public function block_theme_requirement_notice(): void {
        $class = 'notice notice-error';
        $message = __( 'Super-Swank Featured Image requires a block theme to function. Please activate a block theme or use a different featured image plugin.', 'super-swank-featured-image' );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

    /**
     * Load plugin textdomain.
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'super-swank-featured-image',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings(): void {
        // Register the setting
        register_setting(
            'theme',
            'ssfi_default_image',
            array(
                'type'              => 'integer',
                'description'       => __( 'Default featured image ID', 'super-swank-featured-image' ),
                'sanitize_callback' => 'absint',
                'show_in_rest'     => true,
                'default'          => 0,
            )
        );

        // Add settings to theme.json
        add_filter('wp_theme_json_data_default', function($theme_json) {
            $new_data = array(
                'version'  => 2,
                'settings' => array(
                    'custom' => array(
                        'ssfi' => array(
                            'defaultImage' => get_option('ssfi_default_image', 0)
                        )
                    )
                )
            );
            return $theme_json->update_with($new_data);
        });

        // Enqueue block editor assets
        add_action('enqueue_block_editor_assets', function() {
            $asset_file = include(SSFI_PLUGIN_DIR . 'build/block-editor/index.asset.php');
            
            wp_enqueue_script(
                'ssfi-block-editor',
                SSFI_PLUGIN_URL . 'build/block-editor/index.js',
                array_merge(
                    $asset_file['dependencies'],
                    array(
                        'wp-blocks',
                        'wp-i18n',
                        'wp-element',
                        'wp-components',
                        'wp-data',
                        'wp-plugins',
                        'wp-edit-site',
                        'wp-block-editor',
                        'wp-media-utils'
                    )
                ),
                $asset_file['version']
            );

            wp_set_script_translations(
                'ssfi-block-editor',
                'super-swank-featured-image',
                SSFI_PLUGIN_DIR . 'languages'
            );

            wp_localize_script(
                'ssfi-block-editor',
                'ssfiSettings',
                array(
                    'defaultImage' => get_option('ssfi_default_image', 0),
                )
            );
        });
    }

    /**
     * Register custom image sizes.
     *
     * @return void
     */
    public function register_image_sizes(): void {
        // Facebook/LinkedIn
        add_image_size(
            'ssfi-facebook',
            SSFI_FB_WIDTH,
            SSFI_FB_HEIGHT,
            true
        );

        // Twitter
        add_image_size(
            'ssfi-twitter',
            SSFI_TWITTER_WIDTH,
            SSFI_TWITTER_HEIGHT,
            true
        );

        // Instagram
        add_image_size(
            'ssfi-instagram',
            SSFI_INSTAGRAM_SIZE,
            SSFI_INSTAGRAM_SIZE,
            true
        );

        // Pinterest
        add_image_size(
            'ssfi-pinterest',
            SSFI_PINTEREST_WIDTH,
            SSFI_PINTEREST_HEIGHT,
            true
        );
    }

    /**
     * Add image editing button to featured image meta box.
     *
     * @param string $content The featured image meta box content.
     * @param int    $post_id The post ID.
     * @param int    $thumbnail_id The thumbnail ID.
     * @return string
     */
    public function add_image_editing_button( string $content, int $post_id, ?int $thumbnail_id ): string {
        if ( $thumbnail_id ) {
            $platforms = array(
                'facebook' => __('Facebook/LinkedIn', 'super-swank-featured-image'),
                'twitter' => __('Twitter', 'super-swank-featured-image'),
                'instagram' => __('Instagram', 'super-swank-featured-image'),
                'pinterest' => __('Pinterest', 'super-swank-featured-image')
            );

            $content .= '<div class="ssfi-crop-buttons">';
            $content .= '<p class="ssfi-crop-buttons-label">' . esc_html__('Edit Social Media Crops:', 'super-swank-featured-image') . '</p>';
            
            foreach ($platforms as $platform => $label) {
                $edit_url = get_edit_post_link($thumbnail_id);
                $button = sprintf(
                    '<a href="%s" class="button" style="margin-right: 5px; margin-bottom: 5px;">%s</a>',
                    esc_url(add_query_arg(array(
                        'context' => 'ssfi-' . $platform,
                        'return_url' => urlencode(get_edit_post_link($post_id)),
                    ), $edit_url)),
                    esc_html($label)
                );
                $content .= $button;
            }
            
            $content .= '</div>';
        }
        return $content;
    }

    /**
     * Enqueue scripts for image editing.
     *
     * @return void
     */
    public function enqueue_image_editing_scripts(): void {
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
            wp_enqueue_media();
        }
    }

    /**
     * Set default thumbnail if none is set.
     *
     * @param int|null $thumbnail_id The post thumbnail ID or null.
     * @param int|WP_Post $post The post ID or WP_Post object.
     * @return int|null
     */
    public function set_default_thumbnail( ?int $thumbnail_id, $post ): ?int {
        if ( empty( $thumbnail_id ) ) {
            $default_image_id = (int) get_option( 'ssfi_default_image', 0 );
            if ( $default_image_id > 0 ) {
                return $default_image_id;
            }
        }
        return $thumbnail_id;
    }

    /**
     * Get the appropriate image URL for social media.
     *
     * @param string $platform The social media platform ('facebook', 'twitter', 'instagram', 'pinterest').
     * @return string|null The URL of the image to use, or null if no image is available.
     */
    private function get_social_image_url(string $platform = 'facebook'): ?string {
        $image_id = null;
        $default_image_id = (int) get_option('ssfi_default_image', 0);
        
        // Handle front page
        if (is_front_page()) {
            if (is_page()) {
                $image_id = get_post_thumbnail_id(get_option('page_on_front'));
            }
            if (!$image_id && $default_image_id) {
                $image_id = $default_image_id;
            }
        } else {
            // Handle all other pages
            $image_id = get_post_thumbnail_id();
            if (!$image_id && $default_image_id) {
                $image_id = $default_image_id;
            }
        }

        if ($image_id) {
            // Try to get the platform-specific cropped version
            $image = wp_get_attachment_image_src($image_id, 'ssfi-' . $platform);
            
            // Fall back to full size if cropped version doesn't exist
            if (!$image) {
                $image = wp_get_attachment_image_src($image_id, 'full');
            }
            
            return $image ? $image[0] : null;
        }

        return null;
    }

    /**
     * Add social media meta tags to the head.
     *
     * @return void
     */
    public function add_social_meta_tags(): void {
        // Don't add tags if a SEO plugin or Jetpack's OpenGraph is active
        if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || class_exists('Jetpack') && Jetpack::is_module_active('publicize')) {
            return;
        }

        // Facebook/LinkedIn image
        $fb_image_url = $this->get_social_image_url('facebook');
        if ($fb_image_url) {
            echo '<meta property="og:image" content="' . esc_url($fb_image_url) . '" />' . "\n";
            echo '<meta property="og:image:width" content="' . esc_attr(SSFI_FB_WIDTH) . '" />' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr(SSFI_FB_HEIGHT) . '" />' . "\n";
        }

        // Twitter image
        $twitter_image_url = $this->get_social_image_url('twitter');
        if ($twitter_image_url) {
            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($twitter_image_url) . '" />' . "\n";
        }

        // Pinterest image (optional, as Pinterest can use og:image)
        $pinterest_image_url = $this->get_social_image_url('pinterest');
        if ($pinterest_image_url && $pinterest_image_url !== $fb_image_url) {
            echo '<meta property="og:image:pinterest" content="' . esc_url($pinterest_image_url) . '" />' . "\n";
        }
    }

    /**
     * Filter callback for SEO plugins to set social image.
     *
     * @param string $image_url The current image URL.
     * @param string $platform The platform requesting the image ('facebook', 'twitter', etc.).
     * @return string The filtered image URL.
     */
    public function maybe_set_social_image(string $image_url, string $platform = 'facebook'): string {
        if (empty($image_url)) {
            $new_image_url = $this->get_social_image_url($platform);
            if ($new_image_url) {
                return $new_image_url;
            }
        }
        return $image_url;
    }

    /**
     * Set default image for Jetpack's OpenGraph implementation.
     *
     * @param string $image_url The default image URL.
     * @return string The filtered image URL.
     */
    public function maybe_set_jetpack_default_image(string $image_url): string {
        $new_image_url = $this->get_social_image_url('facebook');
        return $new_image_url ?: $image_url;
    }

    /**
     * Add default image to Jetpack's image detection results.
     *
     * @param array $images Array of images found by Jetpack.
     * @param int $post_id Post ID.
     * @param array $args Array of arguments.
     * @return array Modified array of images.
     */
    public function maybe_add_default_to_jetpack_images(array $images, int $post_id, array $args): array {
        // If no images were found and we have a default
        if (empty($images)) {
            $default_image_id = (int) get_option('ssfi_default_image', 0);
            if ($default_image_id > 0) {
                $image_url = wp_get_attachment_url($default_image_id);
                if ($image_url) {
                    $images[] = array(
                        'type'       => 'image',
                        'from'       => 'default',
                        'src'        => $image_url,
                        'src_width'  => SSFI_FB_WIDTH,
                        'src_height' => SSFI_FB_HEIGHT,
                        'href'       => get_permalink($post_id),
                    );
                }
            }
        }
        return $images;
    }
}

// Initialize the plugin
function super_swank_featured_image(): Super_Swank_Featured_Image {
    return Super_Swank_Featured_Image::get_instance();
}
super_swank_featured_image(); 