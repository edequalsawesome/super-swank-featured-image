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
     * Default featured image ID.
     *
     * @var int
     */
    private int $default_image_id = 0;

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
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_filter( 'get_post_thumbnail_id', array( $this, 'set_default_thumbnail' ), 10, 2 );
        
        // Add block editor support
        add_action( 'init', array( $this, 'register_block_settings' ) );
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
        register_setting(
            'ssfi_options',
            'ssfi_default_image',
            array(
                'type'              => 'integer',
                'description'       => __( 'Default featured image ID', 'super-swank-featured-image' ),
                'sanitize_callback' => 'absint',
                'show_in_rest'     => true,
                'default'          => 0,
            )
        );

        register_setting(
            'ssfi_options',
            'ssfi_default_image_crop',
            array(
                'type'              => 'object',
                'description'       => __( 'Default featured image crop settings', 'super-swank-featured-image' ),
                'show_in_rest'     => true,
                'default'          => array(),
            )
        );
    }

    /**
     * Register block editor settings.
     *
     * @return void
     */
    public function register_block_settings(): void {
        register_setting(
            'ssfi_block_options',
            'ssfi_default_image',
            array(
                'type'              => 'integer',
                'description'       => __( 'Default featured image ID', 'super-swank-featured-image' ),
                'sanitize_callback' => 'absint',
                'show_in_rest'     => true,
                'default'          => 0,
            )
        );

        // Register settings for block themes
        if ( wp_is_block_theme() ) {
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
        }

        // Add settings to theme.json
        add_filter('block_editor_settings_all', function($settings) {
            $settings['__experimentalFeatures']['custom']['ssfi'] = array(
                'defaultImage' => get_option('ssfi_default_image', 0)
            );
            return $settings;
        });
    }

    /**
     * Add settings page for classic themes.
     *
     * @return void
     */
    public function add_settings_page(): void {
        if ( ! wp_is_block_theme() ) {
            add_options_page(
                __( 'Default Featured Image', 'super-swank-featured-image' ),
                __( 'Default Featured Image', 'super-swank-featured-image' ),
                'manage_options',
                'ssfi-settings',
                array( $this, 'render_settings_page' )
            );
        }
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_admin_scripts( string $hook ): void {
        // For block themes, enqueue the block editor script
        if ( wp_is_block_theme() ) {
            add_action('enqueue_block_editor_assets', function() {
                wp_enqueue_script(
                    'ssfi-block-editor',
                    SSFI_PLUGIN_URL . 'assets/js/block-editor.js',
                    array(
                        'wp-blocks',
                        'wp-i18n',
                        'wp-element',
                        'wp-components',
                        'wp-data',
                        'wp-plugins',
                        'wp-edit-post',
                        'wp-media-utils'
                    ),
                    SSFI_VERSION,
                    true
                );
            });
        }

        // For classic themes, enqueue the admin scripts
        if ( 'settings_page_ssfi-settings' !== $hook ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        
        wp_enqueue_style(
            'ssfi-admin-styles',
            SSFI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SSFI_VERSION
        );

        wp_enqueue_script(
            'ssfi-admin-script',
            SSFI_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-dialog', 'wp-i18n', 'media-editor' ),
            SSFI_VERSION,
            true
        );

        wp_localize_script(
            'ssfi-admin-script',
            'ssfiAdmin',
            array(
                'cropTitle' => __( 'Crop Default Featured Image', 'super-swank-featured-image' ),
                'cropButton' => __( 'Crop Image', 'super-swank-featured-image' ),
                'cancelButton' => __( 'Cancel', 'super-swank-featured-image' ),
                'aspectRatio' => apply_filters( 'ssfi_crop_aspect_ratio', 16 / 9 ),
                'minWidth' => apply_filters( 'ssfi_crop_min_width', 200 ),
                'minHeight' => apply_filters( 'ssfi_crop_min_height', 200 ),
            )
        );
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
     * Render the settings page for classic themes.
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get the default image ID and crop settings
        $default_image_id = (int) get_option( 'ssfi_default_image', 0 );
        $crop_settings = get_option( 'ssfi_default_image_crop', array() );
        $image_url = $default_image_id ? wp_get_attachment_image_url( $default_image_id, 'full' ) : '';
        $thumbnail_url = $default_image_id ? wp_get_attachment_image_url( $default_image_id, 'thumbnail' ) : '';
        
        ?>
        <div class="wrap ssfi-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'ssfi_options' );
                do_settings_sections( 'ssfi-settings' );
                ?>
                <div class="ssfi-image-section">
                    <h2><?php esc_html_e( 'Default Featured Image', 'super-swank-featured-image' ); ?></h2>
                    <div class="ssfi-image-preview">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $thumbnail_url ); ?>" 
                                 alt="" 
                                 class="ssfi-preview-image"
                                 data-full-src="<?php echo esc_url( $image_url ); ?>">
                        <?php else : ?>
                            <div class="ssfi-no-image">
                                <?php esc_html_e( 'No image selected', 'super-swank-featured-image' ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="ssfi_default_image" id="ssfi_default_image" value="<?php echo esc_attr( $default_image_id ); ?>">
                    <input type="hidden" name="ssfi_default_image_crop" id="ssfi_default_image_crop" value="<?php echo esc_attr( wp_json_encode( $crop_settings ) ); ?>">
                    
                    <div class="ssfi-image-actions">
                        <button type="button" class="button button-primary" id="ssfi-select-image">
                            <?php esc_html_e( 'Select Image', 'super-swank-featured-image' ); ?>
                        </button>
                        <?php if ( $image_url ) : ?>
                            <button type="button" class="button" id="ssfi-crop-image">
                                <?php esc_html_e( 'Crop Image', 'super-swank-featured-image' ); ?>
                            </button>
                            <button type="button" class="button" id="ssfi-remove-image">
                                <?php esc_html_e( 'Remove Image', 'super-swank-featured-image' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ssfi-image-settings">
                    <h2><?php esc_html_e( 'Image Settings', 'super-swank-featured-image' ); ?></h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Image Size', 'super-swank-featured-image' ); ?>
                            </th>
                            <td>
                                <?php if ( $image_url ) : ?>
                                    <?php
                                    $image_data = wp_get_attachment_metadata( $default_image_id );
                                    if ( $image_data ) {
                                        printf(
                                            /* translators: 1: Image width, 2: Image height */
                                            esc_html__( 'Original: %1$d × %2$d pixels', 'super-swank-featured-image' ),
                                            (int) $image_data['width'],
                                            (int) $image_data['height']
                                        );
                                    }
                                    ?>
                                <?php else : ?>
                                    <?php esc_html_e( 'No image selected', 'super-swank-featured-image' ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <div id="ssfi-crop-dialog" style="display:none;">
            <div class="ssfi-crop-area">
                <img src="" alt="" id="ssfi-crop-image-element">
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
function super_swank_featured_image(): Super_Swank_Featured_Image {
    return Super_Swank_Featured_Image::get_instance();
}
super_swank_featured_image(); 