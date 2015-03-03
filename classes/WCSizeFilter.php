<?php

if ( ! class_exists( 'WPPS_CPT_Example' ) ) :

/**
 * The filter that runs with each WooCommerce product listing.
 * TODO: make it work with any combination of parameters.
 * TODO: support ranges, e.g. width_min, width_max.
 * TODO: if not supporting ranges, see if a meta field filter on WC_Query is more efficient.
 * TODO: hook into standard WC filters so the filter settings can be preserved.
 */
class WCSizeFilter
{
    /**
     * A query filter to handle the size (length/height/depth) filters.
     * This is registered with the "loop_shop_post_in" filter.
     */
    public static function size_filter($filtered_posts = array())
    {
        // OMG - NO! The price filter in core WC selects ALL products that match the
        // price range, then uses the list to filter out the products selected in 
        // the main loop. Let's select everything - twice - then throw half of it away.
        // We will give it a go though...

        global $wpdb;

        // All three dimensions must be provided to filter at all, though they don't all have
        // to be set.
        // TODO: we don't really need this limitation.

        if (isset($_GET['length']) && isset($_GET['width']) && isset($_GET['height'])) {
            $length = floatval($_GET['length']);
            $width = floatval($_GET['width']);
            $height = floatval($_GET['height']);

            $query_join_sql = array();
            $meta_value_index = 3;

            $query_select_sql = 'SELECT DISTINCT ID, post_parent, post_type FROM %1$s';
            $query_where_sql = '
                WHERE post_type IN ( "product", "product_variation" )
                AND post_status = "publish"
            ';

            // For each of the three dimensions, do a join to narrow down the search.

            foreach(array('_length' => $length, '_width' => $width, '_height' => $height) as $meta_key => $meta_value) {
                if (!empty($meta_value)) {
                    $query_join_sql[] = '
                        INNER JOIN %2$s AS meta_data'.$meta_value_index.'
                        ON ID = meta_data'.$meta_value_index.'.post_id
                        AND meta_data'.$meta_value_index.'.meta_key IN ("' . implode( '","', apply_filters( 'woocommerce_length_filter_meta_keys', array( $meta_key ) ) ) . '")
                        AND meta_data'.$meta_value_index.'.meta_value = %'.$meta_value_index.'$s
                    ';
                }
                $meta_value_index++;
            }

            // Only perform the select if there is at least one dimension.

            if (!empty($query_join_sql)) {
                $query_sql = $query_select_sql . implode(' ', $query_join_sql) . $query_where_sql;

                $matched_products_query = apply_filters(
                    'woocommerce_size_filter_results',
                     $wpdb->get_results(
                        $wpdb->prepare(
                            $query_sql,
                            $wpdb->posts,
                            $wpdb->postmeta,
                            $length,
                            $width,
                            $height
                        ),
                        OBJECT_K
                    ),
                    $length,
                    $width,
                    $height
                );

                $matched_products = array();
                if ( $matched_products_query ) {
                    foreach ( $matched_products_query as $product ) {
                        if ( $product->post_type == 'product' ) {
                            $matched_products[] = $product->ID;
                        }
                        if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) ) {
                            $matched_products[] = $product->post_parent;
                        }
                    }
                }

                // Filter the id's
                if ( 0 === sizeof( $filtered_posts ) ) {
                    $filtered_posts = $matched_products;
                } else {
                    $filtered_posts = array_intersect( $filtered_posts, $matched_products );
                }
            }
        }

        return (array)$filtered_posts;
    }
}

endif; // class defined
