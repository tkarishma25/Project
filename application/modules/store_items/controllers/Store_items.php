<?php
class Store_items extends MX_Controller {

  function __construct() {
    parent::__construct();
    // These two lines are needed to display custom validation messages
    $this->load->module('custom_pagination');
    $this->load->library('upload');
    $this->load->library('image_lib');
  }

  // shows items based on the category_title
  function view_items_for_category($cat_url) {
    $this->load->module('store_categories');
    $this->load->module('site_settings');
    $cat_id = $this->store_categories->get_where_custom('cat_url', $cat_url)->row()->id;

    $use_limit = false;
    $mysql_query = $this->_generate_mysql_query_for_category($use_limit, $cat_id);
    $query = $this->_custom_query($mysql_query);
    $total_items = $query->num_rows();

    $use_limit = true;
    $mysql_query = $this->_generate_mysql_query_for_category($use_limit, $cat_id);
    $query = $this->_custom_query($mysql_query);
    $pagination_data['template'] = "unishop";
    $pagination_data['target_base_url'] = $this->get_target_pagination_base_url();
    $pagination_data['total_rows'] = $total_items;
    $pagination_data['offset_segment'] = 4;
    $pagination_data['limit'] = $this->get_pagination_limit("main");
    $data['pagination'] = $this->custom_pagination->_generate_pagination($pagination_data);
    $showing_statement_data['limit'] = $this->get_pagination_limit("main");
    $showing_statement_data['offset'] = $this->_get_pagination_offset();
    $showing_statement_data['total_rows'] = $total_items;
    $data['query'] = $query;
    $data['currency_symbol'] = $this->site_settings->_get_currency_symbol();
    $data['showing_statement'] = $this->custom_pagination->get_showing_statement($showing_statement_data);
    $data['view_file'] = "view_items";
    $this->load->module('templates');
    $this->templates->public_bootstrap($data);
  }

  function _generate_mysql_query_for_category($use_limit, $cat_id) {
    //NOTE: use_limit can be true or false
    $mysql_query = "SELECT DISTINCT si.id, si.item_url, si.item_price, si.item_title, si.was_price
                    FROM store_items si
                    JOIN store_cat_assign sca ON si.id = item_id
                    JOIN store_categories sc ON sca.cat_id = sc.id
                    WHERE sc.id = $cat_id";
    if ($use_limit == true) {
      $limit = $this->get_pagination_limit("main");
      $offset = $this->_get_pagination_offset();
      $mysql_query.= " LIMIT ".$offset.", ".$limit;
    }
    return $mysql_query;
  }

  function search_items_by_keywords() {
    $this->load->module('site_settings');
    $submit = $this->input->post('submit', true);
    $searchKeywords = $this->input->post('searchKeywords', true);
    $searchKeywords = trim($searchKeywords);
    $searchKeywords = explode(" ", $searchKeywords);

    if ($submit == "submit") {
      $use_limit = false;
      $mysql_query = $this->_get_mysql_query_for_search_items_by_keywords($searchKeywords, $use_limit);
      $query = $this->_custom_query($mysql_query);
      $keywords = "";
      foreach($searchKeywords as $value) {
        $keywords.= $value." ";
      }
      $data['query'] = $query;
      $data['keywords'] = "<b>Keyword(s)</b>: ".$keywords;
      $data['currency_symbol'] = $this->site_settings->_get_currency_symbol();
      $data['view_file'] = "view_items";
      $this->load->module('templates');
      $this->templates->public_bootstrap($data);
    }
  }

  function _get_mysql_query_for_search_items_by_keywords($searchKeywords, $use_limit) {
    $mysql_query = "
    SELECT si.id, si.item_url, si.item_price, si.item_title, si.was_price
    FROM store_items si
    LEFT JOIN item_pics sp ON si.id = sp.item_id
    WHERE item_title LIKE '$searchKeywords[0]'
    OR item_description LIKE '$searchKeywords[0] AND status = 1'"
    ;
    if (sizeOf($searchKeywords) > 1) {
      for ($i = 1; $i < sizeOf($searchKeywords); $i++) {
        $mysql_query.= " OR item_title LIKE '$searchKeywords[$i]' OR item_description LIKE '$searchKeywords[$i]'";
      }
    }
    if ($use_limit == true) {
      $limit = $this->get_pagination_limit("main");
      $offset = $this->_get_pagination_offset();
      $mysql_query.= " LIMIT ".$offset.", ".$limit;
    }
    return $mysql_query;
  }

  // a function that shows all items in database.
  function view_all_items() {
    $this->load->module('site_security');
    $this->load->module('site_settings');

    $use_limit = false;
    $mysql_query = $this->_generate_mysql_query_for_view_all_items($use_limit);
    $query = $this->_custom_query($mysql_query);
    $total_items = $query->num_rows();

    // fetch the items for this page
    $use_limit = true;
    $mysql_query = $this->_generate_mysql_query_for_view_all_items($use_limit);
    $query = $this->_custom_query($mysql_query);

    $pagination_data['template'] = "unishop";
    $pagination_data['target_base_url'] = $this->get_target_pagination_base_url();
    $pagination_data['total_rows'] = $total_items;
    $pagination_data['offset_segment'] = 4;
    $pagination_data['limit'] = $this->get_pagination_limit("main");

    $data['pagination'] = $this->custom_pagination->_generate_pagination($pagination_data);
    $showing_statement_data['limit'] = $this->get_pagination_limit("main");
    $showing_statement_data['offset'] = $this->_get_pagination_offset();
    $showing_statement_data['total_rows'] = $total_items;
    $data['showing_statement'] = $this->custom_pagination->get_showing_statement($showing_statement_data);
    $data['query'] = $query;
    $data['currency_symbol'] = $this->site_settings->_get_currency_symbol();
    $data['view_file'] = "view_items";
    $this->load->module('templates');
    $this->templates->public_bootstrap($data);
  }

  function _generate_mysql_query_for_view_all_items($use_limit) {
    //NOTE: use_limit can be true or false
    $mysql_query = "SELECT DISTINCT si.id, si.item_url, si.item_price, si.item_title, si.was_price FROM store_items si JOIN item_pics sp ON si.id = sp.item_id";
    if ($use_limit == true) {
      $limit = $this->get_pagination_limit("main");
      $offset = $this->_get_pagination_offset();
      $mysql_query.= " LIMIT ".$offset.", ".$limit;
    }
    return $mysql_query;
  }

  function view_item($item_url) {
    $this->load->module('site_security');
    $this->load->module('site_settings');
    $this->load->module('timedate');
    // only those people with an update_id for an item can get in.
    $item_id = $this->_get_item_id_from_item_url($item_url);
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }

    // fetch the item details
    $data = $this->fetch_data_from_db($item_id);
    $data['date_made'] = $this->timedate->get_date($data['date_made'], 'datepicker_us');
    $data['update_id'] = $item_id;
    $data['pics_query'] = $this->_get_pics_by_update_id($item_id);

    // build the breadcrumbs data array
    $breadcrumbs_data['template'] = "public_bootstrap";
    $breadcrumbs_data['current_page_title'] = $data['item_title'];
    $breadcrumbs_data['breadcrumbs_array'] = $this->_generate_breadcrumbs_array($item_id);
    $data['breadcrumbs_data'] = $breadcrumbs_data;

    $data['flash'] = $this->session->flashdata('item');
    $this->load->module('site_settings');
    $currency_symbol = $this->site_settings->_get_currency_symbol();
    $data['item_price_desc'] = $currency_symbol.number_format($data['item_price'], 2);
    // this module helps to make a friendly URL
    $data['view_module'] = "store_items";
    $data['view_file'] = "view";
    $this->load->module('templates');
    $this->templates->public_bootstrap($data);
  }

  // method for pagination
  function get_target_pagination_base_url() {
    $first_bit = $this->uri->segment(1);
    $second_bit = $this->uri->segment(2);
    $third_bit = $this->uri->segment(3);
    $target_base_url = base_url().$first_bit."/".$second_bit."/".$third_bit;
    return $target_base_url;
  }
  // $location is where you show the data: it's either "main" or "admin"
  function get_pagination_limit($location) {
    if ($location == "main")
    $limit = 6;
    else if ($location == "admin")
    $limit = 10;
    return $limit;
  }

  function _get_pagination_offset() {
    $offset = $this->uri->segment(4);
    if (!is_numeric($offset)) {
      $offset = 0;
    }
    return $offset;
  }
  // method for pagination

  function sort() {
    $this->load->module('site_security');
    $this->load->module('item_pics');
    $this->site_security->_make_sure_is_admin();

    $number = $this->input->post('number', true);
    for ($i = 1; $i <= $number; $i++) {
      $small_pic_id = $_POST['order'.$i];
      $data['priority'] = $i;
      $this->item_pics->_update($small_pic_id, $data);
    }
  }

  function _get_item_id_from_item_url($item_url) {
    $query = $this->get_where_custom('item_url', $item_url);
    foreach($query->result() as $row) {
      $item_id = $row->id;
    }
    if (!isset($item_id)) {
      $item_id = 0;
    }
    return $item_id;
  }

  function _get_cat_url_by_item_url($item_url) {
    $mysql_query = "
    SELECT sc.cat_url FROM
    store_items si JOIN store_cat_assign sca ON si.id = sca.item_id
    JOIN store_categories sc ON sca.cat_id = sc.id
    WHERE si.item_url = '$item_url'
    ";
    $query = $this->_custom_query($mysql_query);
    foreach($query->result() as $row) {
      $cat_url = $row->cat_url;
    }
    return $cat_url;
  }

  function _get_small_pic_by_item_url($item_url) {
    $mysql_query = "
    SELECT sp.picture_name AS picture_name FROM
    store_items si JOIN item_pics sp ON si.id = sp.item_id
    WHERE si.item_url = '$item_url'
    ORDER BY sp.priority DESC
    LIMIT 1
    ";
    $query = $this->_custom_query($mysql_query);
    foreach($query->result() as $row) {
      $picture_name = $row->picture_name;
    }
    if (!isset($picture_name)) {
      $picture_name = "";
    }
    return $picture_name;
  }

  function get_item_title_by_id($item_id) {
    $query = $this->get_where_custom('id', $item_id);
    foreach($query->result() as $row) {
      $item_title = $row->item_title;
    }
    if (!isset($item_title)) {
      $item_title = "";
    }
    return $item_title;
  }

  function _get_all_items_for_dropdown() {
    // note: this gets used on store_cat_assign
    $mysql_query = "SELECT * FROM store_items ORDER BY item_title";
    $query = $this->_custom_query($mysql_query);
    foreach($query->result() as $row) {
      $items[$row->id] = $row->item_title;
    }
    if (!isset($items)) {
      $items = "";
    }
    return $items;
  }

  function _get_pics_by_update_id($item_id) {
    $mysql_query = "
    SELECT @counter := @counter + 1 as row_number, picture_name
    FROM item_pics WHERE item_id = ?
    ";

    $query = $this->db->query($mysql_query, array($item_id));
    return $query;
  }

  function _generate_breadcrumbs_array($item_id) {
    $homepage_url = base_url();
    $breadcrumbs_array[$homepage_url] = 'Home';

    // figure out what the sub_cat_id is for this item
    $sub_cat_id = $this->_get_sub_cat_id($item_id);
    // now that we have the sub_cat_id, get the title and the URL
    $this->load->module('store_categories');
    $sub_cat_title = $this->store_categories->_get_cat_title($sub_cat_id);
    // get the sub cat URL
    //TODO update _get_full_cat_url();
    $sub_cat_url = $this->store_categories->_get_full_cat_url($sub_cat_id);

    $breadcrumbs_array[$sub_cat_url] = $sub_cat_title;
    return $breadcrumbs_array;
  }

  function _get_sub_cat_id($item_id) {

    if (!isset($_SERVER['HTTP_REFERER'])) {
      $refer_url = '';
    } else {
      $refer_url = $_SERVER['HTTP_REFERER'];
    }
    //http://localhost/practice/pre_owned_items/Big_boat_rental
    $this->load->module('site_settings');
    $this->load->module('store_cat_assign');
    $this->load->module('store_categories');

    $items_segments = $this->site_settings->_get_items_segments();
    $ditch_this = base_url().$items_segments;
    $cat_url = str_replace($ditch_this, '', $refer_url);
    $sub_cat_id = $this->store_categories->_get_cat_id_from_cat_url($cat_url);
    if ($sub_cat_id > 0) {
      return $sub_cat_id;
    } else {
      $sub_cat_id = $this->_get_best_sub_cat_id($item_id);
    }
    return $sub_cat_id;
  }

  function _get_best_sub_cat_id($item_id) {
    $this->load->module('store_cat_assign');
    // figure out which associated sub cat has the most items
    $query = $this->store_cat_assign->get_where_custom('item_id', $item_id);
    foreach($query->result() as $row) {
      $potential_sub_cats[] = $row->cat_id;
    }
    // how many sub cats does this item appear in?
    $num_sub_cats_for_item = count($potential_sub_cats);

    if ($num_sub_cats_for_item == 1) {
      // the item only appears in one sub category, so use this
      $sub_cat_id = $potential_sub_cats['0'];
      return $sub_cat_id;
    } else {
      // we have more than one sub cat START

      foreach ($potential_sub_cats as $key => $value) {
        $sub_cat_id = $value;
        $num_items_in_sub_cat = $this->store_cat_assign->count_where('cat_id', $sub_cat_id);
        $num_items_count[$sub_cat_id] = $num_items_in_sub_cat;
      }
      // which array key is paired with the highest value?
      $sub_cat_id = $this->get_best_array_key($num_items_count);
      return $sub_cat_id;
      // we have more than one sub cat END
    }
  }

  function _get_sub_cat_url_by_item_id($item_id) {
    $mysql_query = "SELECT sc.cat_url FROM store_categories sc
    JOIN store_cat_assign sca ON sc.id = sca.cat_id
    JOIN store_items si ON sca.item_id = si.id
    WHERE si.id = ?";
    $query = $this->db->query($mysql_query, array($item_id));
    $sub_cat_url = "";
    foreach($query->result() as $row) {
      $sub_cat_url = $row->cat_url;
    }
    return $sub_cat_url;
  }

  function get_best_array_key($target_array) {
    foreach ($target_array as $key => $value) {
      if (!isset($key_with_highest_value)) {
        $key_with_highest_value = $key;
      } else if ($value > $target_array[$key_with_highest_value]) {
        $key_with_highest_value = $key;
      }
    }
    return $key_with_highest_value;
  }

  // '_' means private
  function _genrate_thumbnail($file_name) {
    $config['image_library'] = 'gd2';
    $config['source_image'] = './media/item_big_pics/'.$file_name;
    $config['new_image'] = './media/item_small_pics/'.$file_name;
    $config['maintain_ratio'] = true;
    $config['width'] = 200;
    $config['height'] = 200;
    $this->image_lib->initialize($config);
    $this->image_lib->resize();
  }

  // This function displays the upload_image page
  function upload_image($item_id) {

    // only those people with an update_id for an item can get in.
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }
    // security
    $this->load->library('session');
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $mysql_query = "SELECT * FROM item_pics WHERE item_id = $item_id ORDER BY priority ASC";
    $query = $this->_custom_query($mysql_query);
    $data['query'] = $query;
    $data['num_rows'] = $query->num_rows(); // number of pictures that an item has
    $data['headline'] = "Manage Images";
    $data['update_id'] = $item_id;
    $date['flash'] = $this->session->flashdata('item');
    $data['view_file'] = "upload_image";
    $data['sort_this'] = true;
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function do_upload($item_id) {
    // only those people with an update_id for an item can get in.
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }
    // security
    $this->load->library('session');
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();
    // getting submit from the post
    $submit = $this->input->post('submit', true);
    if ($submit == "cancel") {
      redirect('store_items/create/'.$item_id);
    } else if ($submit == "upload") {
      $config['upload_path'] = './media/item_big_pics';
      $config['allowed_types'] = 'gif|jpg|png|jpeg';
      $config['max_size'] = 2048;
      $config['max_width'] = 3036;
      $config['max_height'] = 1902;
      $file_name = $this->site_security->generate_random_string(16);
      $config['file_name'] = $file_name;
      $this->load->library('upload', $config);
      $this->upload->initialize($config);

      if (!$this->upload->do_upload('userfile')) {
        $mysql_query = "SELECT * FROM item_pics WHERE item_id = $item_id";
        $query = $this->_custom_query($mysql_query);
        $data['query'] = $query;
        $data['num_rows'] = $query->num_rows();
        $data['error'] = array('error' => $this->upload->display_errors("<p style='color: red;'>", "</p>"));
        $data['headline'] = "Upload Error";
        $data['_id'] = $item_id;
        $date['flash'] = $this->session->flashdata('item');
        $data['view_file'] = "upload_image";
        $this->load->module('templates');
        $this->templates->admin($data);
      } else {
        // upload was successful
        $data = array('upload_data' => $this->upload->data());
        $upload_data = $data['upload_data'];

        $file_ext = $upload_data['file_ext'];
        $file_name = $file_name.$file_ext;
        // $file_name = $upload_data['file_name'];
        $this->_genrate_thumbnail($file_name);

        //update the database
        $priority = $this->_get_priority($item_id);
        $mysql_query = "INSERT INTO item_pics (item_id, picture_name, priority) VALUES ($item_id, '$file_name', $priority)";
        $this->_custom_query($mysql_query);

        $data['headline'] = "Upload Success";
        $data['update_id'] = $item_id;
        $flash_msg = "The picture was successfully uploaded.";
        $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
        $this->session->set_flashdata('item', $value);

        redirect(base_url()."/store_items/upload_image/".$item_id);
      }
    }
  }

  function _get_priority($item_id) {
    $mysql_query = "SELECT * FROM item_pics WHERE item_id = $item_id ORDER BY priority DESC LIMIT 1";
    $query = $this->_custom_query($mysql_query);
    if ($query->num_rows() == 1) {
      foreach ($query->result() as $row) {
        $priority = $row->priority + 1;
      }
    } else {
      $priority = 1;
    }
    return $priority;
  }

  function _get_small_pic_id($item_id, $priority) {
    $mysql_query = "SELECT id FROM item_pics WHERE item_id = $item_id AND priority = $priority";
    $query = $this->_custom_query($mysql_query);
    foreach($query->result() as $row) {
      $small_pic_id = $row->id;
    }
    return $small_pic_id;
  }

  function deleteconf($item_id) {

    // only those people with an update_id for an item can get in.
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }

    // security
    $this->load->library('session');
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $data['headline'] = "Delete Image";
    $data['update_id'] = $item_id;
    $date['flash'] = $this->session->flashdata('item');
    $data['view_file'] = "deleteconf";
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function _process_delete($item_id) {
    // attempt to delete item colors
    $this->load->module("store_item_colors");
    $this->store_item_colors->_delete_for_item($item_id);
    // attempt to delete item sizes
    $this->load->module("store_item_sizes");
    $this->store_item_sizes->_delete_for_item($item_id);
    // attemp to delete cat assign for the item_id
    $this->load->module('store_cat_assign');
    $this->store_cat_assign->_custom_delete('user_id', $item_id);
    // attempt to delete item big & small pics
    $data = $this->fetch_data_from_db($item_id);

    $this->load->module('item_pics');
    $this->load->module('big_pics');
    $small_pic_ids = $this->item_pics->get_small_pic_ids_by_item_id($item_id);

    foreach ($small_pic_ids as $key => $value) {
      $picture_name = $this->item_pics->_get_picture_name_by_small_pic_id($value);
      $big_pic_path = './media/item_big_pics/'.$picture_name;
      $small_pic_path = '.media/item_small_pics/'.$picture_name;
      // attemp to delete item small pics
      if (file_exists($big_pic_path)) {
        unlink($big_pic_path);
      }
      if (file_exists($small_pic_path)) {
        unlink($small_pic_path);
      }
    }

    // delete the item record from store_items
    $this->_delete($item_id);
  }

  function delete($item_id) {
    // only those people with an update_id for an item can get in.
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }
    // security
    $this->load->library('session');
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $submit = $this->input->post('submit', true);

    if ($submit == "Cancel") {
      redirect('store_items/create/'.$item_id);
    } else if ($submit == "Delete") {
      // manipulate the DB
      $this->_process_delete($item_id);
      // preparing the flash message after deletion
      $flash_msg = "The item was successfully deleted.";
      $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
      $this->session->set_flashdata('item', $value);

      redirect('store_items/manage');
    }
  }

  function delete_image($item_id) {
    $this->load->module('site_security');
    $this->load->module('item_pics');
    $this->load->module('big_pics');


    // only those people with an update_id for an item can get in.
    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }

    // security
    $this->load->library('session');
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $pic_id = $this->uri->segment(4);
    $data = $this->fetch_data_from_db($item_id);
    $picture_name = $this->item_pics->get_where($pic_id)->row()->picture_name;

    $big_pic_path = './media/item_big_pics/'.$picture_name;
    $small_pic_path = './media/item_small_pics/'.$picture_name;

    // checks if the file exists in the directory and if so, attemt to remove the images
    if (file_exists($big_pic_path)) {
      unlink($big_pic_path);
    }

    if (file_exists($small_pic_path)) {
      unlink($small_pic_path);
    }

    // reassign priority
    $priority_for_deleted_pic = $this->item_pics->get_priority_for_item($pic_id, $item_id);
    // delete small and big pics
    $this->item_pics->_delete($pic_id);
    $query = $this->item_pics->get_where_custom('item_id', $item_id);
    foreach ($query->result() as $row) {
      if ($row->priority > $priority_for_deleted_pic) {
        $new_priority = $row->priority - 1;
        $data['priority'] = $new_priority;
        $this->item_pics->_update($row->id, $data);
      }
    }

    $flash_msg = "The item image was successfuly deleted.";
    $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
    $this->session->set_flashdata('item', $value);

    redirect('store_items/upload_image/'.$item_id);
  }

  function manage() {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    // gettinf flash data
    $data['flash'] = $this->session->flashdata('item');
    // getting data from DB
    // this means order by item_title
    $use_limit = false;
    $mysql_query = $this->_generate_mysql_query_for_manage_store_items($use_limit);
    $query = $this->_custom_query($mysql_query);
    $total_items = $query->num_rows();

    $pagination_data['template'] = "public_bootstrap";
    $pagination_data['target_base_url'] = $this->get_target_pagination_base_url();
    $pagination_data['total_rows'] = $total_items;
    $pagination_data['offset_segment'] = 4;
    $pagination_data['limit'] = $this->get_pagination_limit("admin");

    $use_limit = true;
    $mysql_query = $this->_generate_mysql_query_for_manage_store_items($use_limit);
    $query = $this->_custom_query($mysql_query);
    $data['pagination'] = $this->custom_pagination->_generate_pagination($pagination_data);
    $data['query'] = $query;
    // create a view file. Putting a php (html) into the admin template.
    $data['view_module'] = "store_items";
    // store_Items.php
    $data['view_file'] = "manage"; // manage.php
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function _generate_mysql_query_for_manage_store_items($use_limit) {
    $mysql_query = "SELECT si.*, sa.user_name  FROM store_items si
    LEFT JOIN users sa ON si.user_id = sa.id
    ";
    if ($use_limit == true) {
      $limit = 10;
      $offset = $this->_get_pagination_offset();
      $mysql_query.= " LIMIT ".$offset.", ".$limit;
    }
    return $mysql_query;
  }

  function create() {
    $this->load->module('site_security');
    $this->load->module('site_settings');
    $this->site_security->_make_sure_is_admin();

    $item_id = $this->uri->segment(3);
    $submit = $this->input->post('submit', true);
    if ($submit == "cancel") {
      redirect('store_items/manage');
    } else if ($submit == "submit") {
      // process the form
      $this->load->module('custom_validation');
      $this->custom_validation->set_rules('item_title', 'Item Title', 'max_length[240]'); // callback is for checking if the item already exists
      $this->custom_validation->set_rules('item_price', 'Item Price', 'numeric');
      if ($this->input->post('was_price') != "") {
        $this->custom_validation->set_rules('was_price', 'Was Price', 'numeric');
      }
      // $this->custom_validation->set_rules('status', 'Status', 'required|numeric');
      if ($this->custom_validation->run() == true) {
        // get the variables and assign into $data variable
        $data = $this->fetch_data_from_post();
        // create a URL for an item but they need to be UNIQUE
        $data['item_url'] = url_title($data['item_title']);

        if (is_numeric($item_id)) {
          //update the item details
          unset($data['categories']);
          $this->_update($item_id, $data);
          // for categories, need to delete the entry for the item id and re-insert
          $this->load->module('store_cat_assign');
          $this->store_cat_assign->_custom_delete('item_id', $item_id);
          // get categories indexes
          $categories_from_post = $this->input->post('categories[]', true);
          // get categories array
          $categories = $this->_get_categories();
          for ($i = 0; $i < count($categories_from_post); $i++) {
            $cat_assign_data['cat_id'] = $categories_from_post[$i];
            $cat_assign_data['item_id'] = $item_id;
            $this->store_cat_assign->_insert($cat_assign_data);
          }
          // These two lines show the alert for the successful item details change.
          $flash_msg = "The item details were successfully updated.";
          $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
          $this->session->set_flashdata('item', $value);
          // add the update data into the URL
          redirect('store_items/create/'.$item_id);
        } else {
          // insert a new item into DB
          $code = $this->site_security->generate_random_string(6);
          $item_url = url_title($data['item_title']).$code;
          $data['item_url'] = $item_url;
          $data['user_id'] = 0; // 0 = admin
          $data['date_made'] = time();
          unset($data['categories']);
          $this->_insert($data);

          $item_id = $this->get_max(); //get the ID of the new item
          $item_id = $this->_get_item_id_from_item_url($item_url);
          // insert into store_cat_assign table
          $categories_from_post = $this->input->post('categories[]', true);
          $this->load->module('store_cat_assign');
          // $categories = $this->_get_categories();
          for ($i = 0; $i < count($categories_from_post); $i++) {
            $cat_assign_data['cat_id'] = $categories_from_post[$i];
            $cat_assign_data['item_id'] = $item_id;
            $this->store_cat_assign->_insert($cat_assign_data);
          }
          $this->load->library('session');
          $flash_msg = "The item was successfully added.";
          $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
          $this->session->set_flashdata('item', $value);
          // add the update data into the URL
          redirect('store_items/create/'.$item_id);
        }
      }
    }
    if ((is_numeric($item_id)) && ($submit != "submit")) {
      $data = $this->fetch_data_from_db($item_id);
    } else {
      $data = $this->fetch_data_from_post();
    }

    if (!is_numeric($item_id) || $item_id == "") {
      $data['headline'] = "Add New Item";
    } else {
      $data['headline'] = "Update Item Details";
    }

    // pass update id into the page
    $data['update_id'] = $item_id;
    if (isset($item_id)) {
      $data['item_url'] = $this->get_where($item_id)->row()->item_url;
    }
    $data['flash'] = $this->session->flashdata('item');
    if ($this->session->has_userdata('validation_errors')) {
      $data['validation_errors'] = $this->session->userdata('validation_errors');
      $this->session->unset_userdata('validation_errors');
    }
    // create a view file. Putting a php (html) into the admin template.
    $data['categories_options'] = $this->_get_categories();
    $data['states'] = $this->site_settings->_get_states_dropdown();
    $data['view_file'] = "create"; // manage.php
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function _get_categories() {
    $mysql_query = "SELECT * FROM store_categories WHERE parent_cat_id = 0 ORDER BY id";
    $query = $this->_custom_query($mysql_query);

    $count = 0;
    foreach ($query->result() as $row) {
      $categories[$count] = $row->id;
      $count++;
    }
    return $categories;
  }

  // get data from POST method
  function fetch_data_from_post() {
    $data['item_title'] = $this->input->post('item_title', true);
    $data['item_price'] = $this->input->post('item_price', true);
    $data['was_price'] = $this->input->post('was_price', true);
    $data['item_description'] = $this->input->post('item_description', true);
    $data['categories'] = $this->input->post('categories[]', true);
    $data['city'] = $this->input->post('city', true);
    $data['status'] = $this->input->post('status', true);
    $this->load->module('site_settings');
    $states = $this->site_settings->_get_states_dropdown();
    $state_index = $this->input->post('state', true);
    $data['state'] = $states[$state_index];
    return $data;
  }

  function fetch_categories_from_post() {
    $categories = $this->input->post('categories[]', true);
    for($i = 0; $i < count($categories); $i++){
      $data['categories'];
    }
    return $data;
  }

  // get data from database
  function fetch_data_from_db($item_id) {
    //security
    $this->load->module('site_security');
    $this->load->module('store_items');

    if (!is_numeric($item_id)) {
      redirect('site_security/not_allowed');
    }

    $mysql_query = "
    SELECT * FROM
    store_items si
    WHERE id = $item_id
    ";

    $query = $this->store_items->_custom_query($mysql_query);
    foreach ($query->result() as $row) {
      $data['user_id'] = $row->user_id;
      $data['item_title'] = $row->item_title;
      $data['item_url'] = $row->item_url;
      $data['item_price'] = $row->item_price;
      $data['was_price'] = $row->was_price;
      $data['item_description'] = $row->item_description;
      $data['status'] = $row->status;
      $data['city'] = $row->city;
      $data['state'] = $row->state;
      $data['date_made'] = $row->date_made;
    }
    if (!isset($data)) {
      $data = "";
    }

    return $data;
  }

  function get_user_by_item_id($item_id) {
    $mysql_query = "
    SELECT sa.id as user_id, sa.user_name as user_name, sa.email as email FROM users sa
    JOIN store_items si on sa.id = si.user_id
    WHERE si.id = ?
    ";
    $query = $this->db->query($mysql_query, array($item_id));

    return $query;
  }

  function _draw_contact_sellter($item_id) {
    $query = $this->get_user_by_item_id($item_id);
    foreach($query->result() as $row) {
      $data['email'] = $row->email;
      $data['user_id'] = $row->user_id;
      $data['user_name'] = $row->user_name;
    }
    $this->load->module('site_settings');
    if (!isset($data['email'])) {
      $data['email'] = $this->site_settings->_get_email_for_admin_seller();
    }
    if (!isset($data['user_name'])) {
      $data['user_name'] = "TCWS Admin";
    }
    if (!isset($data['user_id'])) {
      $data['user_id'] = 0;
    }

    $data['item_id'] = $item_id;
    $this->load->view('contact_seller', $data);
  }

  function _draw_new_items() {
    $this->load->module('site_settings');

    $mysql_query = "
    SELECT si.item_title, si.item_url, si.item_price, si.was_price, sc.cat_url
    FROM store_items si
    JOIN store_cat_assign sca ON sca.item_id = si.id
    JOIN store_categories sc ON sc.id = sca.cat_id
    LIMIT 6
    ";

    $query = $this->_custom_query($mysql_query);
    $data['item_segments'] = $this->site_settings->_get_item_segments();
    $data['currency_symbol'] = $this->site_settings->_get_currency_symbol();
    $data['query'] = $query;
    $this->load->view('new_items', $data);
  }

  function get($order_by)
  {
    $this->load->model('mdl_store_items');
    $query = $this->mdl_store_items->get($order_by);
    return $query;
  }

  function get_with_limit($limit, $offset, $order_by)
  {
    if ((!is_numeric($limit)) || (!is_numeric($offset))) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_store_items');
    $query = $this->mdl_store_items->get_with_limit($limit, $offset, $order_by);
    return $query;
  }

  function get_where($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_store_items');
    $query = $this->mdl_store_items->get_where($id);
    return $query;
  }

  function get_where_custom($col, $value)
  {
    $this->load->model('mdl_store_items');
    $query = $this->mdl_store_items->get_where_custom($col, $value);
    return $query;
  }

  function _insert($data)
  {
    $this->load->model('mdl_store_items');
    $this->mdl_store_items->_insert($data);
  }

  function _update($id, $data)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_store_items');
    $this->mdl_store_items->_update($id, $data);
  }

  function _delete($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_store_items');
    $this->mdl_store_items->_delete($id);
  }

  function _delete_custom($col, $val) {
    $mysql_query = "DELETE FROM store_items WHERE $col = '$value'";
    $this->_custom_query($mysql_query);
  }

  function count_where($column, $value)
  {
    $this->load->model('mdl_store_items');
    $count = $this->mdl_store_items->count_where($column, $value);
    return $count;
  }

  function get_max()
  {
    $this->load->model('mdl_store_items');
    $max_id = $this->mdl_store_items->get_max();
    return $max_id;
  }

  function _custom_query($mysql_query)
  {
    $this->load->model('mdl_store_items');
    $query = $this->mdl_store_items->_custom_query($mysql_query);
    return $query;
  }

  // a method to check if the item name exists.
  function item_check($str) {

    $item_url = url_title($str);
    $mysql_query = "SELECT * FROM store_items WHERE item_title = '$str' AND item_url = '$item_url'";

    $item_id = $this->uri->segment(3);
    if (is_numeric($item_id)) {
      // this is an update
      $mysql_query .= "AND id != $item_id";
    }

    $query = $this->_custom_query($mysql_query);
    $num_rows = $query->num_rows();

    if ($num_rows > 0) {
      $this->custom_validation->set_message('item_check', 'The item title that you submitted is not available.');
      return false;
    } else {
      return true;
    }
  }

}
