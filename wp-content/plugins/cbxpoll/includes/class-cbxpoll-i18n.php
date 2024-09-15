<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    Cbxpoll
 * @subpackage Cbxpoll/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Cbxpoll
 * @subpackage Cbxpoll/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class Cbxpoll_i18n
{


    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {

        load_plugin_textdomain(
            'cbxpoll',
            false,
            dirname(dirname(plugin_basename(__FILE__))).'/languages/'
        );

    }


}
