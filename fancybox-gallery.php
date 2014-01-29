<?php 
/* 
    Plugin Name: wp-Architect Lightbox 
    Description: Fancybox plugin for WP-Architect Theme
    Author: Matthew Ell 
    Version: 1.0 
*/ 


/**
     * If the option to redirect attachments to their parent is checked, this performs the redirect.
     *
     * An extra check is done for when the attachment has no parent.
     */
// Redirect Image Attachment Urls 
// Edited code from Plugin : Attachment Pages Redirect by Samuel Aguilera 
// http://www.basicwp.com/stop-attachment-pages-indexing-wordpress/

function wpss_attachment_redirect() {  
        global $post;
        if ( is_attachment() && isset($post->post_parent) && is_numeric($post->post_parent) && ($post->post_parent != 0) ) {
            wp_redirect(get_permalink($post->post_parent), 301); // permanent redirect to post/page where image or document was uploaded
            exit;  
        }
    }

add_action('template_redirect', 'wpss_attachment_redirect', 1);

// Custom Gallery Shortcode
// http://wordpress.org/support/topic/edit-gallery-shortcode
// Follow install instructions: http://fancyapps.com/fancybox/

    
    // Hook into enqueue script action with plugin scritps and styles
    add_action( 'wp_enqueue_scripts', 'wpss_scripts_styles', 999);

    // Create funtion to enqueue plugin scripts and styles
    function wpss_scripts_styles() {

        if ( !is_admin() ) {

            // enqueue script | @Dependents: jQuery
            wp_enqueue_script('wp_arch_lightbox_scripts', plugins_url('/source/jquery.fancybox.pack.js', __FILE__), array('jquery'), "1", true);

            // enqueue script | @Dependents: jQuery & wp_arch_lightbox_scripts
            wp_enqueue_script('wp_arch_lightbox_scripts_init', plugins_url('/source/init.js', __FILE__), array('wp_arch_lightbox_scripts'), "1", true);

            // enqueue css
            wp_enqueue_style('wp_arch_lightbox_styles', plugins_url('/source/jquery.fancybox.css', __FILE__), array(), '01', 'all');
        }

    }
        // Remove default Gallery Shortcode to replace with custom
        remove_shortcode('gallery', 'gallery_shortcode');

        // Add Custom shortcode
        add_shortcode('gallery', 'gallery_shortcode_wp_arch');

        // Get the 'large' image
        //http://oikos.org.uk/2011/09/tech-notes-using-resized-images-in-wordpress-galleries-and-lightboxes/

        function oikos_get_attachment_link_filter( $content, $post_id, $size, $permalink ) {
 
                // Only do this if we're getting the file URL
                if (! $permalink) {
                    // This returns an array of (url, width, height)
                    $image = wp_get_attachment_image_src( $post_id, 'large' );
                    $new_content = preg_replace('/href=\'(.*?)\'/', 'href=\'' . $image[0] . '\'', $content );
                    return $new_content;
                } else {
                    return $content;
                }
            }
            add_filter('wp_get_attachment_link', 'oikos_get_attachment_link_filter', 10, 4);


        // Adding Fancybox class to anchor link wrapping image 
        function add_rel_to_gallery($link) {
            $link = str_replace("'><img", "' class=\"fancybox\" rel=\"group1\" ><img", $link);
            return $link;
        }

        // Custom Gallery Shortcode funcion
        function gallery_shortcode_wp_arch($attr)   {

            global $post, $wp_locale;
            static $instance = 0;

            $post = get_post();

            static $instance = 0;
            $instance++;

            if ( ! empty( $attr['ids'] ) ) {
                // 'ids' is explicitly ordered, unless you specify otherwise.
                if ( empty( $attr['orderby'] ) )
                    $attr['orderby'] = 'post__in';
                $attr['include'] = $attr['ids'];
            }

            // Allow plugins/themes to override the default gallery template.
            $output = apply_filters('post_gallery', '', $attr);
            if ( $output != '' )
                return $output;

            // We're trusting author input, so let's at least make sure it looks like a valid orderby statement
            if ( isset( $attr['orderby'] ) ) {
                $attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
                if ( !$attr['orderby'] )
                    unset( $attr['orderby'] );
            }

            extract(shortcode_atts(array(
                'order'      => 'ASC',
                'orderby'    => 'menu_order ID',
                'id'         => $post->ID,
                'itemtag'    => 'dl',
                'icontag'    => 'dt',
                'captiontag' => 'dd',
                'columns'    => 3,
                'size'       => 'thumbnail',
                'include'    => '',
                'exclude'    => ''
            ), $attr));

            $id = intval($id);
            if ( 'RAND' == $order )
                $orderby = 'none';

            if ( !empty($include) ) {
                $_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

                $attachments = array();
                foreach ( $_attachments as $key => $val ) {
                    $attachments[$val->ID] = $_attachments[$key];
                }
            } elseif ( !empty($exclude) ) {
                $attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
            } else {
                $attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
            }

            if ( empty($attachments) )
                return '';

            if ( is_feed() ) {
                $output = "\n";
                foreach ( $attachments as $att_id => $attachment )
                    $output .= wp_get_attachment_link($att_id, $size, true) . "\n";
                return $output;
            }

            $itemtag = tag_escape($itemtag);
            $captiontag = tag_escape($captiontag);
            $icontag = tag_escape($icontag);
            $valid_tags = wp_kses_allowed_html( 'post' );
            if ( ! isset( $valid_tags[ $itemtag ] ) )
                $itemtag = 'dl';
            if ( ! isset( $valid_tags[ $captiontag ] ) )
                $captiontag = 'dd';
            if ( ! isset( $valid_tags[ $icontag ] ) )
                $icontag = 'dt';

            $columns = intval($columns);
            $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
            $float = is_rtl() ? 'right' : 'left';

            $selector = "gallery-{$instance}";

            $gallery_style = $gallery_div = '';
            if ( apply_filters( 'use_default_gallery_style', true ) )
                $gallery_style = "
                <style type='text/css'>
                    #{$selector} {
                        margin: auto;
                    }
                    #{$selector} .gallery-item {
                        float: {$float};
                        margin-top: 10px;
                        text-align: center;
                        width: {$itemwidth}%;
                    }
                    #{$selector} img {
                        border: 2px solid #cfcfcf;
                    }
                    #{$selector} .gallery-caption {
                        margin-left: 0;
                    }
                </style>
            <!-- see gallery_shortcode() in plugins/wp-architect-lightbox/fancybox-gallery.php -->";
            $size_class = sanitize_html_class( $size );
            $gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";
            $output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

            // Added MRE 2013_04_23
            add_filter('wp_get_attachment_link', 'add_rel_to_gallery');

            $i = 0;
            foreach ( $attachments as $id => $attachment ) {
                $link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, false, false);

                $output .= "<{$itemtag} class='gallery-item'>";
                $output .= "
                    <{$icontag} class='gallery-icon'>
                        $link
                    </{$icontag}>";
                if ( $captiontag && trim($attachment->post_excerpt) ) {
                    $output .= "
                        <{$captiontag} class='wp-caption-text gallery-caption'>
                        " . wptexturize($attachment->post_excerpt) . "
                        </{$captiontag}>";
                }
                $output .= "</{$itemtag}>";
                if ( $columns > 0 && ++$i % $columns == 0 )
                    $output .= '<br style="clear: both" />';
            }

            $output .= "
                    <br style='clear: both;' />
                </div>\n";

            // Replaces old attachment link with fancybox 
            remove_filter('wp_get_attachment_link', 'add_rel_to_gallery');

            return $output;
        } // close gallery_shortcode_wp_arch()
 ?>