<?php
/**
 * Plugin Name: Super-Swank Featured Image
 * Plugin URI: https://edequalsaweso.me/super-swank-featured-image
 * Description: Sets a default featured image for posts, pages, and custom post types when no featured image is set.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 8.2
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
        add_filter( 'get_post_thumbnail_id', array( $this, 'set_default_thumbnail' ), 10, 2 );
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
}

// Initialize the plugin
function super_swank_featured_image(): Super_Swank_Featured_Image {
    return Super_Swank_Featured_Image::get_instance();
}
super_swank_featured_image(); 