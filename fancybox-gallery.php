<?php 
/* 
    Plugin Name: wp-Architect Lightbox 
    Description: Fancybox plugin for WP-Architect Theme
    Author: Matthew Ell 
    Version: 2.0 
*/ 

    
/**
 * Load Scripts and Styles
 */
add_action( 'wp_enqueue_scripts', 'wpss_scripts_styles', 999);
function wpss_scripts_styles() {

    if ( !is_admin() ) {

        // enqueue script | @Dependents: jQuery
        wp_enqueue_script('wp_arch_lightbox_scripts', plugins_url('/source/jquery.fancybox.pack.js', __FILE__), array('jquery'), null, true);

        // enqueue script | @Dependents: jQuery & wp_arch_lightbox_scripts
        wp_enqueue_script('wp_arch_lightbox_scripts_init', plugins_url('/source/init.js', __FILE__), array('wp_arch_lightbox_scripts'), null, true);

        // enqueue css
        wp_enqueue_style('wp_arch_lightbox_styles', plugins_url('/source/jquery.fancybox.css', __FILE__), array(), '01', 'all');
    }
}

/**
 * Custom Shortcode
 */

function mre_lightboxShortcode( $args, $content = null ) {
    // Attributes
        extract( shortcode_atts(
            array(
                'thumbnail' => '',
                'fullsize' => '',
                'alt' => '',
                'class' => ''
            ), $args )
        );

    return '<a class="fancybox" href="' . $fullsize . '"><img src="' . $thumbnail . '" alt="' . $alt . '" class="' .$class .'" /></a>';
}
add_shortcode( 'lightbox','mre_lightboxShortcode' );
