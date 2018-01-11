<?php
class Pre_owned extends MX_Controller {

  function __construct() {
    parent::__construct();
    $this->load->module('store_categories');
    $this->load->module('store_items');
  }

  function pre_owned() {
    // figure out what the category ID is
    $cat_url = $this->uri->segment(3);
    // $this->load->module('store_categories');
    $cat_id = $this->store_categories->_get_cat_id_from_cat_url($cat_url);
    $this->store_categories->view($cat_id);
  }

  function big_boat() {
    // echo "here";die();
    $item_url = $this->uri->segment(2);
    // $this->load->module('store_items');
    $cat_id = $this->store_items->_get_cat_id_from_cat_url($cat_url);
    $this->store_items->view($cat_id);
  }

  function big_boats() {
    $item_url = $this->uri->segment(3);
    // $this->load->module('store_items');
    $item_id = $this->store_items->_get_item_id_from_item_url($item_url);
    $this->store_items->view($item_id);
  }

  function small_boats() {
    $item_url = $this->uri->segment(3);
    // $this->load->module('store_items');
    $item_id = $this->store_items->_get_item_id_from_item_url($item_url);
    $this->store_items->view($item_id);
  }

}
