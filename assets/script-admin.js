jQuery(document).ready(function($) {

    // <div class="ewm_cat_list ewm_cat_selected" data-category_key_id="'. $single_cat->id .'" data-category_selected="not_selected" ></div>

    function update_cats_on_server( data ) {

        var form_data = new FormData() ;
        
        form_data.append( 'action', 'ws_process_categories_list_' ) ;

        form_data.append( 'category_list', data ) ;

        ewm_details = '' ; 
        
        $( '.ewm_cat_list' ).each( function(){

            name_id = $( this ).data( 'category_name_id' ) ;

            id = $( this ).data( 'category_key_id' ) ;

            state = $( this ).data( 'category_selected' ) ;

            form_data.append( 'e_name_id_ewmlist_data_'+id ,  name_id ) ;

            form_data.append( 'e_state_ewmlist_data_'+id ,  state ) ;

            form_data.append( 'e_id_ewmlist_data_'+id ,  id ) ;

            ewm_details = ewm_details + ',' + id ;
        
        } ) ;

        form_data.append( 'e_id_ewmlist_data_ewm_details' ,  ewm_details ) ;

        // console.log( data_to_send ) ;
        // data_to_send = jQuery.parseJSON( data_to_send ) ;

        jQuery.ajax( {

            url: ajax_object.ajaxurl,

            type: 'post',

            contentType: false,

            processData: false,

            data: form_data,

            success: function ( response ) {

                // response = jQuery.parseJSON( response ) ;

                console.log( response ) ;

            } ,

            error: function ( response ) {

                console.log( response ) ;

            }

        } ) ;

    }

        var ewm_update_cat_selection_on_server = function(e){

            data_to_send = [] ;
            update_cats_on_server( data_to_send ) ;
            //console.log( data_to_send );

        }

    $( '.ewm_cat_list' ).click( function(e) {

        // category_selected = $( this ).data( 'category_selected' ) ;
        /*if( category_selected == 'selected' ){ // if selected -> de-selected
            // remove class ewm_cat_selected
            $( this ).removeClass( 'ewm_cat_selected' ) ;
            // add class ewm_cat_not_selected
            $( this ).addClass( 'ewm_cat_not_selected' ) ;
            // edit local state of the category list.
            $( this ).data('category_selected', 'not_selected' );
            // edit state in server
            ewm_update_cat_selection_on_server() ;
        }
        else if( category_selected == 'not_selected' ){ // if not selected -> select
            // Add class ewm_cat_selected
            //$( this ).addClass( 'ewm_cat_selected' ) ;
            // Remove class ewm_not selected
            //$( this ).removeClass( 'ewm_cat_not_selected' ) ;
            // Edit local state of the category list. as array
            //$( this ).data('category_selected', 'selected' ) ;
            // Edit state in server as a whole. => update_options
            //ewm_update_cat_selection_on_server() ;
        }*/

    } ) ;

    function ewm_hf_run_final_import(){

        $('#loading_update_message').append( '<br>Importation process started... <br>' );
        
        var form_data = new FormData() ;
        form_data.append( 'action', 'ewm_hf_run_final_import' );
        form_data.append( 'products_to_load', 'all' );

        jQuery.ajax( {
            url: ajax_object.ajaxurl,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function ( response ) {
                console.log( response );
                response = jQuery.parseJSON( response ) ;
                $('#loading_update_message').append( '<br>Importation process complete! '+response.number_of_products+' have been imported/updated. <br>' );
            },
            error: function (response) {
                console.log( response ) ;
                $('#loading_update_message').html('') ;
            }

        } ) ;

    }

    function ewm_hf_upload_and_save_text() {

        $('#loading_update_message').html('');
        $('#loading_update_message').html('<br>Scheduling products to be imported...<br>') ;

        var form_data = new FormData() ;
        form_data.append( 'action', 'ws_process_product_import' );
        form_data.append( 'products_to_load', 'all' );

        jQuery.ajax( {
            url: ajax_object.ajaxurl,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function ( response ) {
                console.log( response );
                response = jQuery.parseJSON( response ) ;
                $('#loading_update_message').append( '<br>Products have been successfully scheduled! <br>' ) ;
                ewm_hf_run_final_import();
            },
            error: function (response) {
                console.log( response ) ;
                $('#loading_update_message').html('') ;
            }
        } ) ;

    }

    $("#load_all_products").click(function(e){
        
        e.preventDefault();
        ewm_hf_upload_and_save_text() ;

    } ) ;

    function ewm_hf_update_the_new_category_selection(){

        var form_data = new FormData() ;
        form_data.append( 'action', 'ewm_cat_save_' );
        form_data.append( 'cat_list', JSON.stringify( server_data_list ) );

        jQuery.ajax( {
            url: ajax_object.ajaxurl,
            type: 'post',
            contentType: false,
            processData: false,
            data: form_data,
            success: function ( response ) {
                console.log( response );
                response = jQuery.parseJSON( response ) ;
            },
            error: function (response) {
                console.log( response ) ;
                $('#loading_update_message').html('') ;
            }
        });

    }

    function wp_periodic_update_all_products() {

        console.log('Checking of logs...');

        var form_data = new FormData() ;
        
        form_data.append( 'action', 'wp_periodic_update_all_products' ) ;

        form_data.append( 'products_to_load', 'all' );

        jQuery.ajax( {

            url: ajax_object.ajaxurl,

            type: 'post',

            contentType: false,

            processData: false,

            data: form_data,

            success: function ( response ) {

                // console.log( response ) ;

            },

            error: function (response) {

                //console.log( response ) ;

            }

        } ) ;

    }

    // wp_periodic_update_all_products() ;

    ///console.log( server_data_list );

    // $('#brand_and_cats').html( server_data_list );
    // Load existing list in frontend 

function add_the_brand_name( brandname , ewm_hf_section_id ){

    ewm_hf_active_brands[ brandname ].status = 'active';

    ewm_hf_active_brands[ brandname ].id = ewm_hf_section_id ;
    
}

function brand_is_already_active( product_brand_val , current_node ){

    if_active = false ;

    // Make sure there is no categories selected
    $(".product_brand_data_hidden").each( function( sample_key , sample_data ){

        // console.log( $(".product_brand_data_hidden:eq(" + sample_key + ")" ).attr('disabled') );
        if( product_brand_val == $(".product_brand_data_hidden:eq(" + sample_key + ")" ).val() && $(".product_brand_data_hidden:eq(" + sample_key + ")" ).attr('disabled') == 'disabled' ){

            $( '.ewm_hf_main_body' ).html( '<center><b>The brand "'+product_brand_val+'" has already be set</center></b>' );

            $( '.ewm_lower_controls_body' ).hide();

            $( '.ewm_out_blur' ).show();

            $( '.ewm_hf_message_error_place' ).css( {'background-color':'red' } );

            $("html").animate(
                {
                    scrollTop: $( ".product_brand_data_hidden:eq("+sample_key +")" ).offset().top
                },
                800 //speed
            );

            if_active = true;
            
        }

    } );

    return if_active;

}

function ewm_hf_delete_brand_and_cat( section_id ){

    // This step comes from the previous step(Do Friction)
    brand_name = $( '.ewm_hf_brand_dropdown_'+ section_id ).val();

    // Remove brand from ewm_hf_active_brands
    ewm_hf_active_brands[ brand_name ].status = 'inactive' ;

    ewm_hf_active_brands[ brand_name ].id = 0;

    // Remove brand from categories affected
    // server_data_list[ category_key_id ];
    $.each( server_data_list , function( sample_key , sample_data ){

        // server_data_list[sample_key].brands = [];
        myArray = server_data_list[sample_key].brands

        myArray.splice(_.findIndex( myArray, function(item) {

            return item == brand_name ;

        }), 1);
        
    });

    $('.ewm_out_blur').hide();
    
    // Remove section on client side
    $( '.main_brand_and_cats_' + section_id ).remove();

    // Update server
    ewm_hf_update_the_new_category_selection( server_data_list );

}

function ewm_hf_categories_selected( section_name ){

    console.log( ewm_hf_active_brands['product_brand_val'] );
    
}

var ewm_hf_delete = 0;

function ewm_hf_filter_listeners() {

    $('.product_brand_data_hidden').change( function() {

        product_brand_val = $( this ).val();

        ewm_hf_section_id = $( this ).data( 'section-id' );

        // Confirm the brand is not in ewm_hf_active_brands
        // If the brand is in ewm_hf_active_brands throw an exception

        // $('.product_brand_data_hidden').show();
        // If there are categories selected, revert to previous
        if( product_brand_val !== 'Unspecified' ){

            if( brand_is_already_active( product_brand_val  , $( this ) ) ){

                $( this ).val( 'Unspecified' );

                return;

                // throw an error - this brand is already selected.
                // focus on the active section slowly and set existing one ot unspecified.

            }
            else{

                add_the_brand_name( product_brand_val , ewm_hf_section_id );
                $('.checkbox_cat_parent').first().show();
                $('.checkbox_cat_parent').first().removeClass('checkbox_cat_parent');
                $('.ewm_hf_message_error_place').css({ "background-color":"#9acd32a1" });
                $('.ewm_hf_message_error_place').removeClass( 'ewm_hf_message_error_place' );
                $( this ).attr( "disabled","true" ) ;

            }

        }

        // If not on ewm_hf_active_brands add it and remove the old brand

    });

    $('.ewm_del_sec_button').click( function(){

        ewm_hf_delete = $( this ).data( 'section-id' );

        $( '.ewm_hf_main_body' ).html( '<center> Are you sure you want to delete the brand "' + $( '.ewm_hf_brand_dropdown_'+ ewm_hf_delete ).val() + '" </center>')

        $( '.ewm_lower_controls_body').show();

        $('.ewm_out_blur').show();

    });

    $('.ewm_submit_continue').click(function(){

        ewm_hf_delete_brand_and_cat( ewm_hf_delete );

    });

    $('.ewm_close_right').click(function(){
        $('.ewm_out_blur').hide();
    });

    $('.ewm_submit_cancel').click(function(){
        $('.ewm_out_blur').hide();
    });

    $('.main_brand_and_cats').hover(function(){
        $( this ).css({ 'border' : '1px solid #ffba00' });

        // get id in
        ewm_hf_section_id = $( this ).data('selection-id');

        $( '.ewm_hf_delete_button_'+ewm_hf_section_id ).css({ 'display':'block' });

    }, function(){

        $( this ).css({ 'border' : '1px solid #c4c3c321' });

        ewm_hf_section_id = $( this ).data( 'selection-id' );

        $( '.ewm_hf_delete_button_'+ewm_hf_section_id ).css({ 'display':'none' });

    });

    $(".ewm_cat_list").click( function(){
        category_key_id = $( this ).data( 'category_key_id' );
        brand_name_det = $( ".ewm_hf_brand_dropdown_"+$( this ).data(  'section-id' ) ).val();
        // Check if it's an addition or a subtraction
        // If it's an addition? add the brand : remove the brand
        if( $( this ).is(":checked") ){
            // Add the brand in category
            brand_name_det = $( ".ewm_hf_brand_dropdown_"+$( this ).data(  'section-id' ) ).val();
            server_data_list[ category_key_id ].brands.push( brand_name_det );
            ewm_hf_update_the_new_category_selection( server_data_list );
        }
        else{
            brand_name_det = $( ".ewm_hf_brand_dropdown_" + $( this ).data(  'section-id' ) ).val();
            myArray = server_data_list[ category_key_id ].brands ;
            myArray.splice(_.findIndex( myArray, function(item) {
                return item == brand_name_det ;
            }), 1);
            ewm_hf_update_the_new_category_selection( server_data_list );
        }
    });

}

ewm_hf_filter_listeners();


var ewm_update_single_option = function( args = {} ){

    var form_data = new FormData();

    form_data.append( 'action','ewm_update_single_option');
    form_data.append( 'ewm_hf_option_name', args.option_name );
    form_data.append( 'ewm_hf_option_value', args.option_value );

    jQuery.ajax({
        url: ajax_object.ajaxurl,
        type: 'post',
        contentType: false,
        processData: false,
        data: form_data,
        success: function ( response ) {
            console.log( response );
        },
        error: function (response) {
            //console.log( response );
        }
    });
    
}

var ewm_my_domain_setting_field = function(){
    
}

$('#my_domain_setting_field').change(function(){
    console.log('change initialized');
    ewm_update_single_option({
        'option_name' : 'my_domain_setting_field',
        'option_value' : $('#my_domain_setting_field').val()
    });
})

$('#my_setting_customer_key_field').change(function(){
    console.log('my_setting_customer_key_field');
    ewm_update_single_option({
        'option_name' : 'my_setting_customer_key_field',
        'option_value' : $('#my_setting_customer_key_field').val()
    });
})

$('#my_setting_customer_secret_field').change(function(){
    console.log('my_setting_customer_secret_field');
    ewm_update_single_option({
        'option_name' : 'my_setting_customer_secret_field',
        'option_value' : $('#my_setting_customer_secret_field').val()
    });
})


$('[name=my_update_frequency_setting_field]').change(function(){
    console.log('my_update_frequency_setting_field');
    ewm_update_single_option({
        'option_name' : 'my_update_frequency_setting_field',
        'option_value' : $(this).val()
    });
})

function add_section_id_to_new_section(){

    // main div area pre_top_layer
    $('.pre_top_layer').first().attr( 'data-selection-id' , ewm_hf_next_section_id ).addClass( 'main_brand_and_cats_' + ewm_hf_next_section_id ).removeClass('pre_top_layer');

    // delete button pre_delete_layer
    $('.pre_single_brand_del').first().addClass( 'ewm_hf_delete_button_' + ewm_hf_next_section_id ).removeClass('pre_single_brand_del');
    
    $('.pre_single_brand_del_b').attr( 'data-section-id' , ewm_hf_next_section_id ).removeClass('pre_single_brand_del_b');

    // dropdown section pre_single_brand_det
    $('.pre_single_brand_det').first().addClass( 'ewm_hf_brand_dropdown_' + ewm_hf_next_section_id ).attr( 'data-section-id' , ewm_hf_next_section_id ).removeClass('pre_single_brand_det');
    
    // categories pre_single_cat_input
    $('.pre_single_cat_input').first().attr( 'data-section-id' , ewm_hf_next_section_id ).removeClass('pre_single_cat_input');

    // 
    $('.checkbox_cat_parent:first > .parent_cat_selects > .pre_id_cat_rel').attr( 'data-section-id' , ewm_hf_next_section_id ).removeClass('pre_id_cat_rel') ;

    ewm_hf_next_section_id++;

}

$('#ewm_add_filter_section').click( function(){

    $('.top_parent_in_filter').append( $('.ewm_hf_hidden_body_sections').html() );

    $('.checkbox_cat_parent').first().hide();

    $('.checkbox_cat_parent').first().hide();

    // add section id and relevant classes
    add_section_id_to_new_section();
    
    // Add numbers and classes
    //-Main data-section-id
    //-Delete class id

    ewm_hf_filter_listeners();

});

function ewm_hf_remove_brand_from_cat( args ){

    // Get section id
    // Loop through server_data_list
    // edit in server using json

}

// console.log( ewm_hf_active_brands );

// console.log( ewm_hf_next_section_id );

// console.log( server_data_list[28].brands );

});
