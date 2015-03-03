<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Size Filter Widget helper functions
 *
 * @author   Jason Judge
 * @category Widgets
 * @package  WooCommerce/Widgets
 * @version  0.1.0
 * @extends  WC_Widget
 */
class WCSizeFilterHelper
{
    /**
     * Format decimals as fractions.
     * If utf8 is true, return UTF-8 fraction characters, otherwise return an ASCII fraction.
     */
    public static function format_dimension_fraction($value, $utf8 = true, $separator = '-')
    {
        // Get the whole and fractional parts.
        $whole = floor($value);
        $fraction = (float)$value - $whole;

        if ($fraction == 0) {
            // No fraction; return unchanged.
            return $value;
        }

        switch($fraction) {
            case 0.125:
                $fraction = '1/8';
                $fraction_urf8 = '⅛';
                break;
            case 0.25:
                $fraction = '1/4';
                $fraction_urf8 = '¼';
                break;
            case 0.375:
                $fraction = '3/8';
                $fraction_urf8 = '⅜';
                break;
            case 0.5:
                $fraction = '1/2';
                $fraction_urf8 = '½';
                break;
            case 0.625:
                $fraction = '5/8';
                $fraction_urf8 = '⅝';
                break;
            case 0.75:
                $fraction = '3/4';
                $fraction_urf8 = '¾';
                break;
            case 0.875:
                $fraction = '7/8';
                $fraction_urf8 = '⅞';
                break;
            default:
                // No fractions we recognise, so return the original unchanged.
                return $value;
        }

        return (string)$whole . ($utf8 ? $fraction_urf8 : $separator . $fraction);
    }
}
