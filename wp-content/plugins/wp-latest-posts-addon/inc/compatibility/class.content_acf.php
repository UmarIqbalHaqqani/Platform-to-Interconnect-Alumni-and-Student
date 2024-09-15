<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPLPAddonContentACF
 */
class WPLPAddonContentACF
{
    /**
     * WPLPAddonContentACF constructor.
     */
    public function __construct()
    {
        if (is_admin()) {
            add_action('wplp_display_advanced_custom_fields', array($this, 'displayAdvancedCustomFields'), 10, 2);
            add_action('wp_ajax_getFieldsCustomAdvanced', array($this, 'displayACFFieldsAjax'));
            add_filter('wplp_get_rules_custom_fields', array($this, 'getRulesCustomFields'), 10, 1);
        } else {
            add_filter('wplp_get_fields', array($this, 'getFields'), 10, 2);
        }
    }

    /**
     * Page or post or custompost
     *
     * @param array  $settings    List of settings
     * @param string $source_type Type of source
     *
     * @return void
     */
    public static function displayAdvancedCustomFields($settings, $source_type)
    {
        wp_enqueue_script(
            'ajaxcustomfieldstype',
            WPLPADDON_PLUGIN_DIR . '/js/ajaxcustomfieldstype.js',
            array('jquery'),
            WPLP_ADDON_VERSION,
            true
        );
        if (!isset($settings['advanced_custom_fields_' . $source_type])
            || empty($settings['advanced_custom_fields_' . $source_type])
        ) {
            $settings['advanced_custom_fields_' . $source_type] = '';
        }

        $rule_customs = self::getRulesCustomFields($source_type);
        echo '<div class="custom_field advanced-custom-field settings-wrapper-field">';
        echo '<div class="group_field input-field input-select wplp-acf-field"> 
			<label for="custom_field_select_' . esc_html($source_type) . '" class="page_cb settings-wrapper-title">' .
             esc_html__('Select an ACF field group', 'wp-latest-posts-addon') . ' : </label>		
			<select data-type="' . esc_attr($source_type) . '" id="custom_field_select_' . esc_html($source_type) .
             '" class="custom_field_select wplp-font-style width-30" name="wplp_advanced_custom_fields_' . esc_html($source_type) . '">
                                        <option value="default">' .
             esc_html__('Select an ACF field group', 'wp-latest-posts-addon') . '</option>';
        foreach ($rule_customs as $k => $rule_custom) {
            echo '<option value="' . esc_html($rule_custom['ID']) . '" ' . selected($settings['advanced_custom_fields_' . $source_type], $rule_custom['ID']) . ' > ' .
                 esc_html($rule_custom['title']) . ' </option>';
        }
        echo '</select></div>';

        self::displayACFFields($settings, $source_type);

        echo '<div class="field input-field input-select wplp-acf-field">
    <label for="custom_field_title" class="coltab settings-wrapper-title">' .
             esc_html__('Display custom field title', 'wp-latest-posts-addon') . '</label>' .
             '<select name="wplp_display_custom_field_title" class="display_custom_field_title wplp-font-style width-30">' .
             '<option value="1" ' .
             ((isset($settings['display_custom_field_title']) &&
               $settings['display_custom_field_title'] === '1') ? 'selected' : '') .
             ' class="short-text">' . esc_html__('Yes', 'wp-latest-posts-addon') . '</option>' .
             '<option value="0" ' .
             ((isset($settings['display_custom_field_title']) &&
               $settings['display_custom_field_title'] === '0') ? 'selected' : '') .
             ' class="short-text">' . esc_html__('No', 'wp-latest-posts-addon') . '</option>' .
             '</select></div>';
        echo '</div><hr>';
    }

    /**
     * Display post form advanced custom post for ajax
     *
     * @return void
     */
    public static function displayACFFieldsAjax()
    {
        global $settings;
        //phpcs:disable WordPress.Security.NonceVerification.Missing -- This function is called via ajax and class method, this is not an action request there is no need to add a nonce
        if (isset($_POST['source_type'])) {
            $source_type = $_POST['source_type'];
            if (isset($_POST['fieldType'])) {
                $parent = $_POST['fieldType'];
            } else {
                $parent = $settings['advanced_custom_fields_' . $source_type];
            }
            //phpcs:enable

            self::displayACF($settings, $source_type, $parent);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                /* special ajax here */
                wp_die();
            }
        }
    }

    /**
     * Display post form advanced custom post
     *
     * @param array  $settings    Settings
     * @param string $source_type Source type
     *
     * @return void
     */
    public static function displayACFFields($settings, $source_type)
    {
        $parent = $settings['advanced_custom_fields_' . $source_type];
        self::displayACF($settings, $source_type, $parent);
    }

    /**
     * Display post form advanced custom post
     *
     * @param array  $settings    Settings
     * @param string $source_type Source type
     * @param string $parent      Post parent
     *
     * @return void
     */
    public static function displayACF($settings, $source_type, $parent)
    {
        $custom_option = (!empty($settings['advanced_fields_taxonomy_' . $source_type]) && is_array($settings['advanced_fields_taxonomy_' . $source_type])) ? $settings['advanced_fields_taxonomy_' . $source_type] : array('all_fields');

        $fields = get_posts(array(
            'posts_per_page' => - 1,
            'post_type'      => 'acf-field',
            'post_parent'    => (int) $parent
        ));
        if (count($fields) > 0) {
            echo '<div id="taxonomySelector' . esc_html($source_type) . '" class="field input-field wplp-acf-field">
		    <label for="custom_fields_select_tax_' . esc_html($source_type) . '" class="page_cb settings-wrapper-title">' .
                 esc_html__('Select specific fields (optional)', 'wp-latest-posts-addon') . ' : </label>
			<select id="custom_fields_select_tax_' . esc_html($source_type) .
                 '" class="wplp-font-style width-30 custom_fields_select_field_multitple" multiple name="wplp_advanced_fields_taxonomy_' . esc_html($source_type) . '[]">';
            if (in_array('all_fields', $custom_option)) {
                echo '<option selected value="all_fields">' . esc_html__('All fields', 'wp-latest-posts-addon') . '</option>';
            } else {
                echo '<option value="all_fields">' . esc_html__('All fields', 'wp-latest-posts-addon') . '</option>';
            }

            foreach ($fields as $field) {
                if (in_array($field->ID, $custom_option)) {
                    echo '<option selected value="' . esc_attr($field->ID) . '"> ' . esc_html($field->post_title) . ' </option>';
                } else {
                    echo '<option value="' . esc_attr($field->ID) . '"> ' . esc_html($field->post_title) . ' </option>';
                }
            }
            echo '</select></div>'; //field
        }
    }

    /**
     * Get rules on advanced custom post
     *
     * @param string $post_type Post type
     *
     * @return array
     */
    public static function getRulesCustomFields($post_type)
    {
        $groups = acf_get_field_groups(array('post_type' => $post_type));
        return $groups;
    }

    /**
     * Return field from postmeta
     *
     * @param array   $fields  Field name
     * @param boolean $post_id Id of post
     *
     * @return array
     */
    public static function getFields($fields = array(), $post_id = false)
    {
        // global
        global $wpdb;
        // loaded by PHP already?
        if (!empty($fields)) {
            return $fields;
        }
        // get field from postmeta
        $rows = $wpdb->get_results($wpdb->prepare('SELECT meta_key FROM ' . $wpdb->postmeta . ' WHERE post_id = %d AND meta_key LIKE %s', $post_id, 'field_%'), ARRAY_A);

        if (!empty($rows)) {
            foreach ($rows as $k => $row) {
                $get_label = get_post_meta($post_id, $row['meta_key']);
                $fields[]  = $get_label[0];
            }
        }

        // return
        return $fields;
    }
}

new WPLPAddonContentACF();
