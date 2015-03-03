<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once(__DIR__ . '/WCSizeFilterHelper.php');

/**
 * Size Filter Widget and related functions
 *
 * Generates a size filter widget to filter products by size.
 *
 * @author   Jason Judge
 * @category Widgets
 * @package  WooCommerce/Widgets
 * @version  0.1.0
 * @extends  WC_Widget
 */
class WCSizeFilterWidget extends WC_Widget {
    /**
     * Constructor
     */
    public function __construct() {
        $this->widget_cssclass    = 'woocommerce widget_size_filter';
        $this->widget_description = __( 'Shows a size filter in a widget which lets you narrow down the list of shown products when viewing product categories.', 'woocommerce' );
        $this->widget_id          = 'woocommerce_size_filter';
        $this->widget_name        = __( 'WooCommerce Size Filter', 'woocommerce' );
        $this->settings           = array(
            'title'  => array(
                'type'  => 'text',
                'std'   => __( 'Filter by size', 'woocommerce' ),
                'label' => __( 'Title', 'woocommerce' )
            )
        );

        parent::__construct();
    }

    /**
     * widget function.
     *
     * @see WP_Widget
     *
     * @param array $args
     * @param array $instance
     *
     * @return void
     */
    public function widget( $args, $instance ) {
        global $_chosen_attributes, $wpdb, $wp;

        if ( ! is_post_type_archive( 'product' ) && ! is_tax( get_object_taxonomies( 'product' ) ) ) {
            return;
        }

        if ( sizeof( WC()->query->unfiltered_product_ids ) == 0 ) {
            return; // None shown - return
        }

        $length = isset( $_GET['length'] ) ? esc_attr( $_GET['length'] ) : '';
        $width = isset( $_GET['width'] ) ? esc_attr( $_GET['width'] ) : '';
        $height = isset( $_GET['height'] ) ? esc_attr( $_GET['height'] ) : '';

        // This only works if WC_Query::wc-price-slider() is run in the init action.
        // That check specifically for woocommerce_price_filter widget to be active.
        // TODO: we will want to enqueue an auto-submit-on-change script.
        //wp_enqueue_script( 'wc-price-slider' );

        // Remember current filters/search
        $fields = '';

        if ( get_search_query() ) {
            $fields .= '<input type="hidden" name="s" value="' . get_search_query() . '" />';
        }

        if ( ! empty( $_GET['post_type'] ) ) {
            $fields .= '<input type="hidden" name="post_type" value="' . esc_attr( $_GET['post_type'] ) . '" />';
        }

        if ( ! empty ( $_GET['product_cat'] ) ) {
            $fields .= '<input type="hidden" name="product_cat" value="' . esc_attr( $_GET['product_cat'] ) . '" />';
        }

        if ( ! empty( $_GET['product_tag'] ) ) {
            $fields .= '<input type="hidden" name="product_tag" value="' . esc_attr( $_GET['product_tag'] ) . '" />';
        }

        if ( ! empty( $_GET['orderby'] ) ) {
            $fields .= '<input type="hidden" name="orderby" value="' . esc_attr( $_GET['orderby'] ) . '" />';
        }

        if ( $_chosen_attributes ) {
            foreach ( $_chosen_attributes as $attribute => $data ) {
                $taxonomy_filter = 'filter_' . str_replace( 'pa_', '', $attribute );

                $fields .= '<input type="hidden" name="' . esc_attr( $taxonomy_filter ) . '" value="' . esc_attr( implode( ',', $data['terms'] ) ) . '" />';

                if ( 'or' == $data['query_type'] ) {
                    $fields .= '<input type="hidden" name="' . esc_attr( str_replace( 'pa_', 'query_type_', $attribute ) ) . '" value="or" />';
                }
            }
        }

        // TODO: here we fetch the lists of measurements for width/length/height for use in the widget dropdowns.
        // The lists should take into account any restrictions already selected.

        $layered_nav_in_operation = (0 !== sizeof(WC()->query->layered_nav_product_ids));

        // Get the list of lengths, taking the existing width and height into account, if set.

        // How we will be selecting which sizes are available for the selection boxes.
        $combinations = array(
            'length' => array('4' => 'width', '5' => 'height'),
            'width' => array('3' => 'length', '5' => 'height'),
            'height' => array('3' => 'length', '4' => 'width'),
        );

        $lists = array();
        foreach($combinations as $base_dim => $related_dims) {
            $lists[$base_dim] = array();

            $sql = '
                SELECT DISTINCT postmeta1.meta_value
                FROM %2$s AS postmeta1

                JOIN %1$s
                ON %1$s.ID = postmeta1.post_id
                AND %1$s.post_type IN (\'product\')
                AND %1$s.post_status = \'publish\'
            ';

            // Join to any other dimensions that have been set, to filter the list
            // of possible values further.
            foreach($related_dims as $dim_number => $dim_name) {
                if (!empty($$dim_name)) {
                    $sql .= '
                        JOIN %2$s AS postmeta'.$dim_number.'
                        ON %1$s.ID = postmeta'.$dim_number.'.post_id
                        AND postmeta'.$dim_number.'.meta_key = \'_'.$dim_name.'\'
                        AND postmeta'.$dim_number.'.meta_value = %'.$dim_number.'$s
                    ';
                }
            }

            $sql .= '
                WHERE postmeta1.meta_key = \'_'.$base_dim.'\'
            ';

            // Add in the other layered nav filters if needed.
            if ($layered_nav_in_operation) {
                $sql .= '
                    AND (
                        %1$s.ID IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
                        OR (
                            %1$s.post_parent IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')
                            AND %1$s.post_parent != 0
                        )
                    )
                ';
            }

            $dim_results = $wpdb->get_results(
                $wpdb->prepare($sql, $wpdb->posts, $wpdb->postmeta, $length, $width, $height),
                OBJECT_K
            );

            if ($dim_results) {
                foreach($dim_results as $dim_result) {
                    // TODO: transform the labels using a filter, defaulting to this helper.
                    // That way the filter can be removed and replaced as needed.
                    $lists[$base_dim][$dim_result->meta_value] = WCSizeFilterHelper::format_dimension_fraction($dim_result->meta_value);
                }
            }
            ksort($lists[$base_dim], SORT_NUMERIC);
        }

        $this->widget_start( $args, $instance );

        // Take the page number out of the URL, so we go back to the beginning
        // if the filter is changed.

        if ( '' == get_option( 'permalink_structure' ) ) {
            $form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
        } else {
            $form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );
        }

        // Here display the dropdowns. Changing one should refresh the page.

        $dropdowns = array();

        foreach($lists as $list_name => $list_values) {
            $dropdowns[$list_name] = '<div>'.$list_name.':<select name="'.$list_name.'">';
            $dropdowns[$list_name] .= '<option value="">- Any -</option>';
            foreach($list_values as $key => $value) {
                if ($key == $$list_name) {
                    $dropdowns[$list_name] .= '<option value="'.$key.'" selected="selected">'.$value.'</option>';
                } else {
                    $dropdowns[$list_name] .= '<option value="'.$key.'">'.$value.'</option>';
                }
            }
            $dropdowns[$list_name] .= '</select></div>';
        }

        // TODO: move this to a template.

        echo '<form method="get" action="' . esc_url( $form_action ) . '">
            <div class="price_slider_wrapper">
                <div class="price_slider" style="display:none;"></div>
                <div class="price_slider_amount">
                    ' . implode('', $dropdowns) . '
                    <button type="submit" class="button">' . __( 'Filter', 'woocommerce' ) . '</button>
                    ' . $fields . '
                    <div class="clear"></div>
                </div>
            </div>
        </form>';

        $this->widget_end( $args );
    }
}
