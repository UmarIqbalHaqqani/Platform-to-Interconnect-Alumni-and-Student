<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CBXPollapi
 * @subpackage CBXPoll/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXPoll
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Cbxpoll_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->version     = CBX_POLL_PLUGIN_VERSION;
        $this->plugin_name = CBX_POLL_PLUGIN_NAME;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Cbxpoll_Loader. Orchestrates the hooks of the plugin.
     * - Cbxpoll_i18n. Defines internationalization functionality.
     * - Cbxpoll_Admin. Defines all hooks for the admin area.
     * - Cbxpoll_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-i18n.php';

        /**
         * This class responsible for plugin setting
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpolll-settings.php';

        /**
         * This class responsible for plugin session
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-session.php';

        /**
         * This class responsible for help methods
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-helper.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'admin/class-cbxpoll-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'public/class-cbxpoll-public.php';

        /**
         * These class responsible for email related helpers
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/Html2Text.php';
        require_once plugin_dir_path(dirname(__FILE__)).'includes/Html2TextException.php';
        require_once plugin_dir_path(dirname(__FILE__)).'includes/emogrifier.php';
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-emailtemplate.php';
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-cbxpoll-mailhelper.php';


        /**
         * This class resposible for single poll display widget
         */
        require_once plugin_dir_path(dirname(__FILE__)).'widgets/classic-widgets/cbxpollsingle-widget/cbxpollsingle-widget.php';


        $this->loader = new Cbxpoll_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Cbxpoll_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Cbxpoll_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
	    global $wp_version;

        $plugin_admin = new CBXPoll_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('upgrader_post_install', $plugin_admin, 'upgrader_post_install', 0, 3);
        $this->loader->add_action('admin_init', $plugin_admin, 'plugin_fullreset', 0);

        // init cookie and custom post types
        $this->loader->add_action('init', $plugin_admin, 'init_cbxpoll_type');


        //add js and css in admin end
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        //on admin init setting init and cbxpoll type post tdelete hook
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');

        // add global settings menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu', 11);

        // add custom status column in table
        $this->loader->add_filter('manage_edit-cbxpoll_columns', $plugin_admin, 'add_new_poll_columns');
        $this->loader->add_action('manage_cbxpoll_posts_custom_column', $plugin_admin, 'manage_poll_columns', 10, 2);
        $this->loader->add_filter('manage_edit-cbxpoll_sortable_columns', $plugin_admin, 'cbxpoll_columnsort');


        // add meta box and hook save meta box
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'metaboxes_display');
        $this->loader->add_action('save_post', $plugin_admin, 'metabox_save');

        $this->loader->add_action("wp_ajax_cbxpoll_get_answer_template", $plugin_admin, 'get_answer_template');


        //on user delete
        $this->loader->add_action('delete_user', $plugin_admin, 'on_user_delete_vote_delete');

        //add plugin row meta and actions links
        $this->loader->add_filter('plugin_action_links_'.CBX_POLL_PLUGIN_BASE_NAME, $plugin_admin,
            'plugin_listing_setting_link');
        $this->loader->add_filter('plugin_row_meta', $plugin_admin, 'custom_plugin_row_meta', 10, 2);

        $this->loader->add_action('upgrader_process_complete', $plugin_admin, 'plugin_upgrader_process_complete', 10,
            2);
        $this->loader->add_action('admin_notices', $plugin_admin, 'plugin_activate_upgrade_notices');

        //gutenberg
        $this->loader->add_action('init', $plugin_admin, 'gutenberg_blocks');
	    if ( version_compare($wp_version,'5.8') >= 0) {
		    $this->loader->add_filter('block_categories_all', $plugin_admin, 'gutenberg_block_categories', 10, 2);
	    }
	    else{
		    $this->loader->add_filter('block_categories', $plugin_admin, 'gutenberg_block_categories', 10, 2);
	    }

        $this->loader->add_action('enqueue_block_editor_assets', $plugin_admin, 'enqueue_block_editor_assets');

        //update manager
        $this->loader->add_filter('pre_set_site_transient_update_plugins', $plugin_admin, 'pre_set_site_transient_update_plugins_pro_addon');
        $this->loader->add_action( 'in_plugin_update_message-' . 'cbxpollproaddon/cbxpollproaddon.php', $plugin_admin, 'plugin_update_message_pro_addons' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new CBXPoll_Public($this->get_plugin_name(), $this->get_version());

        // init cookie
        $this->loader->add_action('template_redirect', $plugin_public, 'init_cookie');
        //$this->loader->add_action( 'init', $plugin_public, 'init_cookie' ); //need to check

        //poll display method 'text' hook
        $this->loader->add_filter('cbxpoll_display_options', $plugin_public, 'poll_display_methods_text');

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        //adding shortcode

        $this->loader->add_action('init', $plugin_public, 'init_shortcodes');


        //Show poll in details poll post type
        if (!is_admin()) {
            $this->loader->add_filter('the_content', $plugin_public, 'cbxpoll_the_content');
            $this->loader->add_filter('the_excerpt', $plugin_public, 'cbxpoll_the_excerpt');
        }

        // ajax for voting
        $this->loader->add_action("wp_ajax_cbxpoll_user_vote", $plugin_public, "ajax_vote");
        $this->loader->add_action("wp_ajax_nopriv_cbxpoll_user_vote", $plugin_public, "ajax_vote");

        // ajax for read more page
        $this->loader->add_action("wp_ajax_cbxpoll_list_pagination", $plugin_public, "ajax_poll_list");
        $this->loader->add_action("wp_ajax_nopriv_cbxpoll_list_pagination", $plugin_public, "ajax_poll_list");

        $this->loader->add_action('widgets_init', $plugin_public, 'init_widgets');

        //elementor
        $this->loader->add_action('elementor/widgets/widgets_registered', $plugin_public, 'init_elementor_widgets');
        $this->loader->add_action('elementor/elements/categories_registered', $plugin_public,
            'add_elementor_widget_categories');
        $this->loader->add_action('elementor/editor/before_enqueue_scripts', $plugin_public, 'elementor_icon_loader',
            99999);

        //wpbakery
        $this->loader->add_action('vc_before_init', $plugin_public, 'vc_before_init_actions', 12);

        //$this->loader->add_action('admin_init', $plugin_public,  'admin_init_ajax_lang');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Cbxpoll_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

}//end method CBXPoll
