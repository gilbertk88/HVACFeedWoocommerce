jQuery(document).ready(function($) {

    // alert( screen.width ) ;

    $('.woo_child_category_filter_button').click(function(){

        $('.woo_child_category_list_left' ).toggle() ;

        $( this ).classList.toggle( "change" ) ;

    } ) ;

    // Listening function
    $( '#woo_front_product_brand_data' ).change(function() {

        if( $('#woo_front_product_brand_data').val() == 'Unspecified' ){

            location.reload();

        }
        else{
            // Get the category
            woo_c_search_brand_cat() ;

            $('.woocommerce-pagination').hide();

            $('.woocommerce-result-count').hide();

        }

        // Get the brand
        // Get the relevant products
        // Run search function
        // Product write function

    } ) ;

    $( '.product-search-product_cat-filter-item' ).click( function( e ){

        e.preventDefault();

        // De-highlight all categories
        $('.product-search-product_cat-filter-item').css( { "background": "#cc3366d1","border": "1px solid #cc3366d1","color": "#fff" } ) ;

        // Highlight the relevant category
        $( this ).css({ "background":"#333", "border":"solid 1px gray"})

        // Change cat id

    } ) ;

    // PHP search function =>  return array products and write on the relevant div
    function woo_c_intial_listen(){

        $( ".term-name" ).click( function(){

            // Change product class to product unterim
            woo_c_cat_term_id = $( this ).parent().attr( 'data-term' ) ;

            // Change category id
            woo_c_search_brand_cat() ;

            $('.woo-c-clear' ).show() ;

            $( '.woo-c-clear' ).click( function(){

                woo_c_cat_term_id = 0 ;

                $( '.woo-c-clear' ).hide() ;

                $('.product-search-product_cat-filter-item').css( { "background": "#cc3366d1","border": "1px solid #cc3366d1","color": "#fff" } ) ;

                woo_c_search_brand_cat() ;

                location.reload();

            } ) ;

            $('.woocommerce-pagination').hide();

            $('.woocommerce-result-count').hide();

        } ) ;

    }

    woo_c_intial_listen(); 

    function woo_c_single_product_html( args = [] ){

        return '<li class="product type-product post-'+ args.product_id+' status-publish first instock product_cat-thermostats has-post-thumbnail shipping-taxable product-type-simple"> \
            <a href="'+args.product_url+'" class="woocommerce-LoopProduct-link woocommerce-loop-product__link"> \
                <img width="300" height="300" src="'+ args.image_url +'" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="" loading="lazy" sizes="(max-width: 300px) 100vw, 300px">\
                <h2 class="woocommerce-loop-product__title">'+args.product_title+'</h2> \
                <div style="width:100%;"> Brand: <b>'+ args.brand+'</b> </div>\
            </a> \
            <a href="'+args.product_url+'" data-quantity="1" class="button product_type_simple" data-product_id="'+ args.product_id+'" data-product_sku="" aria-label="Read more about '+args.product_title+'" rel="nofollow">Read more</a>\
        </li> ';

    };

    function woo_c_all_product_html( args = [] ){

        final_htm = '' ; //woo_c_single_product_html();

        console.log( args.product.length );

        for( i = 0 ; i < args.product.length ; i++ ){

            console.log( 'Product number: ' );

            console.log( i );

            final_htm = final_htm + woo_c_single_product_html( args.product[i] ) ;

        }

        return final_htm ;

    } ;

    function woo_c_search_brand_cat() {

        //console.log( 'Category term id: ' + woo_c_cat_term_id ) ;

        var form_data = new FormData() ;
        
        form_data.append( 'action', 'woo_c_filter_brand_and_category' ) ;

        form_data.append( 'woo_c_cat_term_id', woo_c_cat_term_id ) ;
            
        form_data.append( 'woo_c_brand_name' , $('#woo_front_product_brand_data').val() ) ;

        jQuery.ajax( {

            url: ajax_object.ajaxurl,

            type: 'post',

            contentType: false,

            processData: false,

            data: form_data,

            success: function ( response ) {

                console.log( response );

                response = jQuery.parseJSON( response ) ;

                final_htm = woo_c_all_product_html( response ) ;

                $('.products').html( final_htm ) ;

            } ,

            error: function ( response ) {

            }

        } ) ;

    }

} ) ;