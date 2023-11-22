<?php

/**
 * Plugin Name: HVAC Feed Woocommerce (Premium)
 * Plugin URI: https://exclusivewebmarketing.com/
 * Description: Provides the ability to copy selected products from a master website to a second website.
 * Version: 1.2.4
 * Update URI: https://api.freemius.com
 * Author: HVAC Feed Woocommerce
 * Author URI: https://exclusivewebmarketing.com/
 * Text Domain: HVAC_Feed_Woocommerce-plugin
 * Domain Path: /languages/
 * License: GPLv2 or any later version
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or later, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package WPBDP
 */
// error_reporting( E_ERROR | E_PARSE );
// ini_set('display_errors', FALSE);
// error_reporting(0);

if ( !function_exists( 'wfeed_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wfeed_fs()
    {
        global  $wfeed_fs ;
        
        if ( !isset( $wfeed_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $wfeed_fs = fs_dynamic_init( array(
                'id'               => '9892',
                'slug'             => 'HVACFeedWoocommerce',
                'type'             => 'plugin',
                'public_key'       => 'pk_06b36ef3f0d8ce958f712fc597c5b',
                'is_premium'       => true,
                'is_premium_only'  => false,
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => false,
                'trial'            => array(
                'days'               => 14,
                'is_require_payment' => true,
            ),
                'menu'             => array(
                'slug'    => 'woocommerce-child',
                'support' => false,
            ),
                'is_live'          => true,
            ) );
        }
        
        return $wfeed_fs;
    }
    
    // Init Freemius.
    wfeed_fs();
    // Signal that SDK was initiated.
    do_action( 'wfeed_fs_loaded' );
}

// error_reporting(E_ERROR | E_PARSE);
// Do not allow direct access to this file.
require __DIR__ . '/vendor/autoload.php';
use  Automattic\WooCommerce\Client ;
// Process images
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
add_action( 'wp_enqueue_scripts', 'ws_load_public_resources' );
function ws_load_public_resources( $options )
{
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'wsf-main-lib-uploader-js', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/public-script.js', 'jquery' ) );
    wp_localize_script( 'wsf-main-lib-uploader-js', 'ajax_object', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
    ) );
}

if ( !function_exists( 'woo_c_avoid_double_install' ) ) {
    function woo_c_avoid_double_install()
    {
        
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            // continue
        } else {
            wp_die( 'Please install and activate Woocommerce before activating HVAC Feed for woocommerce.' );
        }
    
    }

}
register_activation_hook( __FILE__, 'woo_c_avoid_double_install' );
add_action( 'admin_enqueue_scripts', 'woo_c_child_load_admin_resources' );
function woo_c_child_load_admin_resources( $options )
{
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'ewm-feed-main-lib-uploader-js', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/script-admin.js', 'jquery' ) );
    wp_localize_script( 'ewm-feed-main-lib-uploader-js', 'ajax_object', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
    ) );
    wp_enqueue_style( 'ewm-feed-style_admin', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/style-admin.css' ) );
}

add_action( 'wp_enqueue_scripts', 'woo_c_child_load_public_resources' );
function woo_c_child_load_public_resources( $options )
{
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'woo_c-public-main-lib-uploader-js', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/script-public.js', 'jquery' ) );
    wp_localize_script( 'woo_c-public-main-lib-uploader-js', 'ajax_object', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
    ) );
    wp_enqueue_style( 'woo_c-style_public', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/style-public.css' ) );
}

function add_cat_name( $args = array() )
{
    $categories = $args['product_data']->categories;
    $id = $args['post_detail']['post_id'];
    foreach ( $categories as $single_category ) {
        $cat_name = $single_category->name;
        $cat_slug = $single_category->slug;
        $listing_tags = $single_category->name;
        // Add term to man list if it does not exist
        // Associate post to
        wp_insert_term( $cat_name, 'product_cat', array(
            'description' => $cat_name,
            'slug'        => $cat_slug,
        ) );
        wp_set_object_terms(
            $id,
            $listing_tags,
            'product_tag',
            false
        );
        $return_tax = get_term_by( 'name', $cat_name, 'product_cat' );
        wp_set_post_terms( $id, [ $return_tax->term_id ], 'product_cat' );
    }
}

function add_listing_post_data( $args )
{
    $current_user_id = get_current_user_id();
    // Add mapping to see were a product relate tot the other
    // => Add new product if the product does not exist => Update the old product of the product already exist
    // Add Post
    $content_slug = preg_replace( '#[ -]+#', '-', $args->name );
    // Create post
    $post_data = [
        "post_author"           => $current_user_id,
        "post_date"             => date( 'Y-m-d H:i:s' ),
        "post_date_gmt"         => date( 'Y-m-d H:i:s' ),
        "post_content"          => $args->description,
        "post_title"            => $args->name,
        "post_excerpt"          => $args->short_description,
        "post_status"           => "publish",
        "comment_status"        => "open",
        "ping_status"           => "closed",
        "post_password"         => "",
        "post_name"             => $args->name,
        "to_ping"               => "",
        "pinged"                => "",
        "post_modified"         => date( 'Y-m-d H:i:s' ),
        "post_modified_gmt"     => date( 'Y-m-d H:i:s' ),
        "post_content_filtered" => "",
        "post_parent"           => 0,
        "guid"                  => "",
        "menu_order"            => 0,
        "post_type"             => "product",
        "post_mime_type"        => "",
        "comment_count"         => "0",
        "filter"                => "raw",
    ];
    global  $wp_error ;
    $new_post_data = [
        'post_id'     => '',
        'post_is_new' => '',
    ];
    $new_post_id = '';
    // @todo change from name to id
    $getPeople = array(
        'name'           => $content_slug,
        'post_type'      => 'wpbdp_listing',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    );
    remove_all_filters( "content_save_pre" );
    add_filter(
        'wp_kses_allowed_html',
        'wpse_kses_allowed_html',
        10,
        2
    );
    $myposts = get_posts( $getPeople );
    $wchild_product_exists = get_option( 'product_details_' . $args->id );
    // echo 'Product details' ;
    // var_dump( $wchild_product_exists ) ;
    
    if ( $wchild_product_exists == false ) {
        $new_post_id = wp_insert_post( $post_data, $wp_error );
        $new_post_data['post_id'] = $new_post_id;
        $new_post_data['post_is_new'] = true;
        //add new product post
        add_option( 'product_details_' . $args->id, $new_post_id );
    } else {
        $new_post_id = $wchild_product_exists;
        $new_post_data['post_id'] = $new_post_id;
        $new_post_data['post_is_new'] = false;
        // do product post update
        $post_data['ID'] = $wchild_product_exists;
        wp_update_post( $post_data );
    }
    
    $f_of_time_to_update = get_option( 'f_of_time_to_update' );
    $timestamp = time();
    $my_update_f = get_option( 'my_update_frequency_setting_field' );
    
    if ( $f_of_time_to_update == false ) {
        // if false -> create new time
        $timestamp = time();
        // If time has elapsed -> load all products
        add_option( 'f_of_time_to_update', $timestamp );
    } else {
        // else update
        $timestamp = time();
        $period_to_add = [
            '1' => '+1 days',
            '2' => '+7 days',
            '3' => '+30 days',
            '4' => '+90 days',
        ];
        $timestamp = strtotime( $period_to_add[$my_update_f], $timestamp );
        // If time has elapsed -> load all products
        update_option( 'f_of_time_to_update', $timestamp );
    }
    
    // Remove Custom Filter
    remove_filter( 'wp_kses_allowed_html', 'wpse_kses_allowed_html', 10 );
    return $new_post_data;
}

function add_listing_meta_d( $args = array() )
{
    // Add attributes.
    // $args['post_details']['gallery_list']
    $_attributes_arr = [];
    foreach ( $args['product_data']->attributes as $_single_product ) {
        $option_s = '';
        foreach ( $_single_product->options as $s_product ) {
            $option_s .= $s_product;
        }
        $content_slug = preg_replace( '#[ -]+#', '-', $_single_product->name );
        $_attributes_arr[$content_slug] = [
            "name"         => $_single_product->name,
            "value"        => $option_s,
            "position"     => 1,
            "is_visible"   => 1,
            "is_variation" => 0,
            "is_taxonomy"  => 0,
        ];
    }
    // Map to Actual Metas
    // Get Metadata Fields
    $edit_lock = time() . ':1';
    $post_id = $args['post_details']['post_id'];
    $total_sales = ( property_exists( $args['product_data'], 'total_sales' ) ? $args['product_data']->total_sales : '' );
    $_tax_status = ( property_exists( $args['product_data'], 'tax_status' ) ? $args['product_data']->tax_status : '' );
    $_tax_class = ( property_exists( $args['product_data'], 'tax_class' ) ? $args['product_data']->tax_class : '' );
    $_manage_stock = ( property_exists( $args['product_data'], 'manage_stock' ) ? $args['product_data']->manage_stock : '' );
    $_backorders = ( property_exists( $args['product_data'], 'backorders' ) ? $args['product_data']->backorders : '' );
    $_sold_individually = ( property_exists( $args['product_data'], 'sold_individually' ) ? $args['product_data']->sold_individually : '' );
    $_virtual = ( property_exists( $args['product_data'], 'virtual' ) ? $args['product_data']->virtual : '' );
    $_downloadable = ( property_exists( $args['product_data'], 'downloadable' ) ? $args['product_data']->downloadable : '' );
    $_download_limit = ( property_exists( $args['product_data'], 'download_limit' ) ? $args['product_data']->download_limit : '' );
    $_download_expiry = ( property_exists( $args['product_data'], 'download_expiry' ) ? $args['product_data']->download_expiry : '' );
    $_stock = ( property_exists( $args['product_data'], 'stock_quantity' ) ? $args['product_data']->stock_quantity : '' );
    $_stock_status = ( property_exists( $args['product_data'], 'stock_status' ) ? $args['product_data']->stock_status : '' );
    $_wc_average_rating = ( property_exists( $args['product_data'], 'average_rating' ) ? $args['product_data']->average_rating : '' );
    $_sku = ( property_exists( $args['product_data'], 'sku' ) ? $args['product_data']->sku : '' );
    $_regular_price = ( property_exists( $args['product_data'], 'regular_price' ) ? $args['product_data']->regular_price : '' );
    $_sale_price = ( property_exists( $args['product_data'], 'sale_price' ) ? $args['product_data']->sale_price : '' );
    $_weight = ( property_exists( $args['product_data'], 'weight' ) ? $args['product_data']->weight : '' );
    $_length = ( property_exists( $args['product_data'], 'dimensions' ) ? $args['product_data']->dimensions->length : '' );
    $_width = ( property_exists( $args['product_data'], 'dimensions' ) ? $args['product_data']->dimensions->width : '' );
    $_height = ( property_exists( $args['product_data'], 'dimensions' ) ? $args['product_data']->dimensions->height : '' );
    $_purchase_note = ( property_exists( $args['product_data'], 'purchase_note' ) ? $args['product_data']->purchase_note : '' );
    $_price = ( property_exists( $args['product_data'], 'price' ) ? $args['product_data']->price : '' );
    $thumbnail_image = $args['post_details']['thumbnail_image'];
    /*
    if ( count($args['post_details']['gallery_list']) > 0 ) {
    
        $thumbnail_image    = $args['post_details']['gallery_list'][0] ;
        unset( $args['post_details']['gallery_list'][0] ) ;
        if(count( $args['post_details']['gallery_list'] ) == 0 ){
    
            $args['post_details']['gallery_list'] = [] ;
    
        }
    
    }
    else{
    
        $thumbnail_image    = '' ;
    
    }
    
    $args['post_details']['gallery_list'] = implode(",", $args['post_details']['gallery_list'] );
    */
    //    var_dump($args['post_details']['gallery_list']);
    //    var_dump($args['post_details']['thumbnail_image']);
    //    echo '<br><br>' ;
    $final_arr = [
        "_edit_lock"             => $edit_lock,
        "_edit_last"             => "1",
        "total_sales"            => $total_sales,
        "_tax_status"            => $_tax_status,
        "_tax_class"             => $_tax_class,
        "_manage_stock"          => $_manage_stock,
        "_backorders"            => $_backorders,
        "_sold_individually"     => $_sold_individually,
        "_virtual"               => $_virtual,
        "_downloadable"          => $_downloadable,
        "_download_limit"        => $_download_limit,
        "_download_expiry"       => $_download_expiry,
        "_stock"                 => $_stock,
        "_stock_status"          => $_stock_status,
        "_wc_average_rating"     => $_wc_average_rating,
        "_thumbnail_id"          => $args['post_details']['thumbnail_image'],
        "_sku"                   => $_sku,
        "_regular_price"         => $_regular_price,
        "_sale_price"            => $_sale_price,
        "_weight"                => $_weight,
        "_length"                => $_length,
        "_width"                 => $_width,
        "_height"                => $_height,
        "_purchase_note"         => $_purchase_note,
        "_price"                 => $_price,
        "_product_image_gallery" => $args['post_details']['gallery_list'],
        "_product_attributes"    => $_attributes_arr,
    ];
    // Create Metadata
    $meta_arr_box = [];
    $tt = 0;
    delete_post_meta( $post_id, '_product_image_gallery' );
    delete_post_meta( $post_id, '_thumbnail_id' );
    foreach ( $final_arr as $meta_key => $meta_value ) {
        //if ($args['post_details']['post_is_new'] || $meta_key == '_product_image_gallery' || $meta_key == "_thumbnail_id" ) {
        $meta_arr_box[$tt] = add_post_meta(
            $post_id,
            $meta_key,
            $meta_value,
            true
        );
        //}
        //else{
        // $meta_arr_box[ $tt ] = update_post_meta( $post_id, $meta_key, $meta_value ) ;
        //}
        $tt++;
    }
}

function process_thumbnail_image( $args = array() )
{
    $images = $args['product_data']->images;
    $media = '';
    $new_post_id = $args['post_detail']['post_id'];
    $image_url = $images[0]->src;
    // Magic sideload image returns an HTML image, not an ID
    $media = media_sideload_image( $image_url, $new_post_id, 'id' );
    // Therefore we must find it so we can set it as featured ID
    
    if ( !empty($media) && !is_wp_error( $media ) ) {
        $args = array(
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'post_parent'    => $new_post_id,
        );
        // Reference new image to set as featured
        $attachments = get_posts( $args );
        if ( isset( $attachments ) && is_array( $attachments ) ) {
            foreach ( $attachments as $attachment ) {
                // Grab source of full size images (so no 300x150 nonsense in path)
                $image = wp_get_attachment_image_src( $attachment->ID, 'full' );
                // Determine if in the $media image we created, the string of the URL exists
                
                if ( strpos( $media, $image[0] ) !== false ) {
                    // If so, we found our image. set it as thumbnail
                    set_post_thumbnail( $new_post_id, $attachment->ID );
                    // Only want one image
                    break;
                }
            
            }
        }
    }
    
    if ( is_wp_error( $media ) ) {
        echo  $media->get_error_message() ;
    }
    return $attachment->ID;
}

function process_gallery_list( $args = array() )
{
    $images = $args['product_data']->images;
    $media = '';
    $new_post_id = $args['post_detail']['post_id'];
    $image_list = '';
    foreach ( $images as $img_key => $image ) {
        if ( $img_key == 0 ) {
            continue;
        }
        $image_url = $image->src;
        // Magic sideload image returns an HTML image, not an ID
        $media = media_sideload_image( $image_url, $new_post_id, 'id' ) . ',';
        // therefore we must find it so we can set it as featured ID
        
        if ( !empty($media) && !is_wp_error( $media ) ) {
            $args = array(
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'post_parent'    => $new_post_id,
            );
            // reference new image to set as featured
            $attachments = get_posts( $args );
            if ( isset( $attachments ) && is_array( $attachments ) ) {
                foreach ( $attachments as $attachment ) {
                    // Grab source of full size images (so no 300x150 nonsense in path)
                    $image = wp_get_attachment_image_src( $attachment->ID, 'full' );
                    $image_list .= $attachment->ID . ',';
                    // Determine if in the $media image we created, the string of the URL exists
                    
                    if ( strpos( $media, $image[0] ) !== false ) {
                        // If so, we found our image. set it as thumbnail
                        set_post_thumbnail( $new_post_id, $attachment->ID );
                        // Only want one image
                        break;
                    }
                
                }
            }
        }
        
        if ( is_wp_error( $media ) ) {
            echo  $media->get_error_message() ;
        }
    }
    return $image_list;
}

function ewm_add_product_brand( $args = array() )
{
    // $args['brand_name'] // $args['post']
    $post_id = $args['post']['post_id'];
    update_post_meta( $post_id, 'product_brand_tag', sanitize_text_field( $args['brand_name'] ) );
}

function add_single_product( $single_product_data = array(), $brand = '' )
{
    $listing_post_data = add_listing_post_data( $single_product_data );
    // Add Categories // @TODO add tags
    add_cat_name( [
        'product_data' => $single_product_data,
        'post_detail'  => $listing_post_data,
    ] );
    ewm_add_product_brand( [
        'brand_name' => $brand,
        'post'       => $listing_post_data,
    ] );
    // Add/ Update Images
    // wp_delete_attachment( $listing_post_data['post_id'], true );
    // var_dump( $ewm_hf_server_cpu_usage );
    $args = array(
        'post_parent'    => $listing_post_data['post_id'],
        'post_type'      => 'attachment',
        'numberposts'    => -1,
        'post_status'    => 'any',
        'post_mime_type' => 'image',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );
    $images = get_posts( $args );
    foreach ( $images as $dk => $dv ) {
        wp_delete_post( $dv->ID );
    }
    $args = array(
        'post_parent' => $listing_post_data['post_id'],
    );
    $posts_array = get_posts( $args );
    // Loop while deleting
    foreach ( $posts_array as $single_post ) {
        wp_delete_attachment( $single_post->ID, true );
    }
    // Do thumbnail
    $thumbnail_image = process_thumbnail_image( [
        'product_data' => $single_product_data,
        'post_detail'  => $listing_post_data,
    ] );
    // Do gallery
    $gallery_list = process_gallery_list( [
        'product_data' => $single_product_data,
        'post_detail'  => $listing_post_data,
    ] );
    $listing_post_data['thumbnail_image'] = $thumbnail_image;
    $listing_post_data['gallery_list'] = $gallery_list;
    // Add/ Update Meta Data
    $listing_meta = add_listing_meta_d( [
        'product_data' => $single_product_data,
        'post_details' => $listing_post_data,
    ] );
}

function ewm_add_categories_options( $data_list = array() )
{
    $array_list = [];
    foreach ( $data_list as $single_cat ) {
        $array_list[$single_cat->id] = [
            'name'   => $single_cat->name,
            'id'     => $single_cat->id,
            'brands' => [],
        ];
    }
    add_option( 'ewm_categories_selected', maybe_serialize( $array_list ) );
}

// return the list of saved categories list
function ewm_get_categories_selected( $data_list = array() )
{
    // If the options not set -> set
    $ewm_categories_selected = maybe_unserialize( get_option( 'ewm_categories_selected' ) );
    
    if ( !$ewm_categories_selected || is_array( $ewm_categories_selected ) && count( $ewm_categories_selected ) == 0 ) {
        delete_option( 'ewm_categories_selected' );
        ewm_get_add_categories();
        $ewm_categories_selected = get_option( 'ewm_categories_selected' );
    }
    
    return maybe_unserialize( $ewm_categories_selected );
}

// Initial load of list.
// Load on javascript.
// On js file mange the data.
// Receive update via json.
// update the db.
// Daily update the category list.
// Checks of there are changed in categories in the parent database
function ewm_get_add_categories()
{
    $my_domain_setting_field = get_option( 'my_domain_setting_field' );
    $my_setting_customer_key_field = get_option( 'my_setting_customer_key_field' );
    $my_setting_customer_secret_field = get_option( 'my_setting_customer_secret_field' );
    $data_list = [];
    if ( is_string( $my_domain_setting_field ) && is_string( $my_setting_customer_key_field ) && is_string( $my_setting_customer_secret_field ) ) {
        
        if ( strlen( $my_domain_setting_field ) > 0 && strlen( $my_setting_customer_key_field ) > 0 && strlen( $my_setting_customer_secret_field ) > 0 ) {
            $woocommerce = new Client(
                $my_domain_setting_field,
                $my_setting_customer_key_field,
                $my_setting_customer_secret_field,
                [
                "wp_json"           => true,
                "version"           => "wc/v1",
                "query_string_auth" => true,
            ]
            );
            $data_list = $woocommerce->get( 'products/categories', [
                'per_page' => 100,
            ] );
        }
    
    }
    $ewm_categories_selected = maybe_unserialize( get_option( 'ewm_categories_selected' ) );
    
    if ( !$ewm_categories_selected || is_array( $ewm_categories_selected ) && count( $ewm_categories_selected ) == 0 ) {
        // Do new category list save
        ewm_add_categories_options( $data_list );
    } else {
        // Loop checking of the category is in db -> if its not add it
        foreach ( $data_list as $key => $value ) {
            // If the category does to exist ->  Add it
            if ( !array_key_exists( $value->id, $ewm_categories_selected ) ) {
                $ewm_categories_selected[$value->id] = [
                    'name'   => $value->name,
                    'id'     => $value->id,
                    'brands' => [],
                ];
            }
        }
        // Delete the previous option -> Add the new category list
        delete_option( 'ewm_categories_selected' );
        // Add the new value
        add_option( 'ewm_categories_selected', maybe_serialize( $ewm_categories_selected ) );
    }

}

// Silent function that runs ins the background and loads all categories
function ewm_get_categories_load()
{
    // check session for existence of a category time
    if ( session_id() == '' ) {
        session_start();
    }
    // if not time -> create time ->
    
    if ( !array_key_exists( 'ewm_hf_next_cat_load', $_SESSION ) ) {
        // Do load cat load if it does not exist
        $ewm_categories_selected = get_option( 'ewm_categories_selected' );
        if ( !$ewm_categories_selected ) {
            ewm_get_add_categories();
        }
        // Add a time stamp 24 hours from now
        $time_now = time();
        $_time_next = strtotime( '+1 days', $time_now );
        $_SESSION['ewm_categories_selected'] = $_time_next;
    } else {
        // if time is overdue -> update -> set new time 24 hours form now
        $time_now = time();
        $next_time_now = $_SESSION['ewm_categories_selected'];
        
        if ( $time_now > $next_time_now ) {
            ewm_get_add_categories();
            $_time_next = strtotime( '+1 days', $time_now );
            session_start();
            $_SESSION['ewm_categories_selected'] = $_time_next;
        }
    
    }

}

add_action( 'init', 'ewm_get_categories_load' );
function ewm_hf_load_json_list( $server_data_list = array() )
{
    foreach ( $server_data_list as $key => $value ) {
        // if brands don't  existing add.
        if ( !array_key_exists( 'brands', $value ) ) {
            $server_data_list[$key]['brands'] = [];
        }
    }
    $server_data_list_json = json_encode( $server_data_list );
    return '
    <script type="text/javascript">

        var server_data_list = ' . $server_data_list_json . ';

    </script>
    
    <style>

        .ewm_cat_list{

            border:1px gray solid;
            margin-top:2px !important;
            float:left;
            padding:10px 30px;
            cursor:pointer;

        }
        .ewm_cat_selected{

            background: #333 ;
            color: #fff ;

        }

    </style>';
}

// Function organizes the db cat list to version that is brand first -> This helps in listing the options
function ewm_hf_convert_to_friendly_arr()
{
    $ewm_server_categories = ewm_get_categories_selected();
    $friendly_arr_of_cat = [];
    foreach ( $ewm_server_categories as $cat_id => $category ) {
        // Loop through the "brands" key -> if the key does not exist -> add it to friendly list
        foreach ( $category['brands'] as $cat_brand ) {
            if ( !array_key_exists( $cat_brand, $friendly_arr_of_cat ) ) {
                $friendly_arr_of_cat[$cat_brand] = [];
            }
            // If it is on the car key list -> add cat value
            array_push( $friendly_arr_of_cat[$cat_brand], $cat_id );
        }
    }
    return $friendly_arr_of_cat;
}

function ewm_hf_brand_drop_down( $single_brand = array() )
{
    /*
        'key'       =>$key,
        'section_id'=>$section_number 
        $single_brand['section_id']
    */
    // Draft
    $Unspecified = ( $single_brand['key'] == 'Unspecified' ? 'selected="selected"' : '' );
    $Tempstar = ( $single_brand['key'] == 'Tempstar' ? 'selected="selected"' : '' );
    $Crown_Boiler_Co = ( $single_brand['key'] == 'Crown Boiler Co' ? 'selected="selected"' : '' );
    $Crown_Mega_Stor = ( $single_brand['key'] == 'Crown Mega-Stor' ? 'selected="selected"' : '' );
    $Weil_McLain = ( $single_brand['key'] == 'Weil-McLain' ? 'selected="selected"' : '' );
    $Velocity_Boiler_Works = ( $single_brand['key'] == 'Velocity Boiler Works' ? 'selected="selected"' : '' );
    // set javascript brand name.
    $full_html_cat_single = '
    <div class="select_brand_parent">

        <select class="product_brand_data_hidden ewm_hf_brand_dropdown_' . $single_brand['section_id'] . '" name="product_brand_data" disabled="true" data-section-id="' . $single_brand['section_id'] . '">

            <optgroup label="Product Brands">
                <option ' . $Unspecified . ' value="Unspecified"> Unspecified </option>
                <option ' . $Tempstar . ' value="Tempstar"> Tempstar </option>
                <option ' . $Crown_Boiler_Co . ' value="Crown Boiler Co">Crown Boiler Co</option>
                <option ' . $Crown_Mega_Stor . ' value="Crown Mega-Stor">Crown Mega-Stor</option>
                <option ' . $Weil_McLain . ' value="Weil-McLain">Weil-McLain</option>
                <option ' . $Velocity_Boiler_Works . ' value="Velocity Boiler Works">Velocity Boiler Works</option>
            </optgroup>

        </select>


        <div class="ewm_hf_delete_button ewm_hf_delete_button_' . $single_brand['section_id'] . '">
            <input type="button" class="ewm_del_sec_button" value="Delete" data-section-id="' . $single_brand['section_id'] . '">
        </div>

    </div>';
    return $full_html_cat_single;
}

function ewm_hf_category_list( $args = array() )
{
    // Get the main category list
    // Create the checkbox while checking the relevant categories
    // Set javascript cat name
    $full_html_cat_single = '';
    //var_dump($args['original']);
    foreach ( $args['original'] as $cat_key => $single_cat ) {
        $html_list[$single_cat['id']] = $single_cat['name'];
        $class_name_ = '';
        $is_checked = ( in_array( $single_cat['id'], $args['selected_list'] ) ? ' checked' : '' );
        if ( is_string( $single_cat['name'] ) ) {
            $full_html_cat_single .= '
                <div class="parent_cat_selects">
                    <input type="checkbox" class="ewm_cat_list ewm_cat_s_i ' . $class_name_ . '" data-category_key_id="' . $single_cat['id'] . '" data-category_selected="" data-category_name_id="' . $single_cat['name'] . '" data-section-id="' . $args['section_id'] . '" ' . $is_checked . '>' . $single_cat['name'] . '
                </div>';
        }
    }
    return $full_html_cat_single;
}

function ewm_hf_category_list_script( $args = array() )
{
    // Get the main category list
    // Create the checkbox while checking the relevant categories
    // Set javascript cat name
    $full_html_cat_single = '<div class="checkbox_cat_parent">';
    foreach ( $args['original'] as $single_cat ) {
        $html_list[$single_cat['id']] = $single_cat['name'];
        $class_name_ = 'script_checker';
        $is_checked = ( in_array( $single_cat['id'], $args['selected_list'] ) ? ' checked' : '' );
        if ( is_string( $single_cat['name'] ) ) {
            $full_html_cat_single .= '
                <div class="parent_cat_selects">
                    <input type="checkbox" class="ewm_cat_list ewm_cat_s_i pre_single_cat_input pre_id_cat_rel ' . $class_name_ . '" data-category_key_id="' . $single_cat['id'] . '" data-category_selected="" data-category_name_id="' . $single_cat['name'] . '" ' . $is_checked . '>' . $single_cat['name'] . '
                </div>';
        }
    }
    $full_html_cat_single .= '</div>';
    return $full_html_cat_single;
}

function ewm_hf_brand_drop_down_script_temp( $single_brand = array() )
{
    // Set javascript brand name
    $full_html_cat_single = '
    <div class="select_brand_parent ewm_hf_message_error_place">
        <select class="product_brand_data_hidden pre_single_brand_det" name="product_brand_data">
            <optgroup label="Product Brands">
                <option value="Unspecified"> Unspecified </option>
                <option value="Tempstar"> Tempstar </option>
                <option value="Crown Boiler Co">Crown Boiler Co</option>
                <option value="Crown Mega-Stor">Crown Mega-Stor</option>
                <option value="Weil-McLain">Weil-McLain</option>
                <option value="Velocity Boiler Works">Velocity Boiler Works</option>
            </optgroup>
        </select>
        <div class="ewm_hf_delete_button pre_single_brand_del">
            <input type="button" class="ewm_del_sec_button pre_single_brand_del_b" value="Delete" >
        </div>
    </div>';
    return $full_html_cat_single;
}

function ewm_hf_script_section( $args = array() )
{
    $ewm_hf_html = '';
    $ewm_hf_html .= '
    <div class="ewm_hf_hidden_body_sections" >
        <div class="main_brand_and_cats pre_top_layer" >';
    $ewm_hf_html .= ewm_hf_brand_drop_down_script_temp();
    $value = [];
    // Add categories
    $ewm_hf_html .= ewm_hf_category_list_script( [
        'original'      => $args['original'],
        'selected_list' => $value,
    ] );
    $ewm_hf_html .= '
    </div>
    </div>';
    return $ewm_hf_html;
}

function ewm_hf_display_popup()
{
    return '
    <div class="ewm_out_blur">
        <div class="ewm_inner_body" > 
            <div class="ewm_close_top_menu" > 
                <span class="ewm_close_right"> Close [x] </span>
            </div>
            <div class="ewm_hf_main_body"></div>
            <center>
                <div class="ewm_lower_controls_body">
                    <input type="button" value="Continue" class="ewm_submit_continue">
                    <input type="button" value="Cancel" class="ewm_submit_cancel">
                </div>
            </center>
        </div>
    </div>';
}

function ewm_hf_format_html( $args = array() )
{
    $section_number = 1;
    $ewm_hf_html = '
    <script type="text/javascript">
        var ewm_hf_active_brands = {

            "Unspecified" : {"status" : "inactive", "id" : 0 },
            "Tempstar"  : {"status" : "inactive", "id" : 0 },
            "Crown Boiler Co"  : {"status" : "inactive", "id" : 0 },
            "Crown Mega-Stor"  : {"status" : "inactive", "id" : 0 },
            "Weil-McLain"  : {"status" : "inactive", "id" : 0 },
            "Velocity Boiler Works"  : {"status" : "inactive", "id" : 0 },

        };
    </script>

    <div class="top_parent_in_filter">';
    foreach ( $args['friendly'] as $key => $value ) {
        $ewm_hf_html .= '
        <div class="main_brand_and_cats main_brand_and_cats_' . $section_number . ' " data-selection-id="' . $section_number . '" >

        <script type="text/javascript">
            ewm_hf_active_brands[' . $section_number . '] = "' . $key . '";
        </script>
        ';
        $ewm_hf_html .= ewm_hf_brand_drop_down( [
            'key'        => $key,
            'section_id' => $section_number,
        ] );
        // Add categories
        $ewm_hf_html .= ewm_hf_category_list( [
            'original'      => $args['original'],
            'selected_list' => $value,
            'section_id'    => $section_number,
        ] );
        $ewm_hf_html .= '</div>';
        $section_number++;
    }
    $ewm_hf_html .= '</div>
    <div class="ewm_add_details_value">
        <input type="button" id="ewm_add_filter_section" value="Add a Brand">
    </div>
    <script type="text/javascript">
        var ewm_hf_next_section_id = ' . $section_number . ' ;
    </script>';
    $ewm_hf_html .= ewm_hf_display_popup();
    $ewm_hf_html .= ewm_hf_script_section( [
        'original' => $args['original'],
    ] );
    return $ewm_hf_html;
}

function ewm_display_categories()
{
    $server_data_list = ewm_get_categories_selected();
    $html_list = [];
    echo  ewm_hf_load_json_list( $server_data_list ) ;
    $new_selection_list = [];
    $converted_to_friendly_arr = ewm_hf_convert_to_friendly_arr();
    $ewm_hf_format_html = ewm_hf_format_html( [
        'original' => $server_data_list,
        'friendly' => $converted_to_friendly_arr,
    ] );
    $full_html_cat = $ewm_hf_format_html;
    echo  $full_html_cat ;
}

function ewm_hf_remove_product( $single_product )
{
    // todo get actual value
    $listing_post_data['post_id'] = 22;
    // Remove all files -  images & attachment
    // Remove the meta data
    // Remove the actual post
    $args = array(
        'post_parent' => $listing_post_data['post_id'],
    );
    $posts_array = get_posts( $args );
    // Loop while deleting
    foreach ( $posts_array as $single_post ) {
        wp_delete_attachment( $single_post->ID, true );
    }
}

/*
ewm_hf_process_single_category([

    'ewm_categories_selected'   => $ewm_categories_selected,
    'single_cat_key'            => $single_cat_key,
    'single_cat_val'            => $single_cat_val,
    'woocommerce'               => $woocommerce,
    'options_details'           => $args

]);
*/
// scheduler - List all product IDs to import.
// import them one by one
class ewm_hf_import_manager
{
    public static function init()
    {
        // add_action();
        // ewm_hf_run_final_import
        // Redirect to relevant URL via Javascript
        add_action( "wp_ajax_nopriv_ewm_hf_run_final_import", [ "ewm_hf_import_manager", "ajax_import_scheduled_products" ] );
        add_action( "wp_ajax_ewm_hf_run_final_import", [ "ewm_hf_import_manager", "ajax_import_scheduled_products" ] );
        add_action( 'wp_footer', [ "ewm_hf_import_manager", "load_periodic_imports" ] );
    }
    
    public static function get_server_cpu_usage()
    {
        //$load = sys_getloadavg();
        //return $load[0];
        $performance = sys_getloadavg();
        $min = min( $performance );
        $max = max( $performance );
        $level = $max - $performance[0];
        return $level;
    }
    
    public static function ajax_import_scheduled_products()
    {
        $ewm_scheduled_list = get_option( 'ewm_scheduled_list' );
        $number_of_products = count( $ewm_scheduled_list );
        // check get_option if there are any outstanding products.
        // if there are any outstanding -> import them. else die.
        ewm_hf_import_manager::process_single_product();
        echo  json_encode( [
            'number_of_products' => $number_of_products,
        ] ) ;
        wp_die();
    }
    
    public static function ewm_do_import_process()
    {
        $my_domain_setting_field = get_option( 'my_domain_setting_field' );
        $my_setting_customer_key_field = get_option( 'my_setting_customer_key_field' );
        $my_setting_customer_secret_field = get_option( 'my_setting_customer_secret_field' );
        $ewm_categories_selected = maybe_unserialize( get_option( 'ewm_categories_selected' ) );
        $woocommerce = new Client(
            $my_domain_setting_field,
            $my_setting_customer_key_field,
            $my_setting_customer_secret_field,
            [
            "wp_json"           => true,
            "version"           => "wc/v1",
            "query_string_auth" => true,
        ]
        );
        // schedule products // get selected categories
        $ewm_categories_selected = get_option( 'ewm_categories_selected' );
        $ewm_scheduled_v = [];
        update_option( 'ewm_scheduled_list', $ewm_scheduled_v );
        // loop through each category while adding the products involved on to a list(option)
        foreach ( $ewm_categories_selected as $_k => $_v ) {
            //if($_v["state"] == "selected" ){
            // @TODO make the returned value to be just the product id
            foreach ( $_v["brands"] as $bk => $bv ) {
                $data_list = $woocommerce->get( 'products', [
                    'category' => $_k,
                    '_fields'  => 'id,name',
                    'search'   => $bv,
                ] );
                foreach ( $data_list as $k_l => $v_l ) {
                    $ewm_scheduled_v[$v_l->id] = [
                        'name'  => $v_l->name,
                        'id'    => $v_l->id,
                        'brand' => $bv,
                    ];
                }
            }
            // filter the brand of each product add that product id onto a list
            // }
        }
        update_option( 'ewm_scheduled_list', $ewm_scheduled_v );
    }
    
    public static function process_single_product()
    {
        $my_domain_setting_field = get_option( 'my_domain_setting_field' );
        $my_setting_customer_key_field = get_option( 'my_setting_customer_key_field' );
        $my_setting_customer_secret_field = get_option( 'my_setting_customer_secret_field' );
        $ewm_scheduled_list = get_option( 'ewm_scheduled_list' );
        $woocommerce = new Client(
            $my_domain_setting_field,
            $my_setting_customer_key_field,
            $my_setting_customer_secret_field,
            [
            "wp_json"           => true,
            "version"           => "wc/v1",
            "query_string_auth" => true,
        ]
        );
        if ( !is_array( $ewm_scheduled_list ) ) {
            $ewm_scheduled_list = [];
        }
        // loop through each category while adding the products involved on to a list(option)
        foreach ( $ewm_scheduled_list as $_k => $_v ) {
            $data_list = $woocommerce->get( 'products', [
                'include' => $_k,
            ] );
            // var_dump( $data_list );
            // $_v['brand']
            add_single_product( $data_list[0], $_v['brand'] );
            // remove singe product from list
            unset( $ewm_scheduled_list[$_k] );
            update_option( 'ewm_scheduled_list', $ewm_scheduled_list );
        }
    }
    
    public static function do_periodic_update()
    {
        // update next time to load: get current time stamp and add the required period
        $timestamp = time();
        $frequency_s = get_option( 'my_update_frequency_setting_field' );
        $regularity = [
            '1' => '+1 days',
            '2' => '+7 days',
            '3' => '+30 days',
            '4' => '+90 days',
        ];
        $_time_next = strtotime( $regularity[$frequency_s], $timestamp );
        update_option( 'f_of_time_to_update', $_time_next );
        // schedule import products
        ewm_hf_import_manager::ewm_do_import_process();
    }
    
    public static function load_periodic_imports()
    {
        // check next time -> if in the future do nothing if in the past "update time" and run schedule
        $f_of_time_to_update = intval( get_option( 'f_of_time_to_update' ) );
        $timestamp = time();
        $my_update_f = get_option( 'my_update_frequency_setting_field' );
        if ( $timestamp > $f_of_time_to_update ) {
            //do periodic update
        }
    }

}
ewm_hf_import_manager::init();
function ewm_hf_process_single_category( $args = array() )
{
    $ewm_categories_selected = $args['ewm_categories_selected'];
    $woocommerce = $args['woocommerce'];
    $selected_category_list_html_list = '';
    $list_of_categories_selected = '';
    $Item_number = 0;
    $single_cat_val = $args['single_cat_val'];
    $args['single_cat_key'];
    // If the brands array has brands, get the import the category
    // Remove all products
    
    if ( count( $args['single_cat_val']['brands'] ) > 0 ) {
        $list_of_categories_selected .= $single_cat_val['name'] . ' <br> ';
        // $selected_category_list_html_list .= ( $Item_number > 0 ? ',' : '');
        $selected_category_list_html_list .= $args['single_cat_key'];
        //$single_cat_val['id'];
        $Item_number++;
    }
    
    // Foreach
    $data_list = $woocommerce->get( 'products', [
        'category' => $selected_category_list_html_list,
        'per_page' => 100,
    ] );
    // echo $selected_category_list_html_list ;
    foreach ( $data_list as $single_product ) {
        // Confirm that product is on the brand list
        
        if ( $single_product ) {
            $product_brand_tag = ( property_exists( $single_product, 'product_brand_tag' ) ? $single_product->product_brand_tag : '' );
            if ( strlen( $product_brand_tag ) > 0 ) {
                
                if ( array_key_exists( $product_brand_tag, $args['single_cat_val']['brands'] ) ) {
                    // add or update the product
                    add_single_product( $single_product );
                } else {
                    // Remove the product
                    // ewm_hf_remove_product( $single_product );
                }
            
            }
        }
    
    }
}

// Run the shedule implement schedule, and update where it go to.
function ewm_hf_run_schedule( $args = array() )
{
    $my_domain_setting_field = get_option( 'my_domain_setting_field' );
    $my_setting_customer_key_field = get_option( 'my_setting_customer_key_field' );
    $my_setting_customer_secret_field = get_option( 'my_setting_customer_secret_field' );
    $ewm_categories_selected = maybe_unserialize( get_option( 'ewm_categories_selected' ) );
    $woocommerce = new Client(
        $my_domain_setting_field,
        $my_setting_customer_key_field,
        $my_setting_customer_secret_field,
        [
        "wp_json"           => true,
        "version"           => "wc/v1",
        "query_string_auth" => true,
    ]
    );
    ini_set( 'max_execution_time', '10000' );
    // set_time_limit( 10000 );
    // Add record
    foreach ( $ewm_categories_selected as $single_cat_key => $single_cat_val ) {
        [
            'status'        => 'complete',
            'last_category' => 0,
        ];
        if ( !array_key_exists( $single_cat_key, $args['last_category'] ) ) {
            ewm_hf_process_single_category( [
                'ewm_categories_selected' => $ewm_categories_selected,
                'single_cat_key'          => $single_cat_key,
                'single_cat_val'          => $single_cat_val,
                'woocommerce'             => $woocommerce,
                'options_details'         => $args,
            ] );
        }
    }
}

// The function listens if there is an incomplete schedule.
function ewm_hf_check_for_active_schedule()
{
    // If there is an active schedule -> Activate schedule module
    $ewm_hf_active_schedule = maybe_unserialize( get_option( 'ewm_hf_active_schedule' ) );
    
    if ( $ewm_hf_active_schedule == false ) {
        $schedule_details = [
            'status'        => 'complete',
            'last_category' => [ 0 ],
        ];
        add_option( 'ewm_hf_active_schedule', $schedule_details );
    } else {
        if ( $ewm_hf_active_schedule['status'] == 'ongoing' ) {
            ewm_hf_run_schedule( $ewm_hf_active_schedule );
        }
    }

}

add_action( 'init', 'ewm_hf_check_for_active_schedule' );
function ewm_get_products_by_categories()
{
    $list_of_categories_selected = [ 'Product 1', 'product 2' ];
    $data_list = [];
    // get categories.
    // process each category using schedules
    // give response
    $response_data = [
        'list_of_product_categories' => $list_of_categories_selected,
        'number_of_products'         => count( $data_list ),
    ];
    return json_encode( $response_data );
}

// add_action( 'wp_footer' , 'ewm_get_categories_load' ) ;
// get_option( 'my_domain_setting_field' )
// get_option( 'my_setting_customer_key_field'
// get_option( 'my_setting_customer_secret_field' )
// get_option( 'my_update_frequency_setting_field' )
//set_time_limit(0);
function post_summary_data()
{
    $woocommerce = new Client(
        get_option( 'my_domain_setting_field' ),
        get_option( 'my_setting_customer_key_field' ),
        get_option( 'my_setting_customer_secret_field' ),
        [
        "wp_json"           => true,
        "version"           => "wc/v1",
        "query_string_auth" => true,
    ]
    );
    // var_dump( $woocommerce ) ;
    // $woocommerce->get( $endpoint, $parameters = [] ) ;
    $data_list = $woocommerce->get( 'products/categories', [
        'per_page' => 100,
    ] );
    //new sample_data() ;
    // echo '<br> <br> ';
    echo  count( $data_list ) ;
    $html_list = '';
    foreach ( $data_list as $single_cat ) {
        $html_list .= ' [' . $single_cat->id . '] ' . $single_cat->name . '<br>';
        // ["id"]=> int(68)
        // ["name"]=> string(3) "Air"
    }
    echo  $html_list ;
    //var_dump( $data_list ) ;
    foreach ( $data_list as $single_product ) {
        // add_single_product( $single_product );
    }
    return count( $data_list );
    // var_dump( $data_list ) ;
    // > echo 'Content type:'. get_post_type();
    // $id = get_the_ID();
    // wp_set_post_tags( int $post_id, string|array $tags = '', bool $append = false )
    // echo 'Post Id:'.$id ;
    // $post = get_post();
    // var_dump( $post );
    // $post_meta = get_post_meta( $id ) ;
    // var_dump( maybe_unserialize( $post_meta["_wpbdp[images]"][0] ) ) ;
    // var_dump( $post_meta );
    // $post_cat = get_terms( 'category' ); //get_the_category(); //category_description($id);
    //> echo '<br><br>Category:';
    // var_dump( $post_cat );
    // echo '<br><br>';
    // var_dump( get_the_tags( $id ) ) ; //get_categories());
    // Add term to man list if it does not exist
    // Associate post to
    // $term_r = wp_insert_term('seed', 'wpbdp_category');
    // echo "<br><br>Insert term:<br><br>";
    // var_dump( $term_r );
    // echo '<br><br>Single term:<br><br>';
    // $return_tax = get_term_by( 'name', 'seed', 'wpbdp_category') ;
    // echo '<br><br>';
    /*
    
        var_dump( get_terms( array(
    
            'taxonomy' => 'wpbdp_category',
            'hide_empty' => false,
    
        ) ) ) ;
    
        wp_set_post_terms( $id , [ $return_tax->term_id ] , 'wpbdp_category' ) ;
    */
    /*
        
            wp_insert_term('seed', 'wpbdp_category', array(
                'description' => 'seed',
                'slug' => 'seed'
            ));
    */
    // get_term_meta( int $term_id, string $key = '', bool $single = false )
    //wp_insert_post( $post_data, $wp_error );
}

function my_admin_menu()
{
    add_menu_page(
        __( 'HVAC Feed', 'my-textdomain' ),
        __( 'HVAC Feed', 'my-textdomain' ),
        'manage_options',
        'woocommerce-child',
        'my_admin_page_contents',
        'dashicons-filter',
        3
    );
}

add_action( 'admin_menu', 'my_admin_menu' );
function my_admin_page_contents()
{
    ?>
    <div class="hvac_admin_container">
        <h1>
            <?php 
    esc_html_e( 'HVAC Feed for Woocommerce', 'my-plugin-textdomain' );
    ?>
        </h1>

        <form method="POST" action="options.php">

            <?php 
    // $ewm_hf_server_cpu_usage = ewm_hf_import_manager::get_server_cpu_usage();
    // ewm_hf_import_manager::ewm_do_import_process();
    // ewm_hf_import_manager::process_single_product();
    settings_fields( 'sample-page' );
    do_settings_sections( 'sample-page' );
    // submit_button();
    ?>

        </form>

    </div>
<?php 
}

add_action( 'admin_init', 'my_settings_init' );
function my_settings_init()
{
    add_settings_section(
        'sample_page_setting_section',
        __( '', 'my-textdomain' ),
        'my_setting_section_callback_function',
        'sample-page'
    );
    add_settings_field(
        'my_domain_setting_field',
        __( 'Domain name', 'my-textdomain' ),
        'my_setting_domain_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    add_settings_field(
        'my_setting_customer_key_field',
        __( 'Customer key', 'my-textdomain' ),
        'my_setting_customer_key_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    add_settings_field(
        'my_setting_customer_secret_field',
        __( 'Customer secret', 'my-textdomain' ),
        'my_setting_customer_secret_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    add_settings_field(
        'my_update_frequency_setting_field',
        __( 'Frequency of update', 'my-textdomain' ),
        'my_update_frequency_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    add_settings_field(
        'my_update_category_setting_field',
        __( 'Select Categories to be Imported', 'my-textdomain' ),
        'my_update_category_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    add_settings_field(
        'my_update_products_setting_field',
        __( '', 'my-textdomain' ),
        'my_update_product_markup',
        'sample-page',
        'sample_page_setting_section'
    );
    register_setting( 'sample-page', 'my_domain_setting_field' );
    register_setting( 'sample-page', 'my_setting_customer_key_field' );
    register_setting( 'sample-page', 'my_setting_customer_secret_field' );
    register_setting( 'sample-page', 'my_update_frequency_setting_field' );
    register_setting( 'sample-page', 'my_update_products_setting_field' );
    register_setting( 'sample-page', 'my_update_category_setting_field' );
}

function my_update_product_markup()
{
    echo  '<input type="button" value="Click to Update All Products(Selected brands and categories)" id="load_all_products" /> <span style="color:gray; padding:5px;" id="loading_update_message"></span> <style>.submit{  text-align: center !important; }</style>' ;
}

function my_update_category_markup()
{
    ewm_display_categories();
    ewm_get_categories_selected();
}

function my_setting_section_callback_function()
{
    // echo  '<p>Please fill in the following fields</p>' ;
}

function my_setting_domain_markup()
{
    ?>
        <input type="text" id="my_domain_setting_field" name="my_domain_setting_field" class="ewm_text_field_details" value="<?php 
    echo  get_option( 'my_domain_setting_field' ) ;
    ?>">
    <?php 
}

function my_setting_customer_key_markup()
{
    ?>
    <input type="text" id="my_setting_customer_key_field" name="my_setting_customer_key_field" class="ewm_text_field_details" value="<?php 
    echo  get_option( 'my_setting_customer_key_field' ) ;
    ?>">

<?php 
}

function my_setting_customer_secret_markup()
{
    ?>

    <input type="text" id="my_setting_customer_secret_field" name="my_setting_customer_secret_field" class="ewm_text_field_details" value="<?php 
    echo  get_option( 'my_setting_customer_secret_field' ) ;
    ?>">

<?php 
}

function my_update_frequency_markup()
{
    $option_selection = get_option( 'my_update_frequency_setting_field' );
    ?>
    <select name='my_update_frequency_setting_field' class="ewm_text_field_details">
        <option value='1' <?php 
    selected( $option_selection, 1 );
    ?>> Daily </option>
        <option value='2' <?php 
    selected( $option_selection, 2 );
    ?>> Weekly </option>
        <option value='3' <?php 
    selected( $option_selection, 3 );
    ?>> Monthly </option>
        <option value='4' <?php 
    selected( $option_selection, 4 );
    ?>> Every 3 Months </option>
    </select>
    <?php 
}

function ws_process_categories_list_()
{
    $category_list_arr = [];
    $e_id_ewmlist_data_ewm_details = explode( ',', $_POST['e_id_ewmlist_data_ewm_details'] );
    //Make all the data into an array and save it as an array.
    foreach ( $e_id_ewmlist_data_ewm_details as $single_arr_data ) {
        $category_list_arr[$single_arr_data] = [
            'name'  => $_POST['e_name_id_ewmlist_data_' . $single_arr_data],
            'state' => $_POST['e_state_ewmlist_data_' . $single_arr_data],
            'id'    => $_POST['e_id_ewmlist_data_' . $single_arr_data],
        ];
    }
    $category_list_arr = maybe_serialize( $category_list_arr );
    update_option( 'ewm_categories_selected', $category_list_arr );
}

// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_ws_process_categories_list_", "ws_process_categories_list_" );
add_action( "wp_ajax_ws_process_categories_list_", "ws_process_categories_list_" );
function ewm_update_single_option()
{
    // $new_value = json_decode( stripslashes( $_POST['cat_list'] ), true );
    update_option( $_POST['ewm_hf_option_name'], $_POST['ewm_hf_option_value'] );
    echo  json_encode( [
        'status' => 'done',
        'post'   => $_POST,
    ] ) ;
    wp_die();
}

// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_ewm_update_single_option", "ewm_update_single_option" );
add_action( "wp_ajax_ewm_update_single_option", "ewm_update_single_option" );
function ewm_cat_save_()
{
    $new_value = json_decode( stripslashes( $_POST['cat_list'] ), true );
    update_option( 'ewm_categories_selected', $new_value );
    echo  json_encode( [
        'status' => 'done',
    ] ) ;
    wp_die();
}

// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_ewm_cat_save_", "ewm_cat_save_" );
add_action( "wp_ajax_ewm_cat_save_", "ewm_cat_save_" );
// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_wp_periodic_update_all_products", "wp_periodic_update_all_products" );
add_action( "wp_ajax_wp_periodic_update_all_products", "wp_periodic_update_all_products" );
function wp_periodic_update_all_products()
{
    // Check the set time
    $f_of_time_to_update = get_option( 'f_of_time_to_update' );
    $number_of_products = 'x';
    $number_of_products = ewm_get_products_by_categories();
    $time_s = time();
    
    if ( $f_of_time_to_update == false ) {
        // Do update.
        $number_of_products = ewm_get_products_by_categories();
    } else {
        if ( $f_of_time_to_update < $time_s ) {
            // Do update.
            $number_of_products = ewm_get_products_by_categories();
        }
    }
    
    echo  $number_of_products ;
    wp_die();
    /*
        echo json_encode([
            'number_of_products' => $number_of_products
        ] ) ;
        wp_die() ;
    */
}

// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_ws_process_product_import", "wc_save_new_list" );
add_action( "wp_ajax_ws_process_product_import", "wc_save_new_list" );
function wc_save_new_list()
{
    ewm_hf_import_manager::ewm_do_import_process();
    $ewm_scheduled_list = get_option( 'ewm_scheduled_list' );
    $number_of_products = count( $ewm_scheduled_list );
    $ewm_categories_selected = get_option( 'ewm_categories_selected' );
    $cat_number = 0;
    foreach ( $ewm_categories_selected as $k_m => $v_m ) {
        if ( count( $v_m['brands'] ) > 0 ) {
            $cat_number++;
        }
    }
    echo  json_encode( [
        'number_of_products'         => $number_of_products,
        'list_of_product_categories' => $cat_number,
    ] ) ;
    wp_die();
}

add_filter( 'woocommerce_product_meta_end', 'display_company_details_filter', 20 );
function display_company_details_filter( $params = '' )
{
    // global $product;
    global  $post ;
    $brand_name_d = '';
    $brand_name_d = esc_attr( get_post_meta( $post->ID, 'product_brand_tag', true ) );
    // $single_cat->name ;
    if ( strlen( $brand_name_d ) ) {
        $brand_name_d = "<div style='width:100%;'> Brand: <b>" . $brand_name_d . '</b> </div>';
    }
    echo  $brand_name_d ;
}

// Redirect to relevant URL via Javascript
add_action( "wp_ajax_nopriv_ewm_hf_update_cat_list", "ewm_hf_update_cat_list" );
add_action( "wp_ajax_ewm_hf_update_cat_list", "ewm_hf_update_cat_list" );
function ewm_hf_update_cat_list( $args = array() )
{
    // Read the cat list.
    $ewm_categories_selected = maybe_unserialize( get_option( 'ewm_categories_selected' ) );
    // Update the cat list
    $_POST['cat_list'];
    update_option( 'ewm_categories_selected', $_POST );
    //var_dump( $ewm_categories_selected );
    // Post
    // Save in options
    // Return update successful -> throw it in logs
    //
}

add_action( 'woocommerce_after_shop_loop_item_title', 'display_company_details' );
function display_company_details()
{
    // global $product;
    global  $post ;
    $brand_name_d = '';
    $brand_name_d = esc_attr( get_post_meta( $post->ID, 'product_brand_tag', true ) );
    // $single_cat->name ;
    if ( strlen( $brand_name_d ) ) {
        $brand_name_d = "<div style='width:100%;'> Brand: <b>" . $brand_name_d . '</b> </div>';
    }
    echo  $brand_name_d ;
}

// Add field:
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'my_meta_box',
        'Product brand',
        function ( $post ) {
        wp_nonce_field( __FILE__, '_product_brand_data_nonce' );
        $_product_brand_data = esc_attr( get_post_meta( $post->ID, 'product_brand_tag', true ) );
        ?>
            <p>
                <select class="large-text" name="product_brand_data">
                    <optgroup label="Product Brands">
                        <option value="Unspecified" <?php 
        echo  ( $_product_brand_data == 'Unspecified' ? 'selected="selected"' : '' ) ;
        ?>> Unspecified </option>
                        <option value="Tempstar" <?php 
        echo  ( $_product_brand_data == 'Tempstar' ? 'selected="selected"' : '' ) ;
        ?>> Tempstar </option>
                        <option value="Crown Boiler Co" <?php 
        echo  ( $_product_brand_data == 'Crown Boiler Co' ? 'selected="selected"' : '' ) ;
        ?>>Crown Boiler Co</option>
                        <option value="Crown Mega-Stor" <?php 
        echo  ( $_product_brand_data == 'Crown Mega-Stor' ? 'selected="selected"' : '' ) ;
        ?>>Crown Mega-Stor</option>
                        <option value="Weil-McLain" <?php 
        echo  ( $_product_brand_data == 'Weil-McLain' ? 'selected="selected"' : '' ) ;
        ?>>Weil-McLain</option>
                        <option value="Velocity Boiler Works" <?php 
        echo  ( $_product_brand_data == 'Velocity Boiler Works' ? 'selected="selected"' : '' ) ;
        ?>>Velocity Boiler Works</option>
                    </optgroup>
                </select>
            <?php 
    },
        'product',
        'side'
    );
} );
// Save field.
add_action( 'save_post', function ( $post_id ) {
    if ( isset( $_POST['product_brand_data'] ) ) {
        //}, $_POST['_product_brand_data_nonce'] ) && wp_verify_nonce( $_POST['_product_brand_data_nonce'], __FILE__ ) ) {
        update_post_meta( $post_id, 'product_brand_tag', sanitize_text_field( $_POST['product_brand_data'] ) );
    }
} );
function woocommerce_sort_by_brand_filter()
{
    $category_list = woo_c_category_list();
    $category_list .= '
        <div style="width:100%;" >
            <b>Filter by brand </b>
            <select class="large-text" name="woo_front_product_brand_data" id="woo_front_product_brand_data">
                <optgroup label="Product Brands">
                    <option value="Unspecified"> Unspecified </option>
                    <option value="Tempstar"> Tempstar </option>
                    <option value="Crown Boiler Co">Crown Boiler Co</option>
                    <option value="Crown Mega-Stor">Crown Mega-Stor</option>
                    <option value="Weil-McLain">Weil-McLain</option>
                    <option value="Velocity Boiler Works">Velocity Boiler Works</option>
                </optgroup>
            </select>
        </div>
    ';
    $woo_c_cat = get_queried_object();
    // replace this with $_POST['']
    $woo_c_cat_term_id = 0;
    if ( 'WP_Term' == get_class( $woo_c_cat ) ) {
        $woo_c_cat_term_id = $woo_c_cat->term_id;
    }
    $category_list .= '<script type="text/javascript">
            var woo_c_cat_term_id  = ' . $woo_c_cat_term_id . ' ;
        </script>
        <style>
            .woocommerce-ordering{
                display:none !important;
            }

            .woocommerce ul.products li.first, .woocommerce-page ul.products li.first {
                clear: initial !important;
            }

            .woocommerce ul.products li.product, .woocommerce-page ul.products li.product {
                margin: 0 2.9% 2.992em 0 !important ;
                height: 480px;
            }

            .woocommerce ul.products li.product a img {
                height: 300px !important;
            }

            @media only screen and (min-width: 600px) {

                .woo_child_category_list_left{
                    float : left;
                    width : 20%;
                    min-width : 275px ;
                    margin-right : 2.5%;
                }

                .products{
                    float : left;
                    width : 75%;
                    min-width : 275px;
                }

                .woocommerce-pagination{
                    width: 75%;
                    float: right;
                }


                .woo_child_category_filter_button{
                    display: none ;
                    /*needs to be fixed*/
                }

            }

            .woo_child_category_filter_button{

                width:100%;
                color:#fff;
                background:black;
                padding:10px 20px;
                border-radius:3px;
                vertical-align : middle ;

            }

            .woocommerce ul.products, .woocommerce-page ul.products {
                clear: inherit;
            }

            .woo_child_category_buttons{
                overflow: auto;
                padding-bottom: 30px;
            }

            ul .product-search-filter-items{
                list-style-position: inside !important;
                padding-left: 0px !important;
            }

            body:not([class*=elementor-page-]) .site-main {
                max-width: 1350px;
            }

            @media only screen and (max-width: 600px) {

                .woo_child_category_list_left {
                    display : none ;
                    width: 100% ;
                    border : solid gray 1px ;
                    padding: 15px ;
                }

                .products {
                    width: 100%;
                }

                .woocommerce-pagination{
                    width: 100%;
                }

                .woo_child_category_filter_button{
                    display: block ;
                    position: sticky; 
                    top:0;
                    /*needs to be fixed*/
                }
                .container_filter {
                    display: inline-block;
                    cursor: pointer;
                    margin-right: 5px ;
                }
                  
                .bar1, .bar2, .bar3 {
                    width: 15px;
                    height: 3px;
                    background-color: #fff ;
                    margin: 1px 0;
                    transition: 0.4s;
                }
                  
                /* Rotate first bar */
                .change .bar1 {
                    -webkit-transform: rotate(-45deg) translate(-9px, 6px) ;
                    transform: rotate(-45deg) translate(-9px, 6px) ;
                }
                  
                /* Fade out the second bar */
                .change .bar2 {
                    opacity: 0;
                }
                  
                /* Rotate last bar */
                .change .bar3 {
                    -webkit-transform: rotate(45deg) translate(-8px, -8px) ;
                    transform: rotate(45deg) translate(-8px, -8px) ;
                }

            }

        </style>
        ';
    return '<div class="woo_child_category_filter_button">
             
            <div class="container_filter"">
                <div class="bar1"></div>
                <div class="bar2"></div>
                <div class="bar3"></div>
            </div>
            Filter Products

        </div>
        <div class="woo_child_category_list_left" >' . $category_list . '</div>';
}

function woo_c_category_list()
{
    $category_array = [];
    $woo_c_cat_primary = get_categories( [
        'taxonomy' => 'product_cat',
        'orderby'  => 'name',
        'order'    => 'ASC',
    ] );
    foreach ( $woo_c_cat_primary as $cat => $cat_v ) {
        $category_array[$cat_v->term_id] = $cat_v->name;
    }
    //todo get list from db
    $return_html = '
        <div width="100%" class="woo_child_category_buttons" >
        
        <span class="woo-c-clear">Clear Category Selection [x]</span></div>

        <div width="100%" style="overflow: auto; padding-bottom:40px; " >
        <ul class="product-categories product-search-filter-items product-search-filter-category product-search-filter-product_cat style-list hide-thumbnails show-names product-search-filter-toggle product-search-filter-toggle-widget" style="list-style-position: inside; padding-left: 0px;" >';
    foreach ( $category_array as $key => $value ) {
        $return_html .= '<li data-term="' . $key . '" data-taxonomy="product_cat" class="cat-item cat-item-' . $key . ' product-search-product_cat-filter-item product-search-attribute-filter-item" ><span data-term = "' . $key . '" class="term-name">' . $value . '</span></li>';
    }
    $return_html .= '</ul>
        </div>

        ';
    $return_html .= '<script type="text/javascript">

            var woo_c_category_list = [] ;

        ';
    $arr_index = 0;
    foreach ( $category_array as $key => $value ) {
        $return_html .= ' woo_c_category_list[ ' . $arr_index . ' ] = {

                "index" :"' . $key . '",

                "name"  :"' . $value . '",

            };
            ';
        $arr_index++;
    }
    $return_html .= '

        // console.log(woo_c_category_list);

        </script>

        ';
    return $return_html;
}

add_shortcode( 'woocommerce_sort_by_brand_filter', 'woocommerce_sort_by_brand_filter' );
add_action( 'woocommerce_before_shop_loop', function () {
    echo  do_shortcode( "[woocommerce_sort_by_brand_filter]" ) ;
} );
add_action( "wp_ajax_nopriv_woo_c_filter_brand_and_category", "woo_c_filter_brand_and_category" );
add_action( "wp_ajax_woo_c_filter_brand_and_category", "woo_c_filter_brand_and_category" );
function woo_c_filter_brand_and_category()
{
    $woo_c_cat_term_id = $_POST['woo_c_cat_term_id'];
    // "150","woo_c_brand_name":"Crown Boiler Co"
    $woo_c_brand_name = $_POST['woo_c_brand_name'];
    // replace this with "Weil-McLain";
    $args = array(
        'post_type' => 'product',
    );
    if ( strlen( $woo_c_brand_name ) > 0 && $woo_c_brand_name !== 'Unspecified' ) {
        $args['meta_query'] = array( array(
            'key'     => 'product_brand_tag',
            'value'   => $woo_c_brand_name,
            'compare' => '=',
        ) );
    }
    if ( $woo_c_cat_term_id !== "0" ) {
        $args['tax_query'] = [ [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $woo_c_cat_term_id,
        ] ];
    }
    $query = new WP_Query( $args );
    $product_list = [];
    $array_numb = 0;
    foreach ( $query->posts as $single_product ) {
        $img_src = get_the_post_thumbnail_url( $single_product->ID );
        $product_list[$array_numb] = [
            'product_id'    => $single_product->ID,
            'product_title' => $single_product->post_title,
            'image_url'     => $img_src,
            'brand'         => get_post_meta( $single_product->ID, 'product_brand_tag', true ),
            'product_url'   => $single_product->guid,
        ];
        $array_numb++;
    }
    echo  json_encode( [
        'post'    => $_POST,
        'product' => $product_list,
    ] ) ;
    wp_die();
}

// ini_set( 'display_errors', 1 );
// ini_set( 'display_startup_errors', 1 );
// error_reporting( E_ALL );