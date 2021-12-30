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
    $page_get=$_GET['page_id'];
    if(empty($page_get)){
        $page_get=1;
    }
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

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Followup_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items($type)
    {

        $data = array();
        $out_stock_data=get_option($type);
        foreach ($out_stock_data as $of){
            $os=explode("|", $of);
            $data[] = array(
                'id'          => $os[0],
                'product_name'       =>  "<a href='".get_site_url()."/wp-admin/post.php?post=".$os[0]."&action=edit'>".get_the_title($os[0])."</a>",
                'date' =>$os[1],
            );

        }




        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 25;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'          => 'ID',
            'product_name'       => 'Product name',
            'date' => 'Date',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'product_name':
            case 'date':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'product_name';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
?>