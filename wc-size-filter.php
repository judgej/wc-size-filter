<?php
/*
Plugin Name: WC Size Filter
Plugin URI:  https://github.com/academe/TBC
Description: Provides a filter widget for selecting from sizes of product.
Version:     0.1
Author:      Jason Judge
Author URI:  http://academe.co.uk
*/

/**
 * Limitations at the moment:
 * - Does not consider ranges of sizes.
 * - Does not look at sizes of variations.
 */

/**
 * Determine whether the dependencies for this plugin have been met.
 */
function wc_size_filter_dependencies_met()
{
    // WooCommerce must be installed and enabled.
    return true;
}

/**
 * Register the widget.
 * We cannot load the widget class until widgets are being registered, 
 * because it extends WC, and so all the plugins need to be loaded first.
 */
function wc_size_filter_register_widget()
{
    if ( class_exists( 'WC_Widget' ) ) {
        require_once(__DIR__ . '/classes/WCSizeFilterWidget.php');

        register_widget('WCSizeFilterWidget');
    }

}

if (wc_size_filter_dependencies_met()) :
    require_once(__DIR__ . '/classes/WCSizeFilter.php' );

    // Register the filter widget.
    add_action('widgets_init', 'wc_size_filter_register_widget', 15);

    // The filter that applies to each product listing.
    add_filter('loop_shop_post_in', 'WCSizeFilter::size_filter');

endif; // Dependencies met
