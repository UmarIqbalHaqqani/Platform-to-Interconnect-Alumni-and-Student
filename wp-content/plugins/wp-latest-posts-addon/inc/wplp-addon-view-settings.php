<?php
//TODO: check if free (main) plugin is installed, generate installation error otherwise.

/**
 * WP Latest Posts Add-on main class
 */
class WPLPAddonViewAdmin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        if (is_admin()) {
            add_action('wplp_addon_contentsource_display_content_custom_posttype', array(
                $this,
                'contentsourceDisplayContentCustomPosttype'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_content_tags', array(
                $this,
                'contentsourceDisplayContentTags'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_content_pages', array(
                $this,
                'contentsourceDisplayContentPages'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_content_inclusion', array(
                $this,
                'contentsourceDisplayContentInclusion'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_article_date', array(
                $this,
                'contentsourceDisplayArticleDate'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_post_order_by', array(
                $this,
                'contentsourceDisplayArticleOrderBy'
            ), 10, 1);
            add_action('wplp_addon_contentsource_display_page_order_by', array(
                $this,
                'contentsourceDisplayPageOrderBy'
            ), 10, 1);
            add_action('wplp_addon_theme_display_background_color', array($this, 'themeDisplayBackgroundColor'), 10, 1);
            add_action('wplp_addon_theme_display_crop_option', array($this, 'themeDisplayCropOption'), 10, 1);
            add_action('wplp_addon_theme_display_open_newlink', array($this, 'themeDisplayOpenNewlink'), 10, 1);
            add_action('wplp_addon_theme_display_force_hover_icon', array($this, 'themeDisplayForceHoverIcon'), 10, 1);
            add_action('wplp_addon_theme_display_loadmore_button', array($this, 'themeDisplayLoadmoreButton'), 10, 1);
            add_action('wplp_addon_imagesource_display_crop_images', array(
                $this,
                'imagesourceDisplayCropImages'
            ), 10, 1);
            add_action('wplp_addon_advanced_display_readmore_text', array($this, 'advancedDisplayReadmoreText'), 10, 1);
            add_action('wplp_addon_advanced_display_default_image', array($this, 'advancedDisplayDefaultImage'), 10, 1);
            add_action('wplp_addon_configuration_display_about', array($this, 'configurationDisplayAbout'), 10, 1);
            add_action('wplp_addon_theme_display_icon_selector', array($this, 'themeDisplayIconSelector'), 10, 1);
            add_action('wplp_addon_theme_display_animation_tab', array($this, 'themeDisplayAnimationTab'), 10, 1);
            // AJAX
            add_action('wp_ajax_getTaxonomyWPLP', array($this, 'getTaxonomyWPLP'));
        }
    }

    /**
     * Display content for custom post type
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayContentCustomPosttype($settings)
    {
        wp_enqueue_script(
            'ajaxcustomposttype',
            plugins_url('js/ajaxcustomposttype.js', dirname(__FILE__)),
            array('jquery'),
            WPLP_ADDON_VERSION,
            true
        );
        $ajax_non = wp_create_nonce('wplp-addon-admin-nonce');
        wp_localize_script('ajaxcustomposttype', 'wpsolAddonAdminJS', array('ajaxnonce' => $ajax_non));

        if (!defined('WPLP_PLUGIN_DIR')) {
            define(WPLP_PLUGIN_DIR, WP_PLUGIN_DIR . '/wp-latest-posts');
        }

        $parameter = array(
            'plugin_dir' => WPLP_PLUGIN_DIR
        );

        wp_localize_script('ajaxcustomposttype', 'content_language_param', $parameter);

        $custom_post_language = '';
        if (isset($settings['content_language'])) {
            $custom_post_language = $settings['content_language'];
        }

        if (!isset($settings['custom_post_type'])
            || empty($settings['custom_post_type'])
            || !$settings['custom_post_type']
        ) {
            $settings['custom_post_type'] = '';
        }

        $source_custom_post_type_checked[$settings['custom_post_type']] = ' selected="selected"';


        echo '<div class="settings-wrapper">';
        echo '<input type="hidden" value="' . esc_html($custom_post_language) . '" id="custom_posttype_language" />';

        if (is_multisite()) {
            if (!isset($settings['mutilsite_custompost'])
                || empty($settings['mutilsite_custompost'])
                || !$settings['mutilsite_custompost']
            ) {
                $settings['mutilsite_custompost'] = '';
            }

            $all_blog_custom_posttypes = get_sites();

            $mutilsite_selected_custompost = '';
            if (isset($settings['mutilsite_custompost'])) {
                $mutilsite_selected_custompost = $settings['mutilsite_custompost'];
            }

            echo '<div class="multisite-select-field settings-wrapper-field">';
            echo '<label class="settings-wrapper-title">' . esc_html__('Multisite selection', 'wp-latest-posts-addon') . '</label>';
            echo '<select id="mutilsite_select_custompost" class="mutilsite_select_custompost wplp-font-style width-30" name="wplp_mutilsite_custompost">
                        <option value="all_blog">' . esc_html__('All blog', 'wp-latest-posts-addon') . '</option>';
            foreach ($all_blog_custom_posttypes as $val) {
                $detail = get_blog_details((int) $val->blog_id);
                $check  = ($settings['mutilsite_custompost'] === $val->blog_id) ? ' selected="selected"' : '';
                echo '<option value="' . esc_html($val->blog_id) . '" ' . esc_html($check) . '> ' . esc_html($detail->blogname) . ' </option>';
            }
            echo '</select>';
            echo '<input type="hidden" value="' . esc_html($mutilsite_selected_custompost) . '" id="selected_multisite_custompost_type" />';
            echo '</div>';
        }

        echo '<div class="custom-posttype-field settings-wrapper-field">';
        echo '<ul id="customposttypetab" class="fields">';
        echo '<li class="field" id="postSelector">';
        echo '<ul>';
        $args     = array(
            'public'   => true,
            '_builtin' => false
        );
        $output   = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        //$custom_post_types = get_post_types();
        echo '<li class="field "> 
              <label for="custom_post_select" class="custompost_cb settings-wrapper-title">' . esc_html__('Choose a custom post type', 'wp-latest-posts-addon') . ' : </label>      
              <select id="custom_post_select" name="wplp_custom_post_type" class="wplp-font-style width-30 wplp_change_content">
                <option value="">' . esc_html__('Choose a custom post type', 'wp-latest-posts-addon') . '</option>';
        if (is_multisite()) {
            if ('all_blog' === $settings['mutilsite_custompost']) {
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
                switch_to_blog((int) $settings['mutilsite_custompost']);
                $allcats = get_post_types($args, $output, $operator);
                foreach ($allcats as $allcat) {
                    $custom_post_types[$allcat] = (int) $settings['mutilsite_custompost'];
                }
                restore_current_blog();
            }
            if (!empty($custom_post_types)) {
                foreach ($custom_post_types as $val => $custom_post_type) {
                    $check = ($settings['custom_post_type'] === $custom_post_type . '_' . $val) ? ' selected="selected"' : '';
                    echo '<option value="' . esc_html($custom_post_type . '_' . $val) . '" ' . esc_html($check) . ' > ' . esc_html($val) . ' </option>';
                }
            }
        } else {
            $custom_post_types = get_post_types($args, $output, $operator);

            foreach ($custom_post_types as $custom_post_type => $val) {
                $check = ($settings['custom_post_type'] === $val) ? ' selected="selected"' : '';
                echo '<option value="' . esc_html($val) . '" ' . esc_html($check) . ' > ' . esc_html($val) . ' </option>';
            }
        }
        echo '</select>
              </li>
              </ul>';
        echo '</li>';
        //field

        $this->getTaxonomyWPLP(
            (isset($settings['custom_post_type']) ? $settings['custom_post_type'] : null),
            (isset($settings['custom_post_taxonomy']) ? $settings['custom_post_taxonomy'] : null),
            (isset($settings['custom_post_term']) ? $settings['custom_post_term'] : null),
            (isset($settings['content_language']) ? $settings['content_language'] : null)
        );

        if (isset($settings['cat_source_order'])) {
            $source_order_selected[$settings['cat_source_order']] = ' checked="checked"';
        }

        if (isset($settings['cat_source_asc'])) {
            $source_asc_selected[$settings['cat_source_asc']] = ' checked="checked"';
        }
        echo '</ul>';    //fields
        echo '<hr style="margin-top: 30px">';

        //custom fields
        if (is_plugin_active('advanced-custom-fields/acf.php')) {
            $post_groups = acf_get_field_groups(array('post_type' => $settings['custom_post_type']));
            //Advanced custom fields
            if (!empty($post_groups)) {
                WPLPAddonContentACF::displayAdvancedCustomFields($settings, $settings['custom_post_type']);
            } else {
                echo '<div class="advanced-custom-field settings-wrapper-field"><input type="hidden" name="wplp_advanced_custom_fields_custompost" value=""/></div>';
            }
        }

        echo '<div class="order-by-field settings-wrapper-field">
                <label class="settings-wrapper-title tooltip" title="'.esc_html__('Define the ordering for news (sort by)', 'wp-latest-posts-addon').'">' . esc_html__('Order by', 'wp-latest-posts-addon') . '</label>
                <ul class="un-craft">
                    <li><input type="radio" name="wplp_cat_source_order" id="cat_source_order1" value="date" class="ju-radiobox"
                            ' . (isset($source_order_selected['date']) ? esc_html($source_order_selected['date']) : '') . ' />
                        <label for="cat_source_order1" class="radio-label">' . esc_html__('Creation date', 'wp-latest-posts-addon') . '</label></li>
                    <li><input type="radio" name="wplp_cat_source_order" id="cat_source_order4" value="modified" class="ju-radiobox"
                            ' . (isset($source_order_selected['modified']) ? esc_html($source_order_selected['modified']) : '') . ' />
                        <label for="cat_source_order4" class="radio-label">' . esc_html__('Last updated', 'wp-latest-posts-addon') . '</label></li>
                    <li><input type="radio" name="wplp_cat_source_order" id="cat_source_order2" value="title" class="ju-radiobox"
                            ' . (isset($source_order_selected['title']) ? esc_html($source_order_selected['title']) : '') . ' />
                        <label for="cat_source_order2" class="radio-label">' . esc_html__('By title', 'wp-latest-posts-addon') . '</label></li>
                    <li><input type="radio" name="wplp_cat_source_order" id="cat_source_order3" value="random" class="ju-radiobox"
                            ' . (isset($source_order_selected['random']) ? esc_html($source_order_selected['random']) : '') . ' />
                        <label for="cat_source_order3" class="radio-label">' . esc_html__('By random', 'wp-latest-posts-addon') . '</label></li>';
        ?>
        <li><input type="radio" name="wplp_cat_source_order" id="cat_source_order5" value="view"
                   class="ju-radiobox"
                <?php echo(isset($source_order_selected['view']) ? esc_html($source_order_selected['view']) : '') ?> />
            <label for="cat_source_order5"
                   class="radio-label"><?php esc_html_e('Most popular', 'wp-latest-posts-addon') ?></label></li>
        <?php
                echo '</ul>
                <div class="clearfix"></div>
            </div>
            <hr>
            <div class="sort-order-field settings-wrapper-field">
                <label class="settings-wrapper-title tooltip" title="'.esc_html__('Define the ordering direction for the news', 'wp-latest-posts-addon').'">' . esc_html__('Order', 'wp-latest-posts-addon') . '</label>
                <ul class="un-craft">
                    <li><input type="radio" name="wplp_cat_source_asc" id="cat_source_asc1" value="asc" class="ju-radiobox"
                            ' . (isset($source_asc_selected['asc']) ? esc_html($source_asc_selected['asc']) : '') . ' />
                        <label for="cat_source_asc1" class="radio-label">' . esc_html__('Ascending', 'wp-latest-posts-addon') . '</label></li>
                    <li><input type="radio" name="wplp_cat_source_asc" id="cat_source_asc2" value="desc" class="ju-radiobox"
                            ' . (isset($source_asc_selected['desc']) ? esc_html($source_asc_selected['desc']) : '') . ' />
                        <label for="cat_source_asc2" class="radio-label">' . esc_html__('Descending', 'wp-latest-posts-addon') . '</label></li>
                </ul>
                <div class="clearfix"></div>
            </div>';
        echo '</div>';
        echo '</div>';
    }


    /**
     * Get taxonomy from post type
     *
     * @param integer $postType      Type of posts
     * @param integer $currentoption Current option
     * @param integer $terms         Id of terms
     * @param string  $language      Languague translation
     *
     * @return void
     */
    public function getTaxonomyWPLP($postType = null, $currentoption = null, $terms = null, $language = null)
    {
        //phpcs:disable WordPress.Security.NonceVerification.Missing -- This function is called via ajax and class method, this is not an action request there is no need to add a nonce
        if (isset($_POST['postType'])) {
            $postType = $_POST['postType'];
        }
        if (isset($_POST['TaxChoose'])) {
            $currentoption = $_POST['TaxChoose'];
        }
        if (isset($_POST['language'])) {
            $language = $_POST['language'];
        }
        //phpcs:enable
        if (is_multisite()) {
            $multisite_id = substr($postType, 0, strpos($postType, '_'));

            $postType = substr($postType, strpos($postType, '_') + 1);
            switch_to_blog($multisite_id);
            $taxonomy_names = get_object_taxonomies($postType);
            restore_current_blog();
        } else {
            $taxonomy_names = get_object_taxonomies($postType);
        }


        if (count($taxonomy_names) > 0) {
            echo '<li id="taxonomySelector" class="field input-field input-select">
<div class="width33 input-field input-select"><label for="custom_post_select" class="post_cb settings-wrapper-title">' .
                 esc_html__('Choose a taxonomy (optionnal)', 'wp-latest-posts-addon') . ' : </label>
         
            <select id="custom_post_select_tax" name="wplp_custom_post_taxonomy" class="width-30 wplp-font-style">
            <option value="">' . esc_html__('all taxonomies', 'wp-latest-posts-addon') . '</option>' .
                 '';

            foreach ($taxonomy_names as $cat) {
                if ($cat === $currentoption) {
                    $taxname = $currentoption;
                }
                echo '<option value="' .
                     esc_html($cat) . '"  ' .
                     (($cat === $currentoption) ? ' selected="selected"' : '') . '> ' . esc_html($cat) . ' </option>';
            }
            echo '</select></div></li>'; //field
            if (isset($taxname)) {
                if (is_multisite()) {
                    switch_to_blog($multisite_id);
                    $termsname = get_terms(
                        $taxname,
                        array(
                            'post_type'  => array($postType),
                            'hide_empty' => false,
                        )
                    );
                    restore_current_blog();
                } else {
                    $termsname = get_terms(
                        $taxname,
                        array(
                            'post_type'  => array($postType),
                            'hide_empty' => false,
                        )
                    );
                }
            }

            if (isset($termsname) && count($termsname) > 0) {
                /**
                 * Get taxonomy by language.
                 *
                 * @param string Term name
                 * @param string Taxonomy name
                 * @param string Type of post
                 * @param string Default language
                 *
                 * @internal
                 *
                 * @return array
                 */
                $termsname = apply_filters(
                    'wplp_get_custom_taxonomy_by_language',
                    $termsname,
                    $taxname,
                    $postType,
                    $language
                );

                echo '<li id="termSelector" class="field"><div class="width33">
<label for="custom_post_select_term" class="post_cb settings-wrapper-title">Choose a terms (optionnal) : </label>
            
                <select id="custom_post_select_term" name="wplp_custom_post_term" class="width-30 wplp-font-style">
                <option value="">' . esc_html__('all terms', 'wp-latest-posts-addon') . '</option>' .
                     '';
                foreach ($termsname as $cat) {
                    echo '<option value="' .
                         esc_html($cat->term_id) . '"  ' .
                         (((int) $cat->term_id === (int) $terms) ? ' selected="selected"' : '') . '> ' . esc_html($cat->name) . ' </option>';
                }
                echo '</select></div></li>'; //field
            }
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            /* special ajax here */
            wp_die();
        }
        // this is required to terminate immediately and return a proper response
    }


    /**
     * Content source tab for tags
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayContentTags($settings)
    {
        if (!isset($settings['source_tags'])
            || empty($settings['source_tags'])
            || !$settings['source_tags']
        ) {
            $settings['source_tags'] = array('_all');
        }


        foreach ($settings['source_tags'] as $tag) {
            $source_tag_checked[$tag] = ' checked="checked"';
        };

        echo '<div class="settings-wrapper">';
        if (is_multisite()) {
            if (!isset($settings['mutilsite_tag'])
                || empty($settings['mutilsite_tag'])
                || !$settings['mutilsite_tag']
            ) {
                $settings['mutilsite_tag'] = '';
            }

            $all_blog_tags = get_sites();

            $mutilsite_selected_tags = '';
            if (isset($settings['mutilsite_tag'])) {
                $mutilsite_selected_tags = $settings['mutilsite_tag'];
            }

            echo '<div class="multisite-select-field settings-wrapper-field">';
            echo '<label class="settings-wrapper-title">' . esc_html__('Multisite selection', 'wp-latest-posts-addon') . '</label>';
            echo '<select id="mutilsite_select_tag" class="mutilsite_select wplp-font-style wplp-short-text width-30" name="wplp_mutilsite_tag">
                        <option value="all_blog">' . esc_html__('All blog', 'wp-latest-posts-addon') . '</option>';
            foreach ($all_blog_tags as $val) {
                $detail = get_blog_details((int) $val->blog_id);
                $check  = ($settings['mutilsite_tag'] === $val->blog_id) ? ' selected="selected"' : '';
                echo '<option value="' . esc_html($val->blog_id) . '" ' . esc_html($check) . '> ' . esc_html($detail->blogname) . ' </option>';
            }
            echo '</select>';
            echo '<input type="hidden" value="' . esc_html($mutilsite_selected_tags) . '" id="selected_multisite_tags_type" />';
            echo '</div>';
        }
        echo '<div class="content-search-field">
                    <input type="text" class="content-search-input search-input" placeholder="' . esc_html__('Search content', 'wp-latest-posts-addon') . '">
                    <i class="material-icons">search</i>
                </div>';
        echo '<div class="list-selector-field settings-wrapper-field tagcat">           
                <ul class="craft">';
        echo '<li><input id="tags_all" type="checkbox" name="wplp_source_tags[]" class="ju-checkbox wplp_change_content" value="_all" ' .
             (isset($source_tag_checked['_all']) ? esc_html($source_tag_checked['_all']) : '') . ' />' .
             '<label for="tags_all" class="tag_cb radio-label">All tags</li>';
        if (is_multisite()) {
            if ('all_blog' === $settings['mutilsite_tag']) {
                $blogs = get_sites();
                foreach ($blogs as $blog) {
                    switch_to_blog((int) $blog->blog_id);
                    $allcats = get_tags();
                    if (isset($settings['content_language']) && !empty($allcats)) {
                        /**
                         * Get list tags via multilanguage plugin.
                         *
                         * @param array  List category
                         * @param string Language to translate
                         *
                         * @internal
                         *
                         * @return array
                         */
                        $allcats = apply_filters('wplp_get_tags_by_language', $allcats, $settings['content_language']);
                    }
                    if (!empty($allcats)) {
                        foreach ($allcats as $allcat) {
                            $allcat->blog = (int) $blog->blog_id;
                            $tags[] = $allcat;
                        }
                    }
                    restore_current_blog();
                }
            } else {
                switch_to_blog((int) $settings['mutilsite_tag']);
                if (!empty($settings['content_language']) && function_exists('pll_get_term')) {
                    $tags = get_tags(array('lang' => $settings['content_language']));
                } else {
                    $tags = get_tags();
                }
                if (isset($settings['content_language']) && !empty($tags)) {
                    /**
                     * Get list tags via multilanguage plugin.
                     *
                     * @param array  List tags
                     * @param string Language to translate
                     *
                     * @internal
                     *
                     * @return array
                     */
                    $tags = apply_filters('wplp_get_tags_by_language', $tags, $settings['content_language']);
                }

                foreach ($tags as $tag) {
                    $tag->blog = (int) $settings['mutilsite_tag'];
                }
                restore_current_blog();
            }

            if (!empty($tags)) {
                $num = 0;
                foreach ($tags as $k => $tag) {
                    $num++;
                    ($num > 20) ? $attr = 'style=display:none data-show=false' : $attr = 'data-show=true';
                    echo '<li class="wplp-list-source" data-num="'. esc_html($num) .'" '. esc_html($attr) .'><input id="tcb_' . esc_html($k) . '" type="checkbox" name="wplp_source_tags[]" class="tag_cb ju-checkbox wplp_change_content"
                    value="' . esc_html($k . '_' . $tag->term_id . '_blog' . $tag->blog) . '" 
                    ' . (isset($source_tag_checked[$k . '_' . $tag->term_id . '_blog' . $tag->blog]) ? esc_html($source_tag_checked[$k . '_' . $tag->term_id . '_blog' . $tag->blog]) : '') . '/>';
                    echo '<label for="tcb_' . esc_html($k) . '" class="tag_cb radio-label">' . esc_html($tag->name) . '</label></li>';
                }

                if (count($tags) > 20) {
                    echo '<li class="wplp-load-more">';
                    esc_html_e('Load more...', 'wp-latest-posts-addon');
                    echo '</li>';
                }
            }
        } else {
            $tags = get_tags();
            if (isset($settings['content_language']) && !empty($tags)) {
                /**
                 * Get list tags via multilanguage plugin.
                 *
                 * @param array  List tags
                 * @param string Language to translate
                 *
                 * @internal
                 *
                 * @return array
                 */
                $tags = apply_filters('wplp_get_tags_by_language', $tags, $settings['content_language']);
            }

            if (!empty($tags)) {
                $num = 0;
                foreach ($tags as $k => $tag) {
                    $num++;
                    ($num > 20) ? $attr = 'style=display:none data-show=false' : $attr = 'data-show=true';
                    echo '<li class="wplp-list-source" data-num="'. esc_html($num) .'" '. esc_html($attr) .'><input id="tcb_' . esc_html($k) . '" type="checkbox" name="wplp_source_tags[]" class="tag_cb ju-checkbox wplp_change_content" 
                    value="' . esc_html($tag->term_id) . '" ' . (isset($source_tag_checked[$tag->term_id]) ? esc_html($source_tag_checked[$tag->term_id]) : '') . ' />';
                    echo '<label for="tcb_' . esc_html($k) . '" class="tag_cb radio-label">' . esc_html($tag->name) . '</label></li>';
                }

                if (count($tags) > 20) {
                    echo '<li class="wplp-load-more">';
                    esc_html_e('Load more...', 'wp-latest-posts-addon');
                    echo '</li>';
                }
            }
        }
        echo '</ul>
                <div class="clearfix"></div>
            </div>';

        echo '</div>';
    }

    /**
     * Content source Page
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayContentPages($settings)
    {
        if (!isset($settings['source_pages']) || empty($settings['source_pages']) || !$settings['source_pages']) {
            $settings['source_pages'] = array('_all');
        }

        if (!isset($settings['custom_post_type'])
            || empty($settings['custom_post_type'])
            || !$settings['custom_post_type']
        ) {
            $settings['custom_post_type'] = '';
        }


        foreach ($settings['source_pages'] as $page) {
            $source_page_checked[$page] = ' checked="checked"';
        };

        echo '<li><input id="page_all" type="checkbox" name="wplp_source_pages[]" class="ju-checkbox wplp_change_content" value="_all" ' .
             (isset($source_page_checked['_all']) ? esc_html($source_page_checked['_all']) : '') . ' />' .
             '<label for="page_all" class="page_cb radio-label">All Pages</li>';
        if (is_multisite()) {
            if (!isset($settings['mutilsite_page'])
                || empty($settings['mutilsite_page'])
                || !$settings['mutilsite_page']
            ) {
                $settings['mutilsite_page'] = '';
            }
            if ('all_blog' === $settings['mutilsite_page']) {
                $blogs = get_sites();
                foreach ($blogs as $blog) {
                    switch_to_blog((int) $blog->blog_id);
                    $allcats = get_pages();
                    if (isset($settings['content_language'])) {
                        /**
                         * Get list pages via multilanguage plugin.
                         *
                         * @param array  List category
                         * @param string Language to translate
                         *
                         * @internal
                         *
                         * @return array
                         */
                        $allcats = apply_filters('wplp_get_pages_by_language', $allcats, $settings['content_language']);
                    }
                    foreach ($allcats as $allcat) {
                        $pages[] = $allcat;
                    }
                    restore_current_blog();
                }
            } else {
                switch_to_blog((int) $settings['mutilsite_page']);
                $pages = get_pages();
                if (isset($settings['content_language'])) {
                    /**
                     * Get list pages via multilanguage plugin.
                     *
                     * @param array  List pages
                     * @param string Language to translate
                     *
                     * @internal
                     *
                     * @return array
                     */
                    $pages = apply_filters('wplp_get_pages_by_language', $pages, $settings['content_language']);
                }
                restore_current_blog();
            }

            $num = 0;
            foreach ($pages as $k => $page) {
                $num++;
                ($num > 20) ? $attr = 'style=display:none data-show=false' : $attr = 'data-show=true';
                echo '<li class="wplp-list-source" data-num="'. esc_html($num) .'" '. esc_html($attr) .'><input id="pcb_' . esc_html($k) . '" type="checkbox" name="wplp_source_pages[]" value="' . esc_html($k . '_' .
                                                                                                                              $page->ID) . '" ' . (isset($source_page_checked[$k . '_' . $page->ID]) ?
                        esc_html($source_page_checked[$k . '_' . $page->ID]) : '') . ' class="page_cb ju-checkbox wplp_change_content" />';
                echo '<label for="pcb_' . esc_html($k) . '" class="page_cb radio-label">' . esc_html($page->post_title) . '</label></li>';
            }

            if (count($pages) > 20) {
                echo '<li class="wplp-load-more">';
                esc_html_e('Load more...', 'wp-latest-posts-addon');
                echo '</li>';
            }
        } else {
            $pages = get_pages();
            if (isset($settings['content_language'])) {
                /**
                 * Get list pages via multilanguage plugin.
                 *
                 * @param array  List pages
                 * @param string Language to translate
                 *
                 * @internal
                 *
                 * @return array
                 */
                $pages = apply_filters('wplp_get_pages_by_language', $pages, $settings['content_language']);
            }

            $num = 0;
            foreach ($pages as $k => $page) {
                $num++;
                ($num > 20) ? $attr = 'style=display:none data-show=false' : $attr = 'data-show=true';
                echo '<li class="wplp-list-source" data-num="'. esc_html($num) .'" '. esc_html($attr) .'><input id="pcb_' . esc_html($k) . '" type="checkbox" name="wplp_source_pages[]" value="' .
                     esc_html($page->ID) . '" ' . (isset($source_page_checked[$page->ID]) ? esc_html($source_page_checked[$page->ID]) : '') .
                     ' class="page_cb ju-checkbox wplp_change_content" />';
                echo '<label for="pcb_' . esc_html($k) . '" class="page_cb radio-label">' . esc_html($page->post_title) . '</label></li>';
            }

            if (count($pages) > 20) {
                echo '<li class="wplp-load-more">';
                esc_html_e('Load more...', 'wp-latest-posts-addon');
                echo '</li>';
            }
        }
    }


    /**
     * Display source category tab content
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayContentInclusion($settings)
    {
        if (isset($settings['content_include'])) {
            $content_include_checked[$settings['content_include']] = ' checked="checked"';
        }
        /**
         * Show content inclusion radio button set *
         */
        ?>
        <div class="posts-sort-field settings-wrapper-field">
            <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Filter your posts/pages if your content is in more than one category. Include the news only once or several times', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Content inclusion (for multiple category posts)', 'wp-latest-posts-addon') ?></label>
            <ul class="un-craft">
                <li><input id="content_include1" type="radio" class="ju-radiobox" name="wplp_content_include" value="0"
                        <?php echo(isset($content_include_checked[0]) ? esc_html($content_include_checked[0]) : '') ?> />
                    <label for="content_include1"
                           class="radio-label"><?php esc_html_e('Include All (AND)', 'wp-latest-posts-addon') ?></label>
                </li>
                <li><input id="content_include2" type="radio" class="ju-radiobox" name="wplp_content_include" value="1"
                        <?php echo(isset($content_include_checked[1]) ? esc_html($content_include_checked[1]) : '') ?>/>
                    <label for="content_include2"
                           class="radio-label"><?php esc_html_e('Include content only once (OR)', 'wp-latest-posts-addon') ?></label>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <?php
    }

    /**
     * Adds fields of the pro plugin to the content_source:category admin settings tab
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayArticleDate($settings)
    {
        ?>
        <hr>
        <div class="schedule-field settings-wrapper-field">
            <div class="title float">
                <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Select the content published after, before or between certain dates', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Published', 'wp-latest-posts-addon') ?></label>
                <select name="wplp_source_date_min_switch" class="wplp-font-style wplp-short-text">
                    <option value="after"
                        <?php echo((isset($settings['source_date_min_switch']) && $settings['source_date_min_switch'] === 'after') ? 'selected' : '') ?> >
                        <?php esc_html_e('after', 'wp-latest-posts-addon') ?>
                    </option>
                    <option value="between" <?php echo((isset($settings['source_date_min_switch']) && $settings['source_date_min_switch'] === 'between') ? 'selected' : '') ?> >
                        <?php esc_html_e('between', 'wp-latest-posts-addon') ?>
                    </option>
                    <option value="before" <?php echo((isset($settings['source_date_min_switch']) && $settings['source_date_min_switch'] === 'before') ? 'selected' : '') ?> >
                        <?php esc_html_e('before', 'wp-latest-posts-addon') ?>
                    </option>
                </select>
            </div>
            <div class="calendar float source_date_min">
                <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Select a date in the calendar to apply', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Date', 'wp-latest-posts-addon') ?></label>
                <input id="source_date_min" type="text"
                       name="wplp_source_date_min" class="wplp_datepicker wplp-short-text wplp-font-style"
                       value="<?php echo esc_html(htmlspecialchars(isset($settings['source_date_min']) ? $settings['source_date_min'] : '')) ?>"/>
            </div>
            <div class="calendar float source_date_max">
                <label class="settings-wrapper-title"><?php esc_html_e('Date', 'wp-latest-posts-addon') ?></label>
                <input id="source_date_max" type="text"
                       name="wplp_source_date_max" class="wplp_datepicker wplp-short-text wplp-font-style"
                       value="<?php echo esc_html(htmlspecialchars(isset($settings['source_date_max']) ? $settings['source_date_max'] : '')) ?>"/>
            </div>
            <div class="clearfix"></div>
        </div>
        <hr>
        <div class="show-article settings-wrapper-field">
            <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Limit the latest news published by hours, days, minutes, years', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Content created in the latest', 'wp-latest-posts-addon') ?></label>
            <ul class="un-craft show-time-field">
                <li><input id="last_hours" type="text" name="wplp_last_hours"
                           value="<?php echo(isset($settings['last_hours']) ? esc_html($settings['last_hours']) : '') ?>"
                           class="show-time short-text"/>
                    <label for="last_hours"
                           class="radio-label"><?php esc_html_e('Hours', 'wp-latest-posts-addon') ?></label></li>
                <li><input id="last_days" type="text" name="wplp_last_days"
                           value="<?php echo(isset($settings['last_days']) ? esc_html($settings['last_days']) : '') ?>"
                           class="show-time short-text"/>
                    <label for="last_days"
                           class="radio-label"><?php esc_html_e('Days', 'wp-latest-posts-addon') ?></label></li>
                <li><input id="last_months" type="text" name="wplp_last_months"
                           value="<?php echo(isset($settings['last_months']) ? esc_html($settings['last_months']) : '') ?>"
                           class="show-time short-text"/>
                    <label for="last_months"
                           class="radio-label"><?php esc_html_e('Months', 'wp-latest-posts-addon') ?></label></li>
                <li><input id="last_years" type="text" name="wplp_last_years"
                           value="<?php echo(isset($settings['last_years']) ? esc_html($settings['last_years']) : '') ?>"
                           class="show-time short-text"/>
                    <label for="last_years"
                           class="radio-label"><?php esc_html_e('Years', 'wp-latest-posts-addon') ?></label></li>
            </ul>
            <div class="clearfix"></div>
        </div>
        <hr>
        <?php
        //TODO: externalize js
        ?>
        <?php
    }

    /**
     * Display background color option
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayBackgroundColor($settings)
    {
        $defaultColorCheckbox = '';
        if (isset($settings['defaultColor'])) {
            if ($settings['defaultColor'] === 'yes' || $settings['defaultColor'] === '1') {
                $defaultColorCheckbox = 'checked="checked"';
            }
        }
        $list_colors = array(
            '#4FC18C',
            '#0A60BD',
            '#419FFD',
            '#33CCCC',
            '#FEF2BF',
            '#FF5C5D',
            '#A635D8',
            '#FF9A00',
            '#BDCF88',
            '#D95858',
            '#DAF489',
            '#ECF0F1',
            '#606470',
            '#BDCF88',
            '#84B9EF',
            '#000000'
        );

        echo '<div id="wplp-default-color">';
        echo '<div class="default-color-theme fit-block" style="margin-bottom: 20px">
                <label class="ju-setting-label image-fit-label" for="default-color">' . esc_html__('Background Color', 'wp-latest-posts-addon') . '</label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox"
                               name="wplp_defaultColor"
                               id="default-color"
                               value="' . (isset($settings['defaultColor']) ? esc_html($settings['defaultColor']) : 'no') . '"
                            ' . esc_attr($defaultColorCheckbox) . '/>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="select-background-color">
                <div class="color-list-box" >
                    <ul>';
        foreach ($list_colors as $color) {
            $classActive = '';
            if (isset($settings['colorpicker']) && $color === $settings['colorpicker']) {
                $classActive = 'color-selected';
            }
            echo '<li style="background-color: ' . esc_attr($color) . '" class="color-box ' . esc_attr($classActive) . '" data-id="' . esc_attr($color) . '"></li>';
        }

        echo '</ul>
                    <div class="clearfix"></div>
                </div>
                <div class="color-set" style="margin-top: 20px">
                    <div id="colorPicker">
                    <input type="text" id="colorpicker" name="wplp_colorpicker" class="wplp-font-style wplp-short-text colorPicker-text"
                           value="' . (isset($settings['colorpicker']) ? esc_html($settings['colorpicker']) : '#FF0000') . '"/>
                    <input class="wplp_colorPicker" name="wplp_colorpicker_clone"
                           value="' . (isset($settings['colorpicker']) ? esc_html($settings['colorpicker']) : '#FF0000') . '"/>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="settings-wrapper-field">
                <div class="overlay-background">
                <label class="settings-wrapper-title tooltip" title="'.esc_html__('The transparency of the overlay color selected in the palette', 'wp-latest-posts-addon').'">'. esc_html__('Overlay Transparency', 'wp-latest-posts-addon') .'</label>
        <span data-option="overlayBackground" class="overlay-slider overlayBackground" style="margin-left: 12px"></span>
        <input id="overlayBackground" type="text" name="wplp_overlay_background" style="width: 25%;"
                               value="'. esc_html(htmlspecialchars(isset($settings['overlay_background']) ? $settings['overlay_background'] : '0.2')) .'"
                               class="wplp-short-text wplp-font-style center-text wplp-slider-input" />
                </div></div>
            </div>';
        echo '</div>';
    }

    /**
     * Display crop option
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayCropOption($settings)
    {
        if (isset($settings['crop_title'])) {
            $crop_title_checked[$settings['crop_title']] = ' checked="checked"';
        }
        if (isset($settings['crop_text'])) {
            $crop_text_checked[$settings['crop_text']] = ' checked="checked"';
        }
        echo ' <label class="settings-wrapper-title tooltip" title="'.esc_html__('Select types to crop the title and content of your blocks', 'wp-latest-posts-addon').'">' . esc_html__('Content automatic crop', 'wp-latest-posts-addon') . '</label>
            <div class="crop-title settings-wrapper-field">
                <div class="crop-length float">
                    <label class="settings-wrapper-subtitle tooltip" title="'.esc_html__('Set maximum title length', 'wp-latest-posts-addon').'">' . esc_html__('Crop Length', 'wp-latest-posts-addon') . '</label>
                    <input id="crop_title_len" type="text" name="wplp_crop_title_len" class="wplp-short-text wplp-font-style wplp-short-input center-text"
                           value="' . esc_html(htmlspecialchars(isset($settings['crop_title_len']) ? $settings['crop_title_len'] : '1')) . '"  />
                </div>
                <div class="crop-type float width-50">
                    <label class="settings-wrapper-subtitle tooltip" title="'.esc_html__('Select a type to crop the title of your blocks', 'wp-latest-posts-addon').'">' . esc_html__('Crop title type', 'wp-latest-posts-addon') . '</label>
                    <ul class="un-craft col-li-3" style="height: 47px; line-height: 47px">
                        <li>
                            <input type="radio" name="wplp_crop_title" id="crop_title0" value="0" class="ju-radiobox"
                                ' . (isset($crop_title_checked[0]) ? esc_html($crop_title_checked[0]) : '') . ' />
                            <label for="crop_title0" class="radio-label">' . esc_html__('Words', 'wp-latest-posts-addon') . '</label>
                        </li>
                        <li>
                            <input type="radio" name="wplp_crop_title" id="crop_title1" value="1" class="ju-radiobox"
                                ' . (isset($crop_title_checked[1]) ? esc_html($crop_title_checked[1]) : '') . ' />
                            <label for="crop_title1" class="radio-label">' . esc_html__('Chars', 'wp-latest-posts-addon') . '</label>
                        </li>
                        <li>
                            <input type="radio" name="wplp_crop_title" id="crop_title2" value="2" class="ju-radiobox"
                                ' . (isset($crop_title_checked[2]) ? esc_html($crop_title_checked[2]) : '') . ' />
                            <label for="crop_title2" class="radio-label">' . esc_html__('Lines', 'wp-latest-posts-addon') . '</label>
                        </li>
                    </ul>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="crop-text settings-wrapper-field">
                <div class="crop-length float">
                    <label class="settings-wrapper-subtitle tooltip" title="'.esc_html__('Set maximum content length', 'wp-latest-posts-addon').'">' . esc_html__('Crop Length', 'wp-latest-posts-addon') . '</label>
                    <input id="crop_text_len" type="text" name="wplp_crop_text_len" class="wplp-font-style wplp-short-text wplp-short-input center-text"
                           value="' . esc_html(htmlspecialchars(isset($settings['crop_text_len']) ? $settings['crop_text_len'] : '2')) . '"  />
                </div>
                <div class="crop-type float width-50">
                    <label class="settings-wrapper-subtitle tooltip" title="'.esc_html__('Select a type to crop the content of your blocks', 'wp-latest-posts-addon').'">' . esc_html__('Crop text type', 'wp-latest-posts-addon') . '</label>
                    <ul class="un-craft col-li-3" style="height: 47px; line-height: 47px">
                        <li>
                            <input type="radio" name="wplp_crop_text" id="crop_text0" value="0" class="ju-radiobox"
                                ' . (isset($crop_text_checked[0]) ? esc_html($crop_text_checked[0]) : '') . ' />
                            <label for="crop_text0" class="radio-label">' . esc_html__('Words', 'wp-latest-posts-addon') . '</label>
                        </li>
                        <li>
                            <input type="radio" name="wplp_crop_text" id="crop_text1" value="1" class="ju-radiobox"
                                ' . (isset($crop_text_checked[1]) ? esc_html($crop_text_checked[1]) : '') . ' />
                            <label for="crop_text1" class="radio-label">' . esc_html__('Chars', 'wp-latest-posts-addon') . '</label>
                        </li>
                        <li>
                            <input type="radio" name="wplp_crop_text" id="crop_text2" value="2" class="ju-radiobox"
                                ' . (isset($crop_text_checked[2]) ? esc_html($crop_text_checked[2]) : '') . ' />
                            <label for="crop_text2" class="radio-label">' . esc_html__('Lines', 'wp-latest-posts-addon') . '</label>
                        </li>
                    </ul>
                </div>
                <div class="clearfix"></div>
            </div>';
    }

    /**
     * Display open new blank link option
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayOpenNewlink($settings)
    {
        ?>
        <div class="fit-block" style="margin-bottom: 20px">
            <?php
            if (isset($settings['open_link'])) {
                $open_link_checked[$settings['open_link']] = ' checked="checked"';
            }
            ?>
            <label class="ju-setting-label image-fit-label tooltip" title="<?php esc_html_e('Open a news in a new window', 'wp-latest-posts-addon') ?>" for="wplp_open_link" style="color: #404852"><?php esc_html_e('Open links in new window', 'wp-latest-posts-addon') ?></label>
            <div class="ju-switch-button">
                <label class="switch">
                    <input type="hidden"
                           name="wplp_open_link"
                           value="0"
                    />
                    <input type="checkbox"
                           name="wplp_open_link"
                           id="wplp_open_link"
                           value="1"
                        <?php echo (isset($open_link_checked[1]) ? esc_html($open_link_checked[1]) : ''); ?>
                    />
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Display load more button
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayLoadmoreButton($settings)
    {
        ?>
        <div class="fit-block" style="margin-bottom: 20px">
            <?php
            if (isset($settings['load_more'])) {
                $load_more_checked[$settings['load_more']] = ' checked="checked"';
            }
            ?>
            <label class="ju-setting-label image-fit-label tooltip" title="<?php esc_html_e('Display load more button when enabled', 'wp-latest-posts-addon') ?>" for="wplp_load_more" style="color: #404852"><?php esc_html_e('Display load more button', 'wp-latest-posts-addon') ?></label>
            <div class="ju-switch-button">
                <label class="switch">
                    <input type="hidden"
                           name="wplp_load_more"
                           value="0"
                    />
                    <input type="checkbox"
                           name="wplp_load_more"
                           id="wplp_load_more"
                           value="1"
                        <?php echo (isset($load_more_checked[1]) ? esc_html($load_more_checked[1]) : ''); ?>
                    />
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Adds cropping feature field of the pro plugin version to the Image Source admin settings tab
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function imagesourceDisplayCropImages($settings)
    {
        /**
         * Crop images switch
         **/
        if (isset($settings['crop_img'])) {
            $crop_img_checked[$settings['crop_img']] = ' checked="checked"';
        }

        echo '<div class="fit-block">
                <label class="ju-setting-label image-fit-label">' . esc_html__('Crop images to fit size', 'wp-latest-posts-addon') . '</label>
                <div class="ju-switch-button">
                    <label class="switch">
                        <input type="checkbox"
                               name="wplp_crop_img"
                               id="crop_img"
                               value="1"
                               ' . (isset($crop_img_checked[1]) ? esc_attr($crop_img_checked[1]) : '') . '/>
                        <span class="slider"></span>
                    </label>
                    <span class="width_height_settings">
                    <input id="thumb_width" type="text" name="wplp_thumb_width"
                           value="' . esc_html(htmlspecialchars(isset($settings['thumb_width']) ? $settings['thumb_width'] : '150')) . '"
                           class="wplp-short-text wplp-font-style width_height_input" />&nbspx&nbsp
                    <input id="thumb_height" type="text" name="wplp_thumb_height"
                           value="' . esc_html(htmlspecialchars(isset($settings['thumb_height']) ? $settings['thumb_height'] : '150')) . '"
                           class="wplp-short-text wplp-font-style width_height_input" />&nbsppx
                </span>
                </div>
                <div class="clearfix"></div>
            </div>';
    }

    /**
     * Display about tab
     *
     * @param integer $version Version of plugin
     *
     * @return void
     */
    public function configurationDisplayAbout($version)
    {
        echo '<p>' . esc_html__('Pro add-on plugin version ', 'wp-latest-posts-addon') . esc_html($version) .
             esc_html__(' is installed and activated.', 'wp-latest-posts-addon') . '</p>';
        echo '<p><em>' . esc_html__('Congratulations! and thank you for your support.', 'wp-latest-posts-addon') . '</em></p>';
    }

    /**
     * Display default image in advanced tab
     *
     * @param array $settings List settings
     *
     * @return void
     */
    public function advancedDisplayDefaultImage($settings)
    {
        echo '<hr>
            <div class="content-text settings-wrapper-field">
                <label class="settings-wrapper-title tooltip" title="'.esc_html__('It will be the represent image if the news does not have any images', 'wp-latest-posts-addon').'">' . esc_html__('Default image', 'wp-latest-posts-addon') . '</label>
                <div style="width: 100%">
                    <input id="default_img" type="file" class="dropify" name="wplp_default_img" class="regular-text"
                       data-default-file="' . esc_html(htmlspecialchars(isset($settings['default_img']) ? $settings['default_img'] : WPLP_PLUGIN_DIR . DEFAULT_IMG)) . '"
                       value="' . esc_html(htmlspecialchars(isset($settings['default_img']) ? $settings['default_img'] : WPLP_PLUGIN_DIR . DEFAULT_IMG)) . '" />
                </div>
                <input type="hidden" id="default_img_previous" name="wplp_default_img_previous"
                       value="' . esc_html(htmlspecialchars(isset($settings['default_img']) ? $settings['default_img'] : WPLP_PLUGIN_DIR . DEFAULT_IMG)) . '" />
                <input type="hidden" id="default_img_id_previous" name="wplp_default_img_id_previous"
                       value="' . esc_html(htmlspecialchars(isset($settings['default_img_id']) ? $settings['default_img_id'] : '')) . '" />
            </div>';
    }

    /**
     * Display readmore text in advanced tab
     *
     * @param array $settings List settings
     *
     * @return void
     */
    public function advancedDisplayReadmoreText($settings)
    {
        echo '<div class="read-more float half-width">
                <label class="settings-wrapper-title">' . esc_html__('Read more text', 'wp-latest-posts-addon') . '</label>
                <input id="read_more" type="text" name="wplp_read_more" class="wplp-short-text wplp-font-style"
                       value="' . esc_html(htmlspecialchars(isset($settings['read_more']) ? $settings['read_more'] : '')) . '" />
              </div>';
    }

    /**
     * Display animation tab
     *
     * @param array $settings List settings
     *
     * @return void
     */
    public function themeDisplayAnimationTab($settings)
    {
        if (isset($settings['autoanimation'])) {
            $autoanim_checked[$settings['autoanimation']] = ' checked="checked"';
        }
        $classdisabledsmooth = '';
        if (strpos($settings['theme'], 'timeline')) {
            $classdisabledsmooth = ' disabled';
        }
        $classdisabled = '';
        if (strpos($settings['theme'], 'masonry') || strpos($settings['theme'], 'portfolio')) {
            $classdisabled = ' disabled';
        }

        $autoanim_trans_values = array('Fade', 'Slide');
        if (isset($settings['autoanimation_trans'])) {
            $transition_selected[$settings['autoanimation_trans']] = ' checked="checked"';
        }

        $autoanim_slidedir_values = array(
            1 => 'Vertical',
            0 => 'Horizontal'
        );
        if (isset($settings['autoanimation_slidedir'])) {
            $slidedir_selected[$settings['autoanimation_slidedir']] = ' checked="checked"';
        }

        if (isset($settings['autoanim_loop'])) {
            $loop_selected[$settings['autoanim_loop']] = ' checked="checked"';
        }

        if (isset($settings['autoanim_pause_hover'])) {
            $pause_hover_selected[$settings['autoanim_pause_hover']] = ' checked="checked"';
        }

        if (isset($settings['autoanim_pause_action'])) {
            $pause_action_selected[$settings['autoanim_pause_action']] = ' checked="checked"';
        }

        if (isset($settings['autoanim_touch_action'])) {
            $autoanim_touch[$settings['autoanim_touch_action']] = ' checked="checked"';
        }

        echo '<div id="animation" class="tab-content">
            <div class="white-content">
                 <label class="ju-setting-label">' . esc_html__('No animation option available for this theme', 'wp-latest-posts-addon') . '</label>
            </div>
            <div class="settings-wrapper" style="padding-top: 20px">
                <div class="fit-block" style="margin-bottom: 20px">
                    <label class="ju-setting-label image-fit-label tooltip" title="'.esc_html__('Animate the news slider automatically with horizontal auto-scroll', 'wp-latest-posts-addon').'" style="color: #404852">' . esc_html__('Autoanimation', 'wp-latest-posts-addon') . '</label>
                    <div class="ju-switch-button">
                        <label class="switch">
                        <input type="hidden" name="wplp_autoanimation" value="0">
                            <input type="checkbox"
                                   name="wplp_autoanimation"
                                   id="autoanimation"
                                   value="1"
                                ' . (isset($autoanim_checked[1]) ? esc_html($autoanim_checked[1]) : '') . '
                                    ' . esc_html($classdisabled) . '/>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="speed settings-wrapper-field">
                    <div class="autoanim-speed float width-50">
                        <label class="settings-wrapper-title tooltip" title="'.esc_html__('Duration of transition between slides (in ms)', 'wp-latest-posts-addon').'">' . esc_html__('Autoanim speed,', 'wp-latest-posts-addon') . '<i> in milliseconds</i></label>
                        <input id="autoanim_slideshowspeed" type="text" name="wplp_autoanim_slideshowspeed" class="wplp-short-input wplp-font-style wplp-short-text center-text"
                               value="' . (isset($settings['autoanim_slideshowspeed']) ? esc_html($settings['autoanim_slideshowspeed']) : '7000') . '" ' . esc_html($classdisabled) . '/>
                    </div>
                    <div class="animation-speed float width-50">
                        <label class="settings-wrapper-title tooltip" title="'.esc_html__('Delay between transitions (in ms). If this parameter is not specified, auto play will be disabled', 'wp-latest-posts-addon').'">' . esc_html__('Animation speed,', 'wp-latest-posts-addon') . '<i> in milliseconds</i></label>
                        <input id="autoanim_slidespeed" type="text" name="wplp_autoanim_slidespeed" class="wplp-short-input wplp-font-style wplp-short-text center-text"
                               value="' . (isset($settings['autoanim_slidespeed']) ? esc_html($settings['autoanim_slidespeed']) : '600') . '" ' . esc_html($classdisabled) . '/>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <hr>
                <div class="transition settings-wrapper-field">
                 <div class="translation-checkbox">
                    <div class="autoanim-transition float width-50">
                        <label class="settings-wrapper-title tooltip" title="'.esc_html__('Select slideshow transition effect', 'wp-latest-posts-addon').'">' . esc_html__('Autoanimation transition', 'wp-latest-posts-addon') . '</label>
                        <ul class="list-animation-transition list-animation">';
        foreach ($autoanim_trans_values as $value => $text) {
            echo '<li class="' . (($settings['autoanimation_trans'] === (string) $value) ? 'autoanimation_trans' . esc_html($settings['autoanimation_trans']) : '') . '">
            <input type="radio" name="wplp_autoanimation_trans" id="autoanimation_trans' . esc_html($value) . '" data-id="autoanimation_trans' . esc_html($value) . '"
                ' . esc_html($classdisabled) . esc_html($classdisabledsmooth) . '
                ' . (isset($transition_selected[$value]) ? esc_html($transition_selected[$value]) : '') . '
               value="' . esc_html($value) . '" class="transition-radio"/>
            <label for="autoanimation_trans' . esc_html($value) . '" class="transition-label ' . (($settings['autoanimation_trans'] === (string) $value) ? 'transition-label-checked' : '') . '">' . esc_html($text) . '</label>
        </li>';
        }
        echo '</ul>
                    </div>
                    <div class="animation-transition float width-50">
                        <label class="settings-wrapper-title tooltip" title="'.esc_html__('Select the transition direction of the slideshow', 'wp-latest-posts-addon').'">' . esc_html__('Slide transition', 'wp-latest-posts-addon') . '</label>
                        <ul class="list-slide-transition list-animation">';
        foreach ($autoanim_slidedir_values as $value => $text) {
            echo '<li class="' . (($settings['autoanimation_slidedir'] === (string) $value) ? 'autoanimation_slidedir' . esc_html($settings['autoanimation_slidedir']) : '') . '">
                <input type="radio" name="wplp_autoanimation_slidedir" id="autoanimation_slidedir' . esc_html($value) . '" data-id="autoanimation_slidedir' . esc_html($value) . '"
                    ' . esc_html($classdisabled) . esc_html($classdisabledsmooth) . '
                    ' . (isset($slidedir_selected[$value]) ? esc_html($slidedir_selected[$value]) : '') . '
                       value="' . esc_html($value) . '" class="transition-radio"/>
                <label for="autoanimation_slidedir' . esc_html($value) . '" class="transition-label ' . (($settings['autoanimation_slidedir'] === (string) $value) ? 'transition-label-checked' : '') . '">' . esc_html($text) . '</label>
            </li>';
        }
        echo '</ul>
                    </div>
                    <div class="animation-action settings-wrapper-field">
                        <div class="fit-block" style="margin-bottom: 20px"></div>
                        <div class="fit-block" style="margin-bottom: 20px">
                            <label class="settings-wrapper-title" for="wplp_slide_height">' . esc_html__('Height of vertical slide', 'wp-latest-posts-addon') . '</label>
                            <input type="number" class="wplp-short-input wplp-font-style wplp-short-text center-text"
                                   name="wplp_slide_height"
                                   id="wplp_slide_height"
                                   value="' . (isset($settings['slide_height']) ? esc_html($settings['slide_height']) : '500') . '"
                            /><span> px</span>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <hr>
                    </div>
                    <div class="animation-action settings-wrapper-field">
                        <div class="fit-block" style="margin-bottom: 20px">
                            <label class="ju-setting-label image-fit-label tooltip" title="'.esc_html__('Slideshow will automatically loop the animation if this option is enabled', 'wp-latest-posts-addon').'" for="wplp_autoanim_loop">' . esc_html__('Animation loop', 'wp-latest-posts-addon') . '</label>
                            <div class="ju-switch-button">
                                <label class="switch">
                                    <input type="hidden"
                                           name="wplp_autoanim_loop"
                                           value="0"
                                    />
                                    <input type="checkbox"
                                           name="wplp_autoanim_loop"
                                           id="wplp_autoanim_loop"
                                           value="1"
                                        ' . (isset($loop_selected[1]) ? esc_html($loop_selected[1]) : '') . '
                                    />
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="fit-block" style="margin-bottom: 20px">
                            <label class="ju-setting-label image-fit-label tooltip" title="'.esc_html__('Pause the slideshow when hovering the mouse. Note: Autoanimation is required', 'wp-latest-posts-addon').'" for="wplp_autoanim_pause_hover">' . esc_html__('Pause on Hover', 'wp-latest-posts-addon') . '</label>
                            <div class="ju-switch-button">
                                <label class="switch">
                                    <input type="hidden"
                                           name="wplp_autoanim_pause_hover"
                                           value="0"
                                    />
                                    <input type="checkbox"
                                           name="wplp_autoanim_pause_hover"
                                           id="wplp_autoanim_pause_hover"
                                           value="1"
                                        ' . (isset($pause_hover_selected[1]) ? esc_html($pause_hover_selected[1]) : '') . '
                                    />
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="touch-action settings-wrapper-field">
                        <div class="fit-block" style="margin-bottom: 20px">
                            <label class="ju-setting-label image-fit-label tooltip" title="'.esc_html__('Pause the slideshow when interacting with control elements. Note: Autoanimation is required', 'wp-latest-posts-addon').'" for="wplp_autoanim_pause_action">' . esc_html__('Pause on Action', 'wp-latest-posts-addon') . '</label>
                            <div class="ju-switch-button">
                                <label class="switch">
                                    <input type="hidden"
                                           name="wplp_autoanim_pause_action"
                                           value="0"
                                    />
                                    <input type="checkbox"
                                           name="wplp_autoanim_pause_action"
                                           id="wplp_autoanim_pause_action"
                                           value="1"
                                        ' . (isset($pause_action_selected[1]) ? esc_html($pause_action_selected[1]) : '') . '
                                    />
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="fit-block" style="margin-bottom: 20px">
                            <label class="ju-setting-label image-fit-label tooltip" title="'.esc_html__('Not allow touch swipe navigation of the slider on touch-enabled devices', 'wp-latest-posts-addon').'" for="wplp_autoanim_touch_action">' . esc_html__('Disable touch', 'wp-latest-posts-addon') . '</label>
                            <div class="ju-switch-button">
                                <label class="switch">
                                    <input type="hidden"
                                           name="wplp_autoanim_touch_action"
                                           value="0"
                                    />
                                    <input type="checkbox"
                                           name="wplp_autoanim_touch_action"
                                           id="wplp_autoanim_touch_action"
                                           value="1"
                                        ' . (isset($autoanim_touch[1]) ? esc_html($autoanim_touch[1]) : '') . '
                                    />
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Display force hover icon
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayForceHoverIcon($settings)
    {
        ?>
        <div class="fit-block" style="margin-bottom: 20px">
            <?php
            if (isset($settings['force_icon'])) {
                $force_icon_checked[$settings['force_icon']] = ' checked="checked"';
            }
            ?>
            <label class="ju-setting-label image-fit-label tooltip" title="<?php esc_html_e('When hovering the mouse over the image, the icon and overlay color are displayed', 'wp-latest-posts-addon') ?>" for="wplp_force_icon" style="color: #404852"><?php esc_html_e('Force an icon on hover', 'wp-latest-posts-addon') ?></label>
            <div class="ju-switch-button">
                <label class="switch">
                    <input type="hidden"
                           name="wplp_force_icon"
                           value="0"
                    />
                    <input type="checkbox"
                           name="wplp_force_icon"
                           id="wplp_force_icon"
                           value="1"
                        <?php echo (isset($force_icon_checked[1]) ? esc_html($force_icon_checked[1]) : ''); ?>
                    />
                    <span class="slider"></span>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Display icon selector
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function themeDisplayIconSelector($settings)
    {
        if (isset($settings['dashicons_selector']) && !empty($settings['dashicons_selector'])) {
            $dashicons_selector = $settings['dashicons_selector'];
        } else {
            $dashicons_selector = 'f109';
        }

        if (isset($settings['material_icon_selector'])) {
            $material_icons_selector = $settings['material_icon_selector'];
        } else {
            $material_icons_selector = 'e8f4';
        }

        if (isset($settings['icon_selector'])) {
            $icon_selector = $settings['icon_selector'];
        } else {
            $icon_selector = plugins_url('wp-latest-posts-addon') . '/themes/masonry/img/overimage.png';
        }
        ?>
        <div class="select-hover-icon" style="margin-top: 20px">
        <label class="settings-wrapper-title wplp-hover-custom-image-label tooltip" title="<?php esc_html_e('Create custom image overlay hover effects', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Mouse hover custom image', 'wp-latest-posts-addon') ?></label>
        <input type="hidden" name="wplp_hover_img_check" class="wplp-hover-img-input" value="<?php echo esc_attr($icon_selector) ?>">
        <input id="hover_img_icon" class="dropify" type="file" name="wplp_icon_selector" data-default-file="<?php echo esc_attr(htmlspecialchars($icon_selector)) ?>" value="<?php echo esc_attr(htmlspecialchars($icon_selector)) ?>"/>
        <div class="wplp-icons-wrap">
        <input type="hidden" id="dashicons_selector" name="wplp_dashicons_selector" value="<?php echo esc_html($dashicons_selector) ?>"/>
        <input type="hidden" id="material_icon_selector" name="wplp_material_icon_selector" value="<?php echo esc_html($material_icons_selector) ?>"/>
        <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Set icon styles', 'wp-latest-posts-addon') ?>" style="margin-top: 30px"><?php esc_html_e('Icon styles', 'wp-latest-posts-addon') ?></label>
        <div class="wplp-addon-icon-styles">
            <div class="wplp-addon-icon-style" style="width: 100%">
                <span class="masonry-category-icon-box" style="display:none" id="masonry-category-icon-box" data-before="&#x<?php echo esc_attr($dashicons_selector) ?>"></span>
                <?php
                if ($material_icons_selector !== '') {
                    echo '<span class="material-icon-box material-icons" style="display:none" id="material-icon-box" data-before="&#x' .
                        esc_html($material_icons_selector) . '"></span>';
                } else {
                    echo '<span class="material-icon-box material-icons" style="display:none" id="material-icon-box"></span>';
                }
                ?>
                <input id="wplp_choose_icon_btn" class="btn wplp_choose_icon_btn" type="button" value="<?php esc_html_e('Choose an icon', 'wp-latest-posts-addon') ?>" />
                <input type="button" class="btn wplp_remove_icon_btn" value="<?php esc_html_e('Remove icon', 'wp-latest-posts-addon') ?>" />
            </div>
            <div class="wplp-addon-icon-style">
                <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Set icon color', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Icon Color', 'wp-latest-posts-addon') ?></label>
                <div class="wplp-pick-color" id="wplp_addon_icon_color" data-id="wplp_addon_icon_color">
                    <input name="wplp_addon_icon_color" class="wplp_colorPicker"
                           value="<?php echo esc_html(htmlspecialchars(isset($settings['addon_icon_color']) ? $settings['addon_icon_color'] : '#ffffff')) ?>"/>
                </div>
            </div>
            <div class="wplp-addon-icon-style wplp-icon-background">
                <label class="settings-wrapper-title tooltip" title="<?php esc_html_e('Set icon background color', 'wp-latest-posts-addon') ?>"><?php esc_html_e('Icon Background Color', 'wp-latest-posts-addon') ?></label>
                <div class="wplp-pick-color" id="wplp_addon_bg_icon_color" data-id="wplp_addon_bg_icon_color">
                    <input name="wplp_addon_bg_icon_color" class="wplp_colorPicker"
                           value="<?php echo esc_html(htmlspecialchars(isset($settings['addon_bg_icon_color']) ? $settings['addon_bg_icon_color'] : 'transparent')) ?>"/>
                </div>
            </div>
        </div>
        </div>
        <div class="popUp" id="iconlist">
            <span class="wplp-close">×</span>
            <div class="wplp-dashicons-list">
                <h4>Admin Menu</h4>
                <span alt="f333" class="dashicons dashicons-menu"></span>
                <span alt="f319" class="dashicons dashicons-admin-site"></span>
                <span alt="f226" class="dashicons dashicons-dashboard"></span>
                <span alt="f109" class="dashicons dashicons-admin-post"></span>
                <span alt="f104" class="dashicons dashicons-admin-media"></span>
                <span alt="f103" class="dashicons dashicons-admin-links"></span>
                <span alt="f105" class="dashicons dashicons-admin-page"></span>
                <span alt="f101" class="dashicons dashicons-admin-comments"></span>
                <span alt="f100" class="dashicons dashicons-admin-appearance"></span>
                <span alt="f106" class="dashicons dashicons-admin-plugins"></span>
                <span alt="f110" class="dashicons dashicons-admin-users"></span>
                <span alt="f107" class="dashicons dashicons-admin-tools"></span>
                <span alt="f108" class="dashicons dashicons-admin-settings"></span>
                <span alt="f112" class="dashicons dashicons-admin-network"></span>
                <span alt="f102" class="dashicons dashicons-admin-home"></span>
                <span alt="f111" class="dashicons dashicons-admin-generic"></span>
                <span alt="f148" class="dashicons dashicons-admin-collapse"></span>
                <span alt="f536" class="dashicons dashicons-filter"></span>
                <span alt="f540" class="dashicons dashicons-admin-customizer"></span>
                <span alt="f541" class="dashicons dashicons-admin-multisite"></span>
                <h4>Welcome Screen</h4>
                <span alt="f119" class="dashicons dashicons-welcome-write-blog"></span>
                <span alt="f113" class="dashicons dashicons-welcome-add-page"></span>
                <span alt="f115" class="dashicons dashicons-welcome-view-site"></span>
                <span alt="f116" class="dashicons dashicons-welcome-widgets-menus"></span>
                <span alt="f117" class="dashicons dashicons-welcome-comments"></span>
                <span alt="f118" class="dashicons dashicons-welcome-learn-more"></span>
                <h4>Post Formats</h4>
                <span alt="f123" class="dashicons dashicons-format-aside"></span>
                <span alt="f128" class="dashicons dashicons-format-image"></span>
                <span alt="f161" class="dashicons dashicons-format-gallery"></span>
                <span alt="f126" class="dashicons dashicons-format-video"></span>
                <span alt="f130" class="dashicons dashicons-format-status"></span>
                <span alt="f122" class="dashicons dashicons-format-quote"></span>
                <span alt="f125" class="dashicons dashicons-format-chat"></span>
                <span alt="f127" class="dashicons dashicons-format-audio"></span>
                <span alt="f306" class="dashicons dashicons-camera"></span>
                <span alt="f232" class="dashicons dashicons-images-alt"></span>
                <span alt="f233" class="dashicons dashicons-images-alt2"></span>
                <span alt="f234" class="dashicons dashicons-video-alt"></span>
                <span alt="f235" class="dashicons dashicons-video-alt2"></span>
                <span alt="f236" class="dashicons dashicons-video-alt3"></span>
                <h4>Media</h4>
                <span alt="f501" class="dashicons dashicons-media-archive"></span>
                <span alt="f500" class="dashicons dashicons-media-audio"></span>
                <span alt="f499" class="dashicons dashicons-media-code"></span>
                <span alt="f498" class="dashicons dashicons-media-default"></span>
                <span alt="f497" class="dashicons dashicons-media-document"></span>
                <span alt="f496" class="dashicons dashicons-media-interactive"></span>
                <span alt="f495" class="dashicons dashicons-media-spreadsheet"></span>
                <span alt="f491" class="dashicons dashicons-media-text"></span>
                <span alt="f490" class="dashicons dashicons-media-video"></span>
                <span alt="f492" class="dashicons dashicons-playlist-audio"></span>
                <span alt="f493" class="dashicons dashicons-playlist-video"></span>
                <span alt="f522" class="dashicons dashicons-controls-play"></span>
                <span alt="f523" class="dashicons dashicons-controls-pause"></span>
                <span alt="f519" class="dashicons dashicons-controls-forward"></span>
                <span alt="f517" class="dashicons dashicons-controls-skipforward"></span>
                <span alt="f518" class="dashicons dashicons-controls-back"></span>
                <span alt="f516" class="dashicons dashicons-controls-skipback"></span>
                <span alt="f515" class="dashicons dashicons-controls-repeat"></span>
                <span alt="f521" class="dashicons dashicons-controls-volumeon"></span>
                <span alt="f520" class="dashicons dashicons-controls-volumeoff"></span>
                <h4>Image Editing</h4>
                <span alt="f165" class="dashicons dashicons-image-crop"></span>
                <span alt="f531" class="dashicons dashicons-image-rotate"></span>
                <span alt="f166" class="dashicons dashicons-image-rotate-left"></span>
                <span alt="f167" class="dashicons dashicons-image-rotate-right"></span>
                <span alt="f168" class="dashicons dashicons-image-flip-vertical"></span>
                <span alt="f169" class="dashicons dashicons-image-flip-horizontal"></span>
                <span alt="f533" class="dashicons dashicons-image-filter"></span>
                <span alt="f171" class="dashicons dashicons-undo"></span>
                <span alt="f172" class="dashicons dashicons-redo"></span>
                <h4>TinyMCE</h4>

                <span alt="f200" class="dashicons dashicons-editor-bold"></span>
                <span alt="f201" class="dashicons dashicons-editor-italic"></span>
                <span alt="f203" class="dashicons dashicons-editor-ul"></span>
                <span alt="f204" class="dashicons dashicons-editor-ol"></span>
                <span alt="f205" class="dashicons dashicons-editor-quote"></span>
                <span alt="f206" class="dashicons dashicons-editor-alignleft"></span>
                <span alt="f207" class="dashicons dashicons-editor-aligncenter"></span>
                <span alt="f208" class="dashicons dashicons-editor-alignright"></span>
                <span alt="f209" class="dashicons dashicons-editor-insertmore"></span>
                <span alt="f210" class="dashicons dashicons-editor-spellcheck"></span>
                <span alt="f211" class="dashicons dashicons-editor-expand"></span>
                <span alt="f506" class="dashicons dashicons-editor-contract"></span>
                <span alt="f212" class="dashicons dashicons-editor-kitchensink"></span>
                <span alt="f213" class="dashicons dashicons-editor-underline"></span>
                <span alt="f214" class="dashicons dashicons-editor-justify"></span>
                <span alt="f215" class="dashicons dashicons-editor-textcolor"></span>
                <span alt="f216" class="dashicons dashicons-editor-paste-word"></span>
                <span alt="f217" class="dashicons dashicons-editor-paste-text"></span>
                <span alt="f218" class="dashicons dashicons-editor-removeformatting"></span>
                <span alt="f219" class="dashicons dashicons-editor-video"></span>
                <span alt="f220" class="dashicons dashicons-editor-customchar"></span>
                <span alt="f221" class="dashicons dashicons-editor-outdent"></span>
                <span alt="f222" class="dashicons dashicons-editor-indent"></span>
                <span alt="f223" class="dashicons dashicons-editor-help"></span>
                <span alt="f224" class="dashicons dashicons-editor-strikethrough"></span>
                <span alt="f225" class="dashicons dashicons-editor-unlink"></span>
                <span alt="f320" class="dashicons dashicons-editor-rtl"></span>
                <span alt="f474" class="dashicons dashicons-editor-break"></span>
                <span alt="f475" class="dashicons dashicons-editor-code"></span>
                <span alt="f476" class="dashicons dashicons-editor-paragraph"></span>
                <span alt="f535" class="dashicons dashicons-editor-table"></span>
                <h4>Posts Screen</h4>
                <span alt="f135" class="dashicons dashicons-align-left"></span>
                <span alt="f136" class="dashicons dashicons-align-right"></span>
                <span alt="f134" class="dashicons dashicons-align-center"></span>
                <span alt="f138" class="dashicons dashicons-align-none"></span>
                <span alt="f160" class="dashicons dashicons-lock"></span>
                <span alt="f528" class="dashicons dashicons-unlock"></span>>
                <span alt="f145" class="dashicons dashicons-calendar"></span>
                <span alt="f508" class="dashicons dashicons-calendar-alt"></span>
                <span alt="f177" class="dashicons dashicons-visibility"></span>
                <span alt="f530" class="dashicons dashicons-hidden"></span>
                <span alt="f173" class="dashicons dashicons-post-status"></span>
                <span alt="f464" class="dashicons dashicons-edit"></span>
                <span alt="f182" class="dashicons dashicons-trash"></span>
                <span alt="f537" class="dashicons dashicons-sticky"></span>
                <h4>Sorting</h4>
                <span alt="f504" class="dashicons dashicons-external"></span>
                <span alt="f142" class="dashicons dashicons-arrow-up"></span>
                <span alt="f140" class="dashicons dashicons-arrow-down"></span>
                <span alt="f139" class="dashicons dashicons-arrow-right"></span>
                <span alt="f141" class="dashicons dashicons-arrow-left"></span>
                <span alt="f342" class="dashicons dashicons-arrow-up-alt"></span>
                <span alt="f346" class="dashicons dashicons-arrow-down-alt"></span>
                <span alt="f344" class="dashicons dashicons-arrow-right-alt"></span>
                <span alt="f340" class="dashicons dashicons-arrow-left-alt"></span>
                <span alt="f343" class="dashicons dashicons-arrow-up-alt2"></span>
                <span alt="f347" class="dashicons dashicons-arrow-down-alt2"></span>
                <span alt="f345" class="dashicons dashicons-arrow-right-alt2"></span>
                <span alt="f341" class="dashicons dashicons-arrow-left-alt2"></span>
                <span alt="f156" class="dashicons dashicons-sort"></span>
                <span alt="f229" class="dashicons dashicons-leftright"></span>
                <span alt="f503" class="dashicons dashicons-randomize"></span>
                <span alt="f163" class="dashicons dashicons-list-view"></span>
                <span alt="f164" class="dashicons dashicons-exerpt-view"></span>
                <span alt="f509" class="dashicons dashicons-grid-view"></span>
                <span alt="f545" class="dashicons dashicons-move"></span>
                <h4>Social</h4>
                <span alt="f237" class="dashicons dashicons-share"></span>
                <span alt="f240" class="dashicons dashicons-share-alt"></span>
                <span alt="f242" class="dashicons dashicons-share-alt2"></span>
                <span alt="f301" class="dashicons dashicons-twitter"></span>
                <span alt="f303" class="dashicons dashicons-rss"></span>
                <span alt="f465" class="dashicons dashicons-email"></span>
                <span alt="f466" class="dashicons dashicons-email-alt"></span>
                <span alt="f304" class="dashicons dashicons-facebook"></span>
                <span alt="f305" class="dashicons dashicons-facebook-alt"></span>
                <span alt="f462" class="dashicons dashicons-googleplus"></span>
                <span alt="f325" class="dashicons dashicons-networking"></span>
                <h4>WordPress.org Specific: Jobs, Profiles, WordCamps</h4>
                <span alt="f308" class="dashicons dashicons-hammer"></span>
                <span alt="f309" class="dashicons dashicons-art"></span>
                <span alt="f310" class="dashicons dashicons-migrate"></span>
                <span alt="f311" class="dashicons dashicons-performance"></span>
                <span alt="f483" class="dashicons dashicons-universal-access"></span>
                <span alt="f507" class="dashicons dashicons-universal-access-alt"></span>
                <span alt="f486" class="dashicons dashicons-tickets"></span>
                <span alt="f484" class="dashicons dashicons-nametag"></span>
                <span alt="f481" class="dashicons dashicons-clipboard"></span>
                <span alt="f487" class="dashicons dashicons-heart"></span>
                <span alt="f488" class="dashicons dashicons-megaphone"></span>
                <span alt="f489" class="dashicons dashicons-schedule"></span>
                <h4>Products</h4>
                <span alt="f120" class="dashicons dashicons-wordpress"></span>
                <span alt="f324" class="dashicons dashicons-wordpress-alt"></span>
                <span alt="f157" class="dashicons dashicons-pressthis"></span>
                <span alt="f463" class="dashicons dashicons-update"></span>
                <span alt="f180" class="dashicons dashicons-screenoptions"></span>
                <span alt="f348" class="dashicons dashicons-info"></span>
                <span alt="f174" class="dashicons dashicons-cart"></span>
                <span alt="f175" class="dashicons dashicons-feedback"></span>
                <span alt="f176" class="dashicons dashicons-cloud"></span>
                <span alt="f326" class="dashicons dashicons-translation"></span>
                <h4>Taxonomies</h4>
                <span alt="f323" class="dashicons dashicons-tag"></span>
                <span alt="f318" class="dashicons dashicons-category"></span>
                <h4>Widgets</h4>
                <span alt="f480" class="dashicons dashicons-archive"></span>
                <span alt="f479" class="dashicons dashicons-tagcloud"></span>
                <span alt="f478" class="dashicons dashicons-text"></span>
                <h4>Notifications</h4>
                <span alt="f147" class="dashicons dashicons-yes"></span>
                <span alt="f158" class="dashicons dashicons-no"></span>
                <span alt="f335" class="dashicons dashicons-no-alt"></span>
                <span alt="f132" class="dashicons dashicons-plus"></span>
                <span alt="f502" class="dashicons dashicons-plus-alt"></span>
                <span alt="f460" class="dashicons dashicons-minus"></span>
                <span alt="f153" class="dashicons dashicons-dismiss"></span>
                <span alt="f159" class="dashicons dashicons-marker"></span>
                <span alt="f155" class="dashicons dashicons-star-filled"></span>
                <span alt="f459" class="dashicons dashicons-star-half"></span>
                <span alt="f154" class="dashicons dashicons-star-empty"></span>
                <span alt="f227" class="dashicons dashicons-flag"></span>
                <span alt="f534" class="dashicons dashicons-warning"></span>
                <h4>Misc</h4>
                <span alt="f230" class="dashicons dashicons-location"></span>
                <span alt="f231" class="dashicons dashicons-location-alt"></span>
                <span alt="f178" class="dashicons dashicons-vault"></span>
                <span alt="f332" class="dashicons dashicons-shield"></span>
                <span alt="f334" class="dashicons dashicons-shield-alt"></span>
                <span alt="f468" class="dashicons dashicons-sos"></span>
                <span alt="f179" class="dashicons dashicons-search"></span>
                <span alt="f181" class="dashicons dashicons-slides"></span>
                <span alt="f183" class="dashicons dashicons-analytics"></span>
                <span alt="f184" class="dashicons dashicons-chart-pie"></span>
                <span alt="f185" class="dashicons dashicons-chart-bar"></span>
                <span alt="f238" class="dashicons dashicons-chart-line"></span>
                <span alt="f239" class="dashicons dashicons-chart-area"></span>
                <span alt="f307" class="dashicons dashicons-groups"></span>
                <span alt="f338" class="dashicons dashicons-businessman"></span>
                <span alt="f336" class="dashicons dashicons-id"></span>
                <span alt="f337" class="dashicons dashicons-id-alt"></span>
                <span alt="f312" class="dashicons dashicons-products"></span>
                <span alt="f313" class="dashicons dashicons-awards"></span>
                <span alt="f314" class="dashicons dashicons-forms"></span>
                <span alt="f473" class="dashicons dashicons-testimonial"></span>
                <span alt="f322" class="dashicons dashicons-portfolio"></span>
                <span alt="f330" class="dashicons dashicons-book"></span>
                <span alt="f331" class="dashicons dashicons-book-alt"></span>
                <span alt="f316" class="dashicons dashicons-download"></span>
                <span alt="f317" class="dashicons dashicons-upload"></span>
                <span alt="f321" class="dashicons dashicons-backup"></span>
                <span alt="f469" class="dashicons dashicons-clock"></span>
                <span alt="f339" class="dashicons dashicons-lightbulb"></span>
                <span alt="f482" class="dashicons dashicons-microphone"></span>
                <span alt="f472" class="dashicons dashicons-desktop"></span>
                <span alt="f547" class="dashicons dashicons-laptop"></span>
                <span alt="f471" class="dashicons dashicons-tablet"></span>
                <span alt="f470" class="dashicons dashicons-smartphone"></span>
                <span alt="f525" class="dashicons dashicons-phone"></span>
                <span alt="f510" class="dashicons dashicons-index-card"></span>
                <span alt="f511" class="dashicons dashicons-carrot"></span>
                <span alt="f512" class="dashicons dashicons-building"></span>
                <span alt="f513" class="dashicons dashicons-store"></span>
                <span alt="f514" class="dashicons dashicons-album"></span>
                <span alt="f527" class="dashicons dashicons-palmtree"></span>
                <span alt="f524" class="dashicons dashicons-tickets-alt"></span>
                <span alt="f526" class="dashicons dashicons-money"></span>
                <span alt="f526" class="dashicons dashicons-money"></span>
                <span alt="f529" class="dashicons dashicons-thumbs-up"></span>
                <span alt="f542" class="dashicons dashicons-thumbs-down"></span>
                <span alt="f538" class="dashicons dashicons-layout"></span>
                <span alt="f546" class="dashicons dashicons-paperclip"></span>
            </div>

            <div class="wplp-material-icons-list">
                <?php
                $fn = fopen(WPLPADDON_PLUGIN_PATH . '/inc/material-icons-list.txt', 'r');
                while (!feof($fn)) {
                    $result = fgets($fn);
                    $material_icon = explode(' ', $result);
                    ?>
                    <i data-content="<?php echo esc_attr(trim($material_icon[1])) ?>"
                       data-class="<?php echo esc_attr(trim($material_icon[0])) ?>"
                       class="material-icons"><?php echo esc_html($material_icon[0]) ?></i>
                    <?php
                }
                ?>
            </div>
        </div>
        </div>
        <div class="clearfix"></div>
        <?php
    }

    /**
     * Display order by: modified for post content source
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayArticleOrderBy($settings)
    {
        if (isset($settings['cat_post_source_order'])) {
            $source_order_selected[$settings['cat_post_source_order']] = ' checked="checked"';
        }
        ?>
        <li><input type="radio" name="wplp_cat_post_source_order" id="cat_post_source_order5" value="modified"
                   class="ju-radiobox"
                <?php echo(isset($source_order_selected['modified']) ? esc_html($source_order_selected['modified']) : '') ?> />
            <label for="cat_post_source_order5"
                   class="radio-label"><?php esc_html_e('Last updated', 'wp-latest-posts-addon') ?></label></li>
        <?php
    }

    /**
     * Display order by: modified for page content source
     *
     * @param array $settings List of settings
     *
     * @return void
     */
    public function contentsourceDisplayPageOrderBy($settings)
    {
        if (isset($settings['pg_source_order'])) {
            $source_page_order_selected[$settings['pg_source_order']] = ' checked="checked"';
        }
        ?>
        <li><input type="radio" name="wplp_pg_source_order" id="pg_source_order5" value="modified"
                   class="ju-radiobox"
                <?php echo(isset($source_page_order_selected['modified']) ? esc_html($source_page_order_selected['modified']) : '') ?> />
            <label for="pg_source_order5"
                   class="radio-label"><?php esc_html_e('Last updated', 'wp-latest-posts-addon') ?></label></li>
        <?php
    }
}

new WPLPAddonViewAdmin();
