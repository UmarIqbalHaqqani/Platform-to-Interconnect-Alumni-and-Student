<?php
//TODO: check if free (main) plugin is installed, generate installation error otherwise.

/**
 * WP Latest Posts Add-on main class
 */
class WPLPAddonAdmin
{
    /**
     * Init version params
     *
     * @var string
     */
    public $version;
    /**
     * Translation domain
     *
     * @var string
     */
    public $tdomain;
    /**
     * Plugin file
     *
     * @var string
     */
    public $plugin_file;
    /**
     * Plugin directory
     *
     * @var string
     */
    public $plugin_dir;

    /**
     * Constructor
     *
     * @param array $opts Options of widget
     */
    public function __construct($opts)
    {
        $this->version     = $opts['version'];
        $this->tdomain     = $opts['translation_domain'];
        $this->plugin_file = $opts['plugin_file'];
        $this->plugin_dir  = dirname(plugin_basename($this->plugin_file));

        //load language
        load_plugin_textdomain(
            $this->tdomain,
            WP_PLUGIN_URL . '/' . $this->plugin_dir . '/languages',
            $this->plugin_dir . '/languages'
        );

        add_theme_support(
            'post-formats',
            array('aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat')
        );

        if (is_admin()) {
            /**
             * Our own filter and actions to plug the pro features into the free plugin
             **/
            add_filter('wplp_themedirs', array($this, 'themeDirsFilter'));

            /**
             * Load calendar ui
             **/
            add_action('admin_enqueue_scripts', array($this, 'loadAdminScripts'));
            add_action('wp_ajax_change_custompost_multisite', array($this, 'changeCustompostMultisite'));
            add_action('wp_enqueue_scripts', array($this, 'wplpLoadDashiconsFrontEnd'));
            add_filter('wplp_src_category_args', array($this, 'wplpSrcCategoryArgsFilter'), 10, 2);
        } else {
            /**
             * Front-end display
             **/
            //add_filter( 'wplp_front_display', array( $this, 'displayFrontAdditionalSources' ), 10, 2 );
            add_filter('wplp_src_category_args', array($this, 'wplpSrcCategoryArgsFilter'), 10, 2);
            add_filter('wplp_plugindir', array($this, 'getPluginDir'));
            /**
             * Load ajax script front-end
             **/
            add_action('wp_head', array($this, 'pluginnameAjaxurl'));
            add_filter('wplp_load_inline_style', array($this, 'loadInlineStyle'), 10, 3);
        }
        add_action('wp_ajax_loadMoreElement', array($this, 'getMoreElement'));
        add_action('wp_ajax_nopriv_loadMoreElement', array($this, 'getMoreElement'));
    }

    /**
     * Enqueue dashicons style
     *
     * @return void
     */
    public function wplpLoadDashiconsFrontEnd()
    {
        wp_enqueue_style('dashicons');
    }

    /**
     * Define ajaxurl
     *
     * @return void
     */
    public function pluginnameAjaxurl()
    {
        ?>
        <script type="text/javascript">
            var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        </script>
        <?php
    }

    /**
     * Display more element with ajax
     *
     * @return void
     */
    public function getMoreElement()
    {
        check_ajax_referer('wplp-addon-front-nonce', 'ajaxnonce');

        if (isset($_POST['loaded_ids'])) {
            $loaded_ids = $_POST['loaded_ids'];
        }
        if (isset($_POST['widget_id'])) {
            $widget_id = $_POST['widget_id'];
        }
        if (isset($_POST['theme_class'])) {
            $theme_class = $_POST['theme_class'];
        }
        // $themeclass = ' ' . $theme_class[1];
        $themeclass = ' ' . $theme_class;

        $widget = get_post($widget_id);
        $html   = '';

        if (isset($widget) && !empty($widget)) {
            $widget->settings = get_post_meta($widget->ID, '_wplp_settings', true);
            //if Number of posts to skip turn on
            $off_set                     = (int) $widget->settings['off_set'];
            $load_off_set                = $off_set + count($loaded_ids);
            $widget->settings['off_set'] = (string) $load_off_set;
            $widget->settings['load_more_ajax'] = 1;
            $widget->settings['loaded_ids'] = $_POST['loaded_ids'];
            $front = new WPLPFront($widget);
            $html  = $front->displayByAjax(false, $themeclass);
        }

        echo wp_json_encode(array('status' => 'success', 'data' => $html));
        die();
    }

    /**
     * Loads js/ajax scripts for admin back-office
     *
     * @param string $hook Post name to hook
     *
     * @return mixed
     */
    public function loadAdminScripts($hook)
    {
        /**
         * Only load on post edit admin page
         **/
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return $hook;
        }

        if (CUSTOM_POST_NEWS_WIDGET_NAME !== get_post_type()) {
            return $hook;
        }
    }


    /**
     * Adds list of pro theme directories
     * Complete list of theme dirs merges with pro
     *
     * @param array $idirs Free plugin list of theme dirs
     *
     * @return array
     *
     * @internal
     */
    public function themeDirsFilter($idirs = array())
    {

        $theme_root = dirname(dirname(__FILE__)) . '/themes';
        //echo 'pro theme dir: ' . $theme_root . '<br/>';   //Debug
        $dirs = scandir($theme_root);
        foreach ($dirs as $k => $v) {
            if (!is_dir($theme_root . '/' . $v) || $v[0] === '.' || $v === 'CVS') {
                unset($dirs[$k]);
            } else {
                $dirs[$k] = array(
                    'path' => $theme_root . '/' . $v,
                    'url'  => plugins_url('themes/' . $v, dirname(__FILE__))
                );
            }
        }

        $idirs = array_merge($idirs, $dirs);

        return (array) $idirs;
    }

    /**
     * Handle the post_type parameter given in get_terms function
     *
     * @param array $clauses  Causes parameter
     * @param array $taxonomy Term parameter
     * @param array $args     Argument parameter
     *
     * @return mixed
     */
    public function wplpTermsClauses($clauses, $taxonomy, $args)
    {
        if (!empty($args['post_type'])) {
            global $wpdb;

            $post_types = array();

            foreach ($args['post_type'] as $cpt) {
                $post_types[] = "'" . $cpt . "'";
            }

            if (!empty($post_types)) {
                $clauses['fields']  = 'DISTINCT ' .
                                      str_replace(
                                          'tt.*',
                                          'tt.term_taxonomy_id, tt.term_id, tt.taxonomy, tt.description, tt.parent',
                                          $clauses['fields']
                                      );
                $clauses['fields']  .= ', COUNT(t.term_id) AS count';
                $clauses['join']    .= ' INNER JOIN ' . $wpdb->term_relationships;
                $clauses['join']    .= ' AS r ON r.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN ';
                $clauses['join']    .= $wpdb->posts . ' AS p ON p.ID = r.object_id';
                $clauses['where']   .= ' AND p.post_type IN (' . implode(',', $post_types) . ')';
                $clauses['orderby'] = 'GROUP BY t.term_id ' . $clauses['orderby'];
            }
        }
        return $clauses;
    }


    /**
     * Change custom post from multisite by ajax
     *
     * @return void
     */
    public function changeCustompostMultisite()
    {
        check_ajax_referer('wplp-addon-admin-nonce', 'ajaxnonce');
        $blog_id = '';
        if (isset($_POST['val_blog'])) {
            $blog_id = $_POST['val_blog'];
        }
        $html     = '';
        $args     = array(
            'public'   => true,
            '_builtin' => false
        );
        $output   = 'names'; // names or objects, note names is the default
        $operator = 'and';

        if ('all_blog' === $blog_id) {
            $blogs = get_sites();
            foreach ($blogs as $blog) {
                switch_to_blog((int) $blog->blog_id);
                $allcats = get_post_types($args, $output, $operator);
                foreach ($allcats as $allcat) {
                    $custom_post_types[$allcat] = (int) $blog->blog_id;
                }
                restore_current_blog();
            }
        } else {
            switch_to_blog((int) $blog_id);
            $allcats = get_post_types($args, $output, $operator);
            foreach ($allcats as $allcat) {
                $custom_post_types[$allcat] = (int) $blog_id;
            }
            restore_current_blog();
        }
        $html .= '<ul><li class="field"> 
            <div class="width33 input-field input-select">
			<label for="custom_post_select" class="custompost_cb settings-wrapper-title">' .
                 __('Choose a custom post type', 'wp-latest-posts-addon') .
                 ' : </label>		
			<select id="custom_post_select" name="wplp_custom_post_type" class="wplp-short-text wplp-font-style width-30 wplp_change_content">
				<option value="">' . esc_html__('Choose a custom post type', 'wp-latest-posts-addon') . '</option>';
        if (!empty($custom_post_types)) {
            foreach ($custom_post_types as $val => $custom_post_type) {
                $html .= '<option value="' . $custom_post_type . '_' . $val . '" > ' . $val . ' </option>';
            }
        }
        $html .= '</select></div></li></ul>';

        echo json_encode($html);
        exit;
    }


    /**
     * Get last date with y m d format
     *
     * @param array $settings List of settings
     *
     * @return false|integer
     */
    public function getLastDatetime($settings)
    {
        $subtime = '';
        if (isset($settings['last_years']) && $settings['last_years'] > 0) {
            $subtime .= ' ' . $settings['last_years'] . ' years';
        }
        if (isset($settings['last_months']) && $settings['last_months'] > 0) {
            $subtime .= ' ' . $settings['last_months'] . ' months';
        }
        if (isset($settings['last_days']) && $settings['last_days'] > 0) {
            $subtime .= ' ' . $settings['last_days'] . ' days';
        }
        if (isset($settings['last_hours']) && $settings['last_hours'] > 0) {
            $subtime .= ' ' . $settings['last_hours'] . ' hours';
        }

        $date = strtotime(date('Y-m-d H:i:s') . ' - ' . $subtime);
        //echo(date('Y-m-d H:i:s', $date));
        return $date;
    }

    /**
     * Displays additional source types modes on the front-end
     * WP Filter
     *
     * @param string $html   Html code
     * @param object $widget Widget settings
     *
     * @return string
     */
    public function displayFrontAdditionalSources($html, $widget)
    {

        if ('src_tags' === $widget->settings['source_type']) {
            $html .= '<p>TODO: list posts by tags</p>';
            //TODO
        }
        return $html;
    }

    /**
     * Filters front-end list display query
     * to add additional pro feature arguments
     *
     * @param array $args   Argument parameter
     * @param array $widget Block details
     *
     * @return mixed
     */
    public function wplpSrcCategoryArgsFilter($args, $widget)
    {
        $settings = $widget->settings;
        if (empty($settings['source_date_min'])
            && empty($settings['last_hours'])
            && empty($settings['last_days'])
            && empty($settings['last_months'])
            && empty($settings['last_years'])
        ) {
            return $args;
        }
        if ((!empty($settings['last_hours'])
             || !empty($settings['last_days'])
             || !empty($settings['last_months'])
             || !empty($settings['last_years']))
            && empty($settings['source_date_min'])
        ) {
            // Show articles from the last
            $args['date_query'] = array(
                array(
                    'after' => date('Y-m-d H:i:s', $this->getLastDatetime($settings))
                )
            );
        } else {
            if ($settings['source_date_min_switch'] === 'between') {
                $min = $settings['source_date_min'];
                $max = $settings['source_date_max'];
                if (strtotime($min) < strtotime($max)) {
                    $args['date_query'] = array(
                        array(
                            'after'     => $min,
                            'before'    => $max,
                            'inclusive' => true
                        )
                    );
                } else {
                    $args['date_query'] = array(
                        array(
                            'after'     => $max,
                            'before'    => $min,
                            'inclusive' => true
                        )
                    );
                }
            } else {
                $args['date_query'] = array(
                    array(
                        $settings['source_date_min_switch'] => $settings['source_date_min']
                    )
                );
            }
        }

        return $args;
    }

    /**
     * Get directory of plugin
     *
     * @return string
     */
    public function getPluginDir()
    {
        $theme_root = dirname(dirname(__FILE__)) . '/themes';
        return $theme_root;
    }

    /**
     * Load inline style
     *
     * @param array   $settings  List of settings
     * @param string  $theme_dir Directory of theme
     * @param integer $idWidget  Id of widget
     *
     * @return void
     */
    public function loadInlineStyle($settings, $theme_dir, $idWidget)
    {
        $colorTheme          = (isset($settings['defaultColor']) ? $settings['defaultColor'] : '');
        if ($settings['theme'] === 'smooth-effect' || $settings['theme'] === 'masonry-category') {
            $settings['overlay_background'] = '0.7';
        }
        if ($settings['theme'] === 'material-vertical' || $settings['theme'] === 'material-horizontal') {
            $overlay_background          = (isset($settings['overlay_background']) ? $settings['overlay_background'] : '0.2');
            $color               = $this->hex2rgba((isset($settings['colorpicker']) ? $settings['colorpicker'] : ''), $overlay_background);
        } else {
            $overlay_background          = (isset($settings['overlay_background']) ? $settings['overlay_background'] : '0.7');
            $color               = $this->hex2rgba((isset($settings['colorpicker']) ? $settings['colorpicker'] : ''), $overlay_background);
        }

        $icon_color = (isset($settings['addon_icon_color']) ? $settings['addon_icon_color'] : '#ffffff');
        $bg_icon_color = (isset($settings['addon_bg_icon_color']) ? $settings['addon_bg_icon_color'] : 'transparent');

        if (isset($settings['colorpicker']) && $settings['colorpicker'] !== 'transparent') {
            $colorfull           = $this->hex2rgba((isset($settings['colorpicker']) ? $settings['colorpicker'] : ''), 1);
        } else {
            $colorfull = 'transparent';
        }

        $nbcol               = $settings['amount_cols'];
        $theme_classDashicon = ' ' . basename($settings['theme']);
        if (($theme_classDashicon !== ' default')) {
            $hanlde = 'themes-wplp-' . $settings['theme'];
            wp_enqueue_style($hanlde, plugins_url('wp-latest-posts-addon/themes/') . $theme_dir . '/style.css', array('myStyleSheets'), WPLP_ADDON_VERSION);
            $css            = '';
            $widthtotal     = 100;
            $width1         = $widthtotal / $nbcol;
            $width2         = $widthtotal;
            $margin_element = 10;
            if ($theme_classDashicon === ' material-vertical') {
                $margin_element = 20;
            }

            $gui            = ($margin_element * ($nbcol - 1));

            if ($theme_classDashicon === ' masonry-category') {
                $css .= '#wplp_widget_' . $idWidget .
                        ' .wplp_listposts li .img_cropper:after{color: '. $icon_color .';background: center center no-repeat ' . $color . '} ';
                $css .= '#wplp_widget_' . $idWidget .
                        ' .masonry-category .wpcu-front-box.bottom .category:before{background:' . $color . '}';

                if (isset($settings['force_icon']) && (int) $settings['force_icon'] === 1) {
                    if (isset($settings['dashicons_selector'])) {
                        if ($settings['dashicons_selector'] === '') {
                            $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                            $css .= 'content: none ;';
                            $css .= '}';
                        } else {
                            $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                            $css .= "content:'\\" . $settings['dashicons_selector'] . "';";
                            $css .= '}';
                        }
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background-color: '. $color .';';
                        $css .= '}';
                    }

                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                    $css .= 'background-color: '. $bg_icon_color .';';
                    $css .= 'color: '. $icon_color .';';
                    $css .= '}';
                } else {
                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                    $css .= 'content: none ;';
                    $css .= '}';
                }



                $css .= '#wplp_widget_' . $idWidget .
                        ' .read-more{border-top:1px solid ' . $color . '}';
                $css .= '#wplp_widget_' . $idWidget .
                        ' .wplp_listposts li{ width: calc((' . $widthtotal . '% - ' . $gui . 'px)/' . $nbcol . ');}';
                $css .= '@media screen and (max-width: 640px) {#wplp_widget_' . $idWidget .
                        ' .wplp_listposts li {width: calc(' . $width2 . '% - ' . (2 * $margin_element) . 'px) !important; }}';
            } elseif ($theme_classDashicon === ' masonry') {
                wp_enqueue_style(
                    'wpmf-settings-google-icon',
                    plugins_url('wp-latest-posts-addon/themes/') . $theme_dir . '/fonts/material-icon.css',
                    array(),
                    WPLP_ADDON_VERSION
                );

                if (isset($settings['force_icon']) && (int) $settings['force_icon'] === 1) {
                    if (isset($settings['material_icon_selector']) && $settings['material_icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:after {';
                        $css .= "content:'\\". $settings['material_icon_selector'] ."';";
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background-color: '. $color .';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:after {';
                        $css .= 'background-color: '. $bg_icon_color .';';
                        $css .= 'color: '. $icon_color .';';
                        $css .= '}';
                    } elseif (isset($settings['icon_selector']) && $settings['icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background: url(' .
                            $settings['icon_selector'] . ') center no-repeat '. $color .';';
                        $css .= '}';
                    } else {
                        $img_dir = plugins_url('wp-latest-posts-addon') . '/themes/masonry/img/overimage.png';
                        $css     .= '#wplp_widget_' . $idWidget .
                            " .wplp_listposts li .img_cropper:before{background: url('" .
                            $img_dir . "') center center no-repeat " . $color . ';}';
                    }
                } else {
                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                    $css .= 'background: '. $color .';';
                    $css .= '}';
                }

                $css     .= '#wplp_widget_' . $idWidget .
                    ' .read-more{border-top:1px solid ' . $color . ';}';

                $css     .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li{
                width: calc((' . $widthtotal . '% - ' . $gui . 'px)/' . $nbcol . ');}';
                $css     .= '@media screen and (max-width: 640px) {#wplp_widget_' . $idWidget .
                            ' .wplp_listposts li {width: calc(' . $width2 . '% - ' . (2 * $margin_element) . 'px) !important;}}';
            } elseif ($theme_classDashicon === ' material-vertical') {
                if (isset($settings['force_icon']) && (int) $settings['force_icon'] === 1) {
                    if (isset($settings['material_icon_selector']) && $settings['material_icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background-color: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:hover:before {';
                        $css .= "content:'\\" . $settings['material_icon_selector'] . "';";
                        $css .= 'background-color: ' . $bg_icon_color . ';';
                        $css .= 'color: ' . $icon_color . ';';
                        $css .= '}';
                    } elseif (isset($settings['icon_selector']) && $settings['icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:hover:before {';
                        $css .= "opacity: 1; background: url('" .
                            $settings['icon_selector'] . "')  center no-repeat;";
                        $css .= 'background-size: cover;';
                        $css .= '}';
                    } else {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                        $css .= 'background-color: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:hover:before {';
                        $css .= 'background-color: transparent;';
                        $css .= '}';
                    }
                } else {
                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:before {';
                    $css .= 'background-color: '. $color .';';
                    $css .= '}';

                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li .img_cropper:hover:before {';
                    $css .= 'background-color: transparent;';
                    $css .= '}';
                }

                $css     .= '#wplp_widget_' . $idWidget . ' .wplp_listposts li{
                width: calc((' . $widthtotal . '% - ' . $gui . 'px)/' . $nbcol . ');}';
                $css     .= '@media screen and (max-width: 640px) {#wplp_widget_' . $idWidget .
                    ' .wplp_listposts li {width: calc(' . $width2 . '% - ' . (2 * $margin_element) . 'px) !important;}}';
            } elseif ($theme_classDashicon === ' material-horizontal') {
                if (isset($settings['force_icon']) && (int) $settings['force_icon'] === 1) {
                    if (isset($settings['material_icon_selector']) && $settings['material_icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card .wplp-mh-post-image a:before {';
                        $css .= 'background-color: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card:hover .wplp-mh-post-image a:before {';
                        $css .= "content:'\\" . $settings['material_icon_selector'] . "';";
                        $css .= 'background-color: ' . $bg_icon_color . ';';
                        $css .= 'color: ' . $icon_color . ';';
                        $css .= '}';
                    } elseif (isset($settings['icon_selector']) && $settings['icon_selector'] !== '') {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card .wplp-mh-post-image a:before {';
                        $css .= 'background: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card:hover .wplp-mh-post-image a:before {';
                        $css .= "opacity: 1; background: url('" .
                            $settings['icon_selector'] . "')  center no-repeat;";
                        $css .= 'background-size: cover;';
                        $css .= '}';
                    } else {
                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card .wplp-mh-post-image a:before {';
                        $css .= 'background-color: ' . $color . ';';
                        $css .= '}';

                        $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card:hover .wplp-mh-post-image a:before {';
                        $css .= 'background-color: transparent;';
                        $css .= '}';
                    }
                } else {
                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card .wplp-mh-post-image a:before {';
                    $css .= 'background-color: '. $color .';';
                    $css .= '}';

                    $css .= '#wplp_widget_' . $idWidget . ' .wplp_listposts .wplp-mh-post-card:hover .wplp-mh-post-image a:before {';
                    $css .= 'background-color: transparent;';
                    $css .= '}';
                }
            } elseif ($theme_classDashicon === ' portfolio') {
                if ($colorTheme === 'yes') {
                    $css .= '#wplp_widget_' . $idWidget .
                            ' .wplp_listposts{background-color: ' . $colorfull . '}';
                } else {
                    $colorfull = 'transparent';
                }
                $css .= '#wplp_widget_' . $idWidget .
                        ' .portfolio .wpcu-front-box.bottom .category::before{ background:' . $color . ';}';
                $css .= '#wplp_widget_' . $idWidget . ' .read-more{border-top:1px solid ' . $color . ';}';
                $css .= '#wplp_widget_' . $idWidget .
                        ' .wplp_listposts li{margin: ' .
                        ($width1 / 20) . '%; width: ' . ($width1 - ($width1 * 2 / 20)) . '%;}';
                $css .= '@media screen and (max-width: 640px) {#wplp_widget_' . $idWidget .
                        ' .wplp_listposts li {width: ' . ($width2 - ($width1 * 2 / 20)) . '% !important;}}';
                $css .= '#wplp_widget_' . $idWidget .
                        ' .wplp_listposts {background: center center no-repeat ' . $colorfull .
                        ';background-color: ' . $colorfull . ';}';
            } elseif ($theme_classDashicon === ' smooth-effect') {
                $css .= '#wplp_widget_' . $idWidget .
                        ' li.smooth-effect:hover .wpcu-front-box a .title { border-top: 1px solid ' . $color . ';}';
                $css .= '#wplp_widget_' . $idWidget .
                        ' li.smooth-effect:hover .wpcu-front-box a .text { border-bottom: 1px solid ' . $color . ';}';
            } elseif ($theme_classDashicon === ' timeline') {
                if ($colorfull === 'rgba(255,255,255,1)') {
                    $innercolor = '#000';
                } else {
                    $innercolor = '#fff';
                }
                $css .= '#wplp_widget_' . $idWidget .
                        ' .wplp_listposts  .wpcu-front-box.top .img_cropper:before {color:' .
                        $innercolor . '; background:' . $colorfull . ';}';
            }
            wp_add_inline_style($hanlde, $css);
        }


        if ($theme_classDashicon === ' masonry-category' || $theme_classDashicon === ' timeline') {
            wp_enqueue_style('dashicons');
        }
    }

    /**
     * Change color to rgb format hex2rgba
     *
     * @param string  $color   Color style
     * @param boolean $opacity Opacity style
     *
     * @return string
     */
    private function hex2rgba($color, $opacity = false)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color)) {
            return $default;
        }
        //Sanitize $color if "#" is provided
        if ($color[0] === '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) === 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) === 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = 1.0;
            }
            $output = 'rgba(' . implode(',', $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(',', $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }
}
