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
}
add_action( 'admin_menu', 'follow_up_products_plugin_menu');

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
}
add_action( 'init', 'register_stock_options' );

/*
 * Get Out-in-stock products Table
 */
function out_in_stock_products(){
    ?>
    <h2>Out of stock products</h2>
    <table>
        <thead>
        <tr>
            <th>Product name</th>
            <th>Product ID</th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
            <?php
            $out_stock_data=get_option('fup_out_of_stock_products');
            foreach ($out_stock_data as $outs){
                $os=explode("|", $outs );
                echo "<tr><td><a href='".get_site_url()."/wp-admin/post.php?post=".$os[0]."&action=edit'>".get_the_title($os[0])."</a></td><td>".$os[0]."</td><td>".$os[1]."</td></tr>";
                
            }
            ?>
            <td></td>
        </tbody>
    </table>
    <h2>IN stock products</h2>
    <table>
        <thead>
        <tr>
            <th>Product name</th>
            <th>Product ID</th>
            <th>Date</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $in_stock_data=get_option('fup_in_stock_products');
        foreach ($in_stock_data as $ins){
            $in=explode("|", $ins );
            echo "<tr><td><a href='".get_site_url()."/wp-admin/post.php?post=".$in[0]."&action=edit'>".get_the_title($in[0])."</a></td><td>".$in[0]."</td><td>".$in[1]."</td></tr>";
        }
        ?>
        <td></td>
        </tbody>
    </table>
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
