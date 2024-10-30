<?php
/**
 * Plugin Name: Layoutist
 * Description: Free blocks for Elementor.
 * Version: 1.0.0
 * Author: WPizard
 * Text Domain: layoutist
 *
 * @package Layoutist
 */

defined( 'ABSPATH' ) or die();

use Elementor\Plugin;
use Elementor\TemplateLibrary\Source_Local;

if ( ! class_exists( 'Layoutist' ) ) {

    /**
     * Layoutist class.
     *
     * @since 1.0.0
     */
    class Layoutist {

        /**
         * Layoutist instance.
         *
         * @since 1.0.0
         *
         * @access private
         * @var Layoutist
         */
        private static $instance;

        /**
         * The plugin version number.
         *
         * @since 1.0.0
         *
         * @access private
         * @var string
         */
        private static $version;

	    /**
	     * The plugin basename.
	     *
	     * @since 1.0.0
	     *
	     * @access private
	     * @var string
	     */
	    private static $plugin_basename;

        /**
         * The plugin name.
         *
         * @since 1.0.0
         *
         * @access private
         * @var string
         */
        private static $plugin_name;

        /**
         * The plugin directory.
         *
         * @since 1.0.0
         *
         * @access private
         * @var string
         */
        private static $plugin_dir;

        /**
         * The plugin URL.
         *
         * @since 1.0.0
         *
         * @access private
         * @var string
         */
        private static $plugin_url;

        /**
         * Returns the Layoutist instance.
         *
         * @since 1.0.0
         *
         * @return Layoutist
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @since 1.0.0
         */
        public function __construct() {
            $this->define_constants();
            $this->add_actions();
        }

        /**
         * Defines constants used by the plugin.
         *
         * @since 1.0.0
         */
        protected function define_constants() {
            $plugin_data = get_file_data( __FILE__, array( 'Plugin Name', 'Version' ), 'layoutist' );

            self::$plugin_basename = plugin_basename( __FILE__ );
            self::$plugin_name = array_shift( $plugin_data );
            self::$version = array_shift( $plugin_data );
            self::$plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) );
            self::$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );
	    }

        /**
         * Adds required action hooks.
         *
         * @since 1.0.0
         * @access protected
         */
        protected function add_actions() {
            add_action( 'init', [ $this, 'init' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'register_script_dependencies' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
            add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
            add_action( 'wp_ajax_layoutist', [ $this, 'ajax_handler' ] );
        }

        /**
         * Init.
         *
         * @since 1.0.0
         */
        public function init() {
            load_plugin_textdomain( 'layoutist', false, $this->plugin_dir() . '/languages' );
        }

        /**
         * Registers script dependencies.
         *
         * @since 1.0.0
         */
        public function register_script_dependencies() {

            wp_register_script(
                'layoutist-vue',
                $this->plugin_url() . 'assets/lib/vue/vue' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [],
                '2.6.6',
                true
            );

            wp_register_script(
                'layoutist-axios',
                $this->plugin_url() . 'assets/lib/axios/axios' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [ 'layoutist-vue' ],
                '0.18.0',
                true
            );

            wp_register_script(
                'layoutist-vue-masonry',
                $this->plugin_url() . 'assets/lib/vue-masonry/vue-masonry' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [ 'layoutist-vue' ],
                '1.0.3',
                true
            );

            wp_register_script(
                'layoutist-vue-infinite-loading',
                $this->plugin_url() . 'assets/lib/vue-infinite-loading/vue-infinite-loading' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [ 'layoutist-vue' ],
                '2.4.3',
                true
            );

            wp_register_script(
                'layoutist-vue-clazy-load',
                $this->plugin_url() . 'assets/lib/vue-clazy-load/vue-clazy-load' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [ 'layoutist-vue' ],
                '0.4.2',
                true
            );
        }

        /**
         * Enqueues admin scripts.
         *
         * @since 1.0.0
         */
        public function enqueue_admin_scripts() {

            if ( 'toplevel_page_layoutist' !== get_current_screen()->id ) {
                return;
            }

            wp_enqueue_script(
                'layoutist-admin',
                $this->plugin_url() . 'assets/js/script' . ( SCRIPT_DEBUG ? '.js' : '.min.js' ),
                [
                    'layoutist-vue',
                    'layoutist-axios',
                    'layoutist-vue-masonry',
                    'layoutist-vue-infinite-loading',
                    'layoutist-vue-clazy-load',
                    'updates'
                ],
                $this->version(),
                true
            );

            wp_localize_script( 'layoutist-admin', 'layoutist',
                [
                    'nonce' => wp_create_nonce( 'layoutist' ),
                    'installed_plugins' => array_keys( get_plugins() ),
                    'active_plugins' =>  array_values( get_option( 'active_plugins' ) ),
                    'elementor_save_templates_url' => class_exists( 'Elementor\TemplateLibrary\Source_Local' ) ? Source_Local::get_admin_url() : '',
                    'elementor_pro_url' => 'http://bit.ly/2ILhKwY',
                ]
            );

            wp_enqueue_style(
                'layoutist-admin',
                $this->plugin_url() . 'assets/css/style' . ( SCRIPT_DEBUG ? '.css' : '.min.css' ),
                [],
                $this->version()
            );
        }

        /**
         * Register admin menu.
         *
         * @since 1.0.0
         */
        public function register_admin_menu() {
            add_menu_page(
                esc_html__( 'Layoutist', 'layoutist' ),
                esc_html__( 'Layoutist', 'layoutist' ),
                'manage_options',
                'layoutist',
                [ $this, 'register_admin_menu_callback' ],
                'dashicons-tide',
                '58.9'
            );
        }

        /**
         * Register admin menu callback.
         *
         * @since 1.0.0
         */
        public function register_admin_menu_callback() {
            ?>
            <div id="layoutist" class="layoutist">
                <div class="wp-filter">
                    <span class="layoutist-brand"><?php esc_html_e( 'Layoutist' ); ?></span>
                    <ul class="filter-links">
                        <li>
                            <a href="#" class="current">
                                <img src="<?php echo $this->plugin_url() . 'assets/img/elementor.svg'; ?>" title="<?php esc_html_e( 'Elementor', 'layoutist' ); ?>">
                            </a>
                        </li>
                        <!-- <li><a href="#"><img src="<?php echo $this->plugin_url() . 'assets/img/gutenberg.jpg'; ?>" title="<?php esc_html_e( 'Elementor', 'layoutist' ); ?>"></a></li> -->
                    </ul>
                </div>
                <div class="theme-browser rendered">
                    <div class="themes wp-clearfix">
                        <masonry :cols="{default: 4, 1400: 3, 1024: 2, 500: 1}" :gutter="20">
                            <layoutist-template
                                v-for="template in templates"
                                :key="template.id"
                                :data="template"
                                @preview="togglePreview">
                            </layoutist-template>
                        </masonry>
                    </div>
                </div>

                <infinite-loading @infinite="infiniteHandler">
                    <div slot="no-more"><?php esc_html_e( 'No more templates, more coming soon :)', 'layoutist' ); ?></div>
                </infinite-loading>

                <div ref="previewOverlay"
                  class="theme-install-overlay wp-full-overlay iframe-ready"
                  :class="[expanded ? 'expanded' : 'preview-only collapsed', deviceClass]"
                  style="display:none">
                    <div class="wp-full-overlay-sidebar">
                        <div class="wp-full-overlay-header">
                            <button @click="togglePreview" class="close-full-overlay"><span class="screen-reader-text">Close</span></button>
                        </div>
                        <div class="wp-full-overlay-sidebar-content">
                            <div class="install-theme-info">
                                <h3 class="theme-name" v-html="template.title"></h3>
                                <img class="theme-screenshot" :src="template.image" alt="">
                                <div class="theme-details">
                                    <h4><?php esc_html_e( 'Required Plugins', 'layoutist' ); ?></h4>
                                    <ul>
                                        <li v-for="plugin in template.plugins">{{ plugin.name }}
                                            <button v-show="!isPluginInstalled(plugin.slug) && !isPluginPremium(plugin.slug)" class="button button-primary" @click="installPlugin(plugin.slug)"><?php esc_html_e( 'Install', 'layoutist' ); ?></button>
                                            <button v-show="isPluginInstalled(plugin.slug) && !isPluginActive(plugin.slug)" class="button button-primary" @click="activatePlugin(plugin.slug)"><?php esc_html_e( 'Activate', 'layoutist' ); ?></button>
                                            <button v-show="!isPluginInstalled(plugin.slug) && !isPluginActive(plugin.slug) && isPluginPremium(plugin.slug)" class="button button-primary" @click="purchasePlugin(plugin.slug)"><?php esc_html_e( 'Purchase', 'layoutist' ); ?></button>
                                            <button v-show="isPluginActive(plugin.slug)" class="button button-disabled"><?php esc_html_e( 'Active', 'layoutist' ); ?></button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div id="customize-footer-actions" class="wp-full-overlay-footer">
                            <div class="layoutist-import-action">
                                <button type="button" class="button button-primary button-hero" :class="{'button-disabled' : !importActive}" @click="importTemplate(template.id)">Import</button>
                            </div>
                            <span class="layoutist-import-feedback"></span>
                            <button @click="expanded =! expanded" type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="Hide Controls">
                                <span class="collapse-sidebar-arrow"></span>
                                <span class="collapse-sidebar-label">Hide Controls</span>
                            </button>
                            <div class="devices-wrapper">
                                <div class="devices">
                                    <button
                                      type="button"
                                      class="preview-desktop"
                                      aria-pressed="true"
                                      data-device="desktop"
                                      @click="deviceClass = 'preview-desktop'"
                                      :class="[deviceClass == 'preview-desktop' ? 'active' : '']">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Enter desktop preview mode', 'layoutist' ); ?></span>
                                    </button>
                                        <button
                                        type="button"
                                        class="preview-tablet"
                                        aria-pressed="false"
                                        data-device="tablet"
                                        @click="deviceClass = 'preview-tablet'"
                                        :class="[deviceClass == 'preview-tablet' ? 'active' : '']">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Enter tablet preview mode', 'layoutist' ); ?></span>
                                    </button>
                                        <button type="button"
                                        class="preview-mobile"
                                        aria-pressed="false"
                                        data-device="mobile"
                                        @click="deviceClass = 'preview-mobile'"
                                        :class="[deviceClass == 'preview-mobile' ? 'active' : '']">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Enter mobile preview mode', 'layoutist' ); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wp-full-overlay-main">
                        <iframe :src="template.url" title="Preview"></iframe>
                    </div>
                </div>
            </div>

            <script type="text/x-template" id="tmpl-layoutist-template">
                <div class="theme" @click="showPreview">
                    <div class="theme-screenshot">
                        <clazy-load :src="data.image">
                            <img :src="data.image">
                        </clazy-load>
                    </div>
                    <div class="theme-id-container">
                        <h2 class="theme-name" v-html="data.title"></h2>
                        <div class="theme-actions">
                            <button class="button preview"><?php esc_html_e( 'Preview & Import', 'layoutist' ); ?></button>
                        </div>
                    </div>
                </div>
            </script>

            <?php
        }

        /**
         * Handle ajax requests.
         *
         * @since 1.0.0
         */
        public function ajax_handler() {
            $nonce = filter_var( $_POST['nonce'], FILTER_SANITIZE_STRING );
            $type = filter_var( $_POST['type'], FILTER_SANITIZE_STRING );

            if ( ! wp_verify_nonce( $nonce, 'layoutist' ) ) {
                wp_send_json_error( esc_html__( 'Security check failed.', 'layoutist' ) );
            }

            if ( empty( $type ) ) {
                wp_send_json_error( esc_html__( 'Type is missing.', 'layoutist' ) );
            }

            $this->$type();
        }

        /**
         * Import a template.
         *
         * @since 1.0.0
         */
        private function import_template() {
            $template_id = filter_var( $_POST[ 'template_id' ], FILTER_SANITIZE_NUMBER_INT );

            // Template ID is missing.
            if ( empty( $template_id ) ) {
                wp_send_json_error( esc_html__( 'Template ID is missing.', 'layoutist' ) );
            }

            require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );

            $template_file = download_url( 'https://technippy.com/layoutist/wp-json/layoutist/v1/templates/' . $template_id );

            // Problem in downloading the template.
            if ( is_wp_error( $template_file ) ) {
                wp_send_json_error( $template_file );
            }

            $source = Plugin::$instance->templates_manager->get_source( 'local' );
            $imported_template = $source->import_template( basename( $template_file ), $template_file );

            // Problem in importing the template.
            if ( is_wp_error( $imported_template ) ) {
                wp_send_json_error( $imported_template );
            }

            @unlink( $template_file );

            wp_send_json_success( $imported_template );
        }

        /**
         * Activate a plugin.
         *
         * @since 1.0.0
         */
        private function activate_plugin() {
            $plugin = filter_var( $_POST[ 'plugin' ], FILTER_SANITIZE_STRING );

            $result = activate_plugin( $plugin, '', false, true );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result );
            }

            wp_send_json_success( esc_html__( 'Plugin activated.', 'layoutist' ) );
        }

        /**
         * Returns the version number of the plugin.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function version() {
            return self::$version;
        }

        /**
         * Returns the plugin basename.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_basename() {
            return self::$plugin_basename;
        }

        /**
         * Returns the plugin name.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_name() {
            return self::$plugin_name;
        }

        /**
         * Returns the plugin directory.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_dir() {

            /**
             * Filter the plugin directory.
             *
             * @since 1.0.0
             *
             * @param string $plugin_dir
             */
            $plugin_dir = apply_filters( 'layoutist_plugin_dir', self::$plugin_dir );

            return $plugin_dir;
        }

        /**
         * Returns the plugin URL.
         *
         * @since 1.0.0
         *
         * @return string
         */
        public function plugin_url() {

            /**
             * Filter the plugin URL.
             *
             * @since 1.0.0
             *
             * @param string $plugin_url
             */
            $plugin_url = apply_filters( 'layoutist_plugin_url', self::$plugin_url );

            return $plugin_url;
        }

        /**
         * Loads all PHP files in a given directory.
         *
         * @since 1.0.0
         *
         * @param string $directory_name
         */
        public function load_directory( $directory_name ) {
            $path = trailingslashit( $this->plugin_dir() . 'includes/' . $directory_name );
            $file_names = glob( $path . '*.php' );
            foreach ( $file_names as $filename ) {
                if ( file_exists( $filename ) ) {
                    require_once $filename;
                }
            }
        }

        /**
         * Loads specified PHP files from the plugin includes directory.
         *
         * @since 1.0.0
         *
         * @param array $file_names The names of the files to be loaded in the includes directory.
         */
        public function load_files( $file_names = array() ) {
            foreach ( $file_names as $file_name ) {
                if ( file_exists( $path = $this->plugin_dir() . 'includes/' . $file_name . '.php' ) ) {
                    require_once $path;
                }
            }
        }
    }
}

/**
 * Returns the Layoutist application instance.
 *
 * @since 1.0.0
 *
 * @return Layoutist
 */
function layoutist() {
	return Layoutist::get_instance();
}

/**
 * Initializes the Layoutist application.
 *
 * @since 1.0.0
 */
layoutist();
