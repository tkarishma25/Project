<?php
class Enquiries extends MX_Controller {

  function __construct() {
    parent::__construct();
    $this->load->module('custom_pagination');
  }

  function _attempt_get_data_from_code($customer_id, $code) {
    // make sure customer is allowed to view/respond and fetch data
    $query = $this->get_where_custom('code', $code);
    $num_rows = $query->num_rows();

    foreach($query->result() as $row) {
      $data['subject'] = $row->subject;
      $data['message'] = $row->message;
      $data['sent_to'] = $row->sent_to;
      $data['date_created'] = $row->date_created;
      $data['opened'] = $row->opened;
      $data['sent_by'] = $row->sent_by;
      $data['urgent'] = $row->urgent;
    }
    // make sure code is good and customer is allowed to view/respond
    if (($num_rows < 1) OR ($customer_id != $data['sent_to'])) {
      redirect('site_security/not_allowed');
    }
    return $data;
  }

  function _draw_customer_inbox($customer_id) {
    // $this->output->enable_profiler(true);
    $folder_type = "inbox";
    $data['customer_id'] = $customer_id;
    $data['query'] = $this->_fetch_customer_enquiries($folder_type, $customer_id);
    $data['folder_type'] = ucfirst($folder_type);
    $data['flash'] = $this->session->flashdata('item');
    $this->load->view('customer_inbox', $data);
  }

  function say_my_name($firstName, $lastName=NULL) {
    if (!isset($lastName)) {
      echo "There's no last name";
    } else {
      echo "Hello $firstName $lastName";
    }
  }

  function create() {
    $this->load->module('site_security');
    $is_admin = $this->session->userdata('is_admin');
    $this->site_security->_make_sure_is_admin();

    $update_id = $this->uri->segment(3);
    $submit = $this->input->post('submit', true);
    $this->load->module('timedate');

    if ($submit == "cancel") {
      redirect('enquiries/inbox');
    } else if ($submit == "submit") {
      // process the form
      $this->load->module('custom_validation');
      $this->custom_validation->set_rules('subject', 'Subject', 'required|max_length[250]');

      if ($this->custom_validation->run() == true) {
        // get the variables and assign into $data variable
        $data = $this->fetch_data_from_post();
        // convert the datepicker into a unix timestamp
        $data['date_created'] = time();
        $data['sent_by'] = 0; // admin
        $data['opened'] = 0;
        $data['code'] = $this->site_security->generate_random_string(6);

        // insert a new item into DB
        $this->_insert($data);
        $flash_msg = "The message was successfully sent.";
        $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
        $this->session->set_flashdata('item', $value);
        redirect('enquiries/inbox');
      }
    }

    if ((is_numeric($update_id)) && ($submit != "Submit")) {
      $data = $this->fetch_data_from_db($update_id);
      $data['message'] = "<br><br>
      ----------------------------------------------------<br>
      The original message is shown blow...<br><br>".$data['message'];
    } else {
      $data = $this->fetch_data_from_post();
    }


    if (!is_numeric($update_id)) {
      $data['headline'] = "Compose New Message";
    } else {
      $data['headline'] = "Reply to Message";
    }

    $data['options'] = $this->_fetch_customers_as_options();
    // pass update id into the enquiries
    if ($this->session->has_userdata('validation_errors')) {
      $data['validation_errors'] = $this->session->userdata('validation_errors');
      $this->session->unset_userdata('validation_errors');
    }
    $data['update_id'] = $update_id;
    $data['flash'] = $this->session->flashdata('item');
    $data['view_file'] = "create";
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function _fetch_customers_as_options() {
    // for the dropdown menu
    $options[''] = "Select Customer...";
    $this->load->module('users');
    $query = $this->users->get('lastName');
    foreach ($query->result() as $row) {
      $customer_name = $row->firstName." ".$row->lastName;
      $company_length = strlen($row->company);
      if ($company_length > 2) {
        $customer_name.= " from ".$row->company;
      }
      $customer_name = trim($customer_name);
      if ($customer_name != "") {
        $options[$row->id] = $customer_name;
      }
    }
    if (!isset($options)) {
      $options = "";
    }
    return $options;
  }

  function view() {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $update_id = $this->uri->segment(3);
    $this->_set_to_opened($update_id);

    $options[''] = 'Select...';
    $options['0'] = '0 Star';
    $options['1'] = '1 Star';
    $options['2'] = '2 Stars';
    $options['3'] = '3 Stars';
    $options['4'] = '4 Stars';
    $options['5'] = '5 Stars';

    $data['options'] = $options;
    $data['update_id'] = $update_id;
    $data['headline'] = "Enquiry ID - ".$update_id;
    $data['query'] = $this->get_where($update_id);
    $data['flash'] = $this->session->flashdata('item');
    $data['view_file'] = "view";
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function submit_ranking() {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();
    $data['ranking'] = $this->input->post('ranking', true);
    $update_id = $this->uri->segment(3);
    $this->_update($update_id, $data);

    $flash_msg = "The enquiry ranking was successfully updated.";
    $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
    $this->session->set_flashdata('item', $value);

    redirect('enquiries/view/'.$update_id);
  }

  function _set_to_opened($update_id) {
    $data['opened'] = 1;
    $this->_update($update_id, $data);
  }

  function inbox() {
    // $this->output->enable_profiler(true);
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $folder_type = "inbox";
    $use_limit = false;
    $query = $this->_fetch_enquiries($folder_type, $use_limit);
    $total_enquiries = $query->num_rows();
    $pagination_data['template'] = "public_bootstrap";
    $pagination_data['target_base_url'] = $this->get_target_pagination_base_url();
    $pagination_data['total_rows'] = $total_enquiries;
    $pagination_data['offset_segment'] = 4;
    $pagination_data['limit'] = $this->_get_pagination_limit();
    $use_limit = true;
    $query = $this->_fetch_enquiries($folder_type, $use_limit);
    $data['pagination'] = $this->custom_pagination->_generate_pagination($pagination_data);
    $data['folder_type'] = ucfirst($folder_type);
    $data['query'] = $query;
    // gettinf flash data
    $data['flash'] = $this->session->flashdata('item');
    // store_Accounts.php
    $data['view_file'] = "view_enquiries";
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function _fetch_enquiries($folder_type, $use_limit) {
    // $mysql_query = "SELECT * FROM enquiries WHERE sent_to = 0 ORDER BY date_created DESC";
    $mysql_query = "
    SELECT e.*, sa.firstName, sa.lastName
    FROM enquiries e LEFT JOIN users sa ON e.sent_by = sa.id
    WHERE e.sent_to = 0
    ORDER BY e.date_created DESC
    ";
    if ($use_limit == true) {
      $limit = $this->_get_pagination_limit();
      $offset = $this->_get_pagination_offset();
      $mysql_query.= " LIMIT ".$offset.", ".$limit;
    }
    $query = $this->_custom_query($mysql_query);
    return $query;
  }

  function _fetch_customer_enquiries($folder_type, $customer_id) {
    $mysql_query = "
    SELECT e.*, sa.firstName, sa.lastName, sa.company
    FROM enquiries e JOIN users sa ON e.sent_to = sa.id
    WHERE e.sent_to = $customer_id
    ORDER BY e.date_created DESC
    ";
    $query = $this->_custom_query($mysql_query);
    return $query;
  }

  // get data from POST method
  function fetch_data_from_post() {
    $data['subject'] = $this->input->post('subject', true);
    $data['message'] = $this->input->post('message', true);
    $data['sent_to'] = $this->input->post('sent_to', true);
    return $data;
  }

  // get data from database
  function fetch_data_from_db($update_id) {
    if (!is_numeric($update_id)) {
      redirect('site_security/not_allowed');
    }
    $query = $this->get_where($update_id);
    foreach($query->result() as $row) {
      $data['subject'] = $row->subject;
      $data['message'] = $row->message;
      $data['sent_to'] = $row->sent_to;
      $data['date_created'] = $row->date_created;
      $data['opened'] = $row->opened;
      $data['sent_by'] = $row->sent_by;
      $data['ranking'] = $row->ranking;
    }
    if (!isset($data)) {
      $data = "";
    }
    return $data;
  }

  // beginning of pagination methods
  function _get_pagination_limit() {
    $limit = 20;
    return $limit;
  }

  function _get_pagination_offset() {
    $offset = $this->uri->segment(4);
    if (!is_numeric($offset)) {
      $offset = 0;
    }
    return $offset;
  }

  function get_target_pagination_base_url() {
    $first_bit = $this->uri->segment(1);
    $second_bit = $this->uri->segment(2);
    $third_bit = $this->uri->segment(3);
    $target_base_url = base_url().$first_bit."/".$second_bit."/".$third_bit;
    return $target_base_url;
  }
  // end of pagination methods

  function get($order_by)
  {
    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->get($order_by);
    return $query;
  }

  function get_with_limit($limit, $offset, $order_by)
  {
    if ((!is_numeric($limit)) || (!is_numeric($offset))) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->get_with_limit($limit, $offset, $order_by);
    return $query;
  }

  function get_where($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->get_where($id);
    return $query;
  }

  function get_where_custom($col, $value)
  {
    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->get_where_custom($col, $value);
    return $query;
  }

  function get_with_double_condition($col1, $value1, $col2, $value2)
  {
    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->get_with_double_condition($col1, $value1, $col2, $value2);
    return $query;
  }

  function _insert($data)
  {
    $this->load->model('mdl_enquiries');
    $this->mdl_enquiries->_insert($data);
  }

  function _update($id, $data)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_enquiries');
    $this->mdl_enquiries->_update($id, $data);
  }

  function _delete($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_enquiries');
    $this->mdl_enquiries->_delete($id);
  }

  function count_where($column, $value)
  {
    $this->load->model('mdl_enquiries');
    $count = $this->mdl_enquiries->count_where($column, $value);
    return $count;
  }

  function get_max()
  {
    $this->load->model('mdl_enquiries');
    $max_id = $this->mdl_enquiries->get_max();
    return $max_id;
  }

  function _custom_query($mysql_query)
  {
    $this->load->model('mdl_enquiries');
    $query = $this->mdl_enquiries->_custom_query($mysql_query);
    return $query;
  }

}
