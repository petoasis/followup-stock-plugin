<?php
/*
Plugin Name: Follow-UP for Woocomerce Products
Plugin URI: https://petoasisksa.com
Description: follow-up products status to show the status of the products on control panel when goes Stock > out-in stock.
Author: Saleem Summour
Version: 1.0.0
Author URI: https://lvendr.com/
*/

/*
 * Show follow up menu in the dashboard
 */
function follow_up_products_plugin_menu() {
    add_menu_page( 'Follow Up Product Options', 'Follow Up', 'manage_options', 'follow-up-id', 'follow_up_options','',5 );
    add_submenu_page( 'follow-up-id', 'Follow Up Setting', 'Follow Up Setting', 'manage_options', 'follow_up_settings','follow_setting_display');

}
add_action( 'admin_menu', 'follow_up_products_plugin_menu');



/*
 * Follow Up settings
 */
function follow_setting_display(){
    $outstock_per_page=get_option('outstock_per_page');
    $instock_per_page=get_option('instock_per_page');

    if(isset($_POST['submit'])){
        if(isset($_POST['outstock_per_page'])){
            update_option('outstock_per_page',$_POST['outstock_per_page'],'');
        }
        if(isset($_POST['instock_per_page'])){
            update_option('instock_per_page',$_POST['instock_per_page'],'');
        }

    }
    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2>Follow Up plugin settings</h2>
        <form method="post"  novalidate="novalidate">
            <table class="form-table" role="presentation">

                <tbody>
                <tr>
                    <th scope="row"><label for="order_status1">Out of stock products per page</label></th>
                    <td>
                        <input name="outstock_per_page" value="<?php echo $outstock_per_page;?>" type="number" id="outstock_per_page" class="regular-text">
                    </td>

                </tr>
                <tr>
                    <th scope="row"><label for="order_status2">IN stock products per page</label></th>
                    <td>
                        <input name="instock_per_page" value="<?php echo $instock_per_page;?>" type="number" id="instock_per_page" class="regular-text">
                    </td>

                </tr>
                </tbody>
            </table>


            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>

    <?php
}

/*
 * Show follow up menu function
 */
function follow_up_options(){
    out_in_stock_products();
}

/*
 * Register Options for the first time
 */
function register_stock_options() {
    if ( !get_option('fup_out_of_stock_products') ){
        $product_array=array('');
        add_option('fup_out_of_stock_products',$product_array,'','yes');
    }
    if ( !get_option('fup_in_stock_products') ){
        $product_array=array('');
        add_option('fup_in_stock_products',$product_array,'','yes');
    }
    if ( !get_option('instock_per_page') ){
       add_option('instock_per_page',25,'','yes');
    }
    if ( !get_option('outstock_per_page') ){
        add_option('outstock_per_page',25,'','yes');
    }

}
add_action( 'init', 'register_stock_options' );

/*
 * Get Out-in-stock products Table
 */
function out_in_stock_products(){
    include_once( plugin_dir_path( __FILE__ ) . 'followup_list_table.php' );
    $outs = new Followup_List_Table();
    $type="fup_out_of_stock_products";
    $outs->prepare_items($type);

    $ins = new Followup_List_Table();
    $type1="fup_in_stock_products";
    $ins->prepare_items($type1);

    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2>Out of stock products</h2>
        <?php


        $outs->display();

        ?>
    </div>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2>IN stock products</h2>
        <?php


        $ins->display();

        ?>
    </div>

    <?php
}

/*
 * Check product stock status after updating products
 */
function check_products_stock_after_update($product_id, $product){

    $in_stock_option=get_option('fup_in_stock_products');
    $out_stock_option=get_option('fup_out_of_stock_products');
    $in_stock_meta=get_post_meta($product_id,'in_stock_meta',true);
    $out_of_stock_meta=get_post_meta($product_id,'out_of_stock_meta',true);
    $today = date("Y-m-d H:i:s");


    /*
     * If stock meta is not defiend before
     */
    if(empty($in_stock_meta) and empty($out_of_stock_meta)){
        if(!$product->is_in_stock()){
            update_post_meta($product_id,'out_of_stock_meta',1);
            array_push($out_stock_option,$product_id.'|'.$today);
            update_option('fup_out_of_stock_products',$out_stock_option,'');

        }
        else{
            update_post_meta($product_id,'in_stock_meta',1);
            array_push($in_stock_option,$product_id.'|'.$today);
            update_option('fup_in_stock_products',$in_stock_option,'');
        }
    }

    /*
     * If stock status is out of stock and the old status is in stock
     */
    if(!$product->is_in_stock() and !empty($in_stock_meta)){
        update_post_meta($product_id,'in_stock_meta',null);
        update_post_meta($product_id,'out_of_stock_meta',1);
        array_push($out_stock_option,$product_id.'|'.$today);
        update_option('fup_out_of_stock_products',$out_stock_option,'');

    }

    /*
    * If stock status is in stock and the old status is out of stock
     */
    if($product->is_in_stock() and !empty($out_of_stock_meta)){
        update_post_meta($product_id,'out_of_stock_meta',null);
        update_post_meta($product_id,'in_stock_meta','1');
        array_push($in_stock_option,$product_id.'|'.$today);
        update_option('fup_in_stock_products',$in_stock_option,'');

    }

}

add_action('woocommerce_update_product', 'check_products_stock_after_update', 10, 2);

/*
 * Check product stock status after order
 */

function check_products_stock_after_order($order_id){
    $order = wc_get_order( $order_id );
    foreach ( $order->get_items() as $item_id => $item_values ) {

        $product_id = $item_values->get_product_id();
        $product = wc_get_product( $product_id );
        $in_stock_option=get_option('fup_in_stock_products');
        $out_stock_option=get_option('fup_out_of_stock_products');
        $in_stock_meta=get_post_meta($product_id,'in_stock_meta',true);
        $out_of_stock_meta=get_post_meta($product_id,'out_of_stock_meta',true);
        $today = date("Y-m-d H:i:s");

        if(empty($in_stock_meta) and empty($out_of_stock_meta)){
            if(!$product->is_in_stock()){
                update_post_meta($product_id,'out_of_stock_meta',1);
                array_push($out_stock_option,$product_id.'|'.$today);
                update_option('fup_out_of_stock_products',$out_stock_option,'');

            }
            else{
                update_post_meta($product_id,'in_stock_meta',1);
                array_push($in_stock_option,$product_id.'|'.$today);
                update_option('fup_in_stock_products',$in_stock_option,'');
            }
        }

        if(!$product->is_in_stock() and !empty($in_stock_meta)){
            update_post_meta($product_id,'in_stock_meta',null);
            update_post_meta($product_id,'out_of_stock_meta',1);
            array_push($out_stock_option,$product_id.'|'.$today);
            update_option('fup_out_of_stock_products',$out_stock_option,'');

        }
        if($product->is_in_stock() and !empty($out_of_stock_meta)){
            update_post_meta($product_id,'out_of_stock_meta',null);
            update_post_meta($product_id,'in_stock_meta','1');
            array_push($in_stock_option,$product_id.'|'.$today);
            update_option('fup_in_stock_products',$in_stock_option,'');

        }


    }

}

add_action('woocommerce_thankyou', 'check_products_stock_after_order', 10, 2);


?>