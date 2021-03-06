<?php
class Paypal extends MX_Controller {

  function __construct() {
    parent::__construct();
  }

  function ipn_listener() {
    // the URL that accepts things that Paypal has posted
    header('HTTP/1.1 200 OK');

    // STEP 1: read POST data
    // Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
    // Instead, read raw POST data from the input stream.
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
      $keyval = explode ('=', $keyval);
      if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
    // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
    $req = 'cmd=_notify-validate';
    if (function_exists('get_magic_quotes_gpc')) {
      $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
      if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
      } else {
        $value = urlencode($value);
      }
      $req .= "&$key=$value";
    }

    // Step 2: POST IPN data back to PayPal to validate
    $ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
    // In wamp-like environments that do not come bundled with root authority certificates,
    // please download 'cacert.pem' from "https://curl.haxx.se/docs/caextract.html" and set
    // the directory path of the certificate as shown below:
    // curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
      // error_log("Got " . curl_error($ch) . " when processing IPN data");
   if ( !($res = curl_exec($ch)) ) {
      curl_close($ch);
      exit;
    }
    curl_close($ch);

    // inspect IPN validation result and act accordingly
    if (strcmp ($res, "VERIFIED") == 0) {
      // The IPN is verified, process it
        $this->load->module('site_security');
        $data['date_created'] = time();
        // makes an array of data retrieved from paypal
        foreach($_POST as $key => $value) {
          if($key=='custom') {
            $customer_session_id = $this->site_security->_decrypt_string($value);
            $value = $customer_session_id;
          }
          $posted_information[$key] = $value;
        }

        $data['posted_information'] = serialize($posted_information);
        $this->_insert($data);
        $this->update_details($customer_session_id);
    } else if (strcmp ($res, "INVALID") == 0) {
      // IPN invalid, log for manual investigation
    }

  }

 function update_details($customer_session_id) {
   $this->load->module('site_security');
//   $session_id = $customer_session_id;
//   $shopper_id = $this->site_security->_get_user_id();
   // if (!is_numeric($shopper_id)) {
   //   $shopper_id = 0;
   // }
   $table = "boat_basket";
   $lesson_table = "lesson_basket";
   $mysql_query = "SELECT * FROM boat_basket WHERE session_id=$customer_session_id";
   $mysql_query1 = "SELECT * FROM lesson_basket WHERE session_id=$customer_session_id";

   $query = $this->_custom_query($mysql_query1);
   $this->load->module('lesson_bookings');
   foreach($query->result() as $row) {
     $data['lesson_schedule_id'] = $row->schedule_id;
     $data['user_id'] = $row->shopper_id;
     $data['lesson_booking_qty'] = $row->booking_qty;
     $this->lesson_bookings->_insert($data);
   }

   $query = $this->_custom_query($mysql_query);
   $this->load->module('boat_rental_schedules');
   foreach($query->result() as $row) {
     $data['boat_rental_id'] = $row->boat_id;
     $data['user_id'] = $row->shopper_id;
     $data['boat_start_date'] = $row->booking_start_date;
     $data['boat_end_date'] = $row->booking_end_date;
     $this->boat_rental_schedules->_insert($data);
   }

   $this->_delete_lesson_basket_content($customer_session_id);
   $this->_delete_boat_basket_content($customer_session_id);

 }


  function _delete_lesson_basket_content($customer_session_id) {
    // fetch the contents of the shopping cart
    $this->load->module('lesson_basket');
    $mysql_query = "
    DELETE FROM lesson_basket WHERE session_id = $customer_session_id";
    $query = $this->lesson_basket->_custom_query($mysql_query);
  }

  function _delete_boat_basket_content($customer_session_id) {
    // fetch the contents of the shopping cart
    $this->load->module('boat_basket');
    $mysql_query = "
    DELETE FROM boat_basket WHERE session_id = $customer_session_id";
    $query = $this->boat_basket->_custom_query($mysql_query);
  }

  function _is_on_test_mode() {
    return FALSE; // set this to false if we are live!
  }

  function thankyou() {
    $data['view_file'] = 'thankyou';
    $this->load->module('templates');
    $this->templates->public_bootstrap($data);
  }

  function cancel() {
    $data['view_file'] = 'cancel';
    $this->load->module('templates');
    $this->templates->public_bootstrap($data);
  }

  function _draw_checkout_btn($query,$lesson_query) {
    $this->load->module('site_settings');
    $this->load->module('site_security');

    if($query->num_rows() > 0 ){
    foreach($query->result() as $row) {
      $session_id = $row->session_id;
    }
    }
    if($lesson_query->num_rows() > 0 )
    {
    foreach($lesson_query->result() as $row) {
      $session_id = $row->session_id;
    }
    }

    $on_test_mode = $this->_is_on_test_mode();
    if ($on_test_mode == true) {
      $data['form_location'] = "https://www.sandbox.paypal.com/cgi_bin/webscr";
    } else {
      $data['form_location'] = "https://www.paypal.com/cgi_bin/webscr";
    }
    // the data is sent to paypal
    $data['return'] = base_url().'paypal/thankyou';
    $data['cancel_return'] = base_url().'paypal/cancel';
    $data['custom'] = $this->site_security->_encrypt_string($session_id);
    $data['paypal_email'] = $this->site_settings->_get_paypal_email();
    $data['currency_code'] = $this->site_settings->_get_currency_code();
    $data['query'] = $query;
    $data['lesson_query;'] = $lesson_query;
    $this->load->view('checkout_btn', $data);
  }

  function get($order_by)
  {
    $this->load->model('mdl_paypal');
    $query = $this->mdl_paypal->get($order_by);
    return $query;
  }

  function get_with_limit($limit, $offset, $order_by)
  {
    if ((!is_numeric($limit)) || (!is_numeric($offset))) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_paypal');
    $query = $this->mdl_paypal->get_with_limit($limit, $offset, $order_by);
    return $query;
  }

  function get_where($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_paypal');
    $query = $this->mdl_paypal->get_where($id);
    return $query;
  }

  function get_where_custom($col, $value)
  {
    $this->load->model('mdl_paypal');
    $query = $this->mdl_paypal->get_where_custom($col, $value);
    return $query;
  }

  function _insert($data)
  {
    $this->load->model('mdl_paypal');
    $this->mdl_paypal->_insert($data);
  }

  function _update($id, $data)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_paypal');
    $this->mdl_paypal->_update($id, $data);
  }

  function _delete($id)
  {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_paypal');
    $this->mdl_paypal->_delete($id);
  }

  function count_where($column, $value)
  {
    $this->load->model('mdl_paypal');
    $count = $this->mdl_paypal->count_where($column, $value);
    return $count;
  }

  function get_max()
  {
    $this->load->model('mdl_paypal');
    $max_id = $this->mdl_paypal->get_max();
    return $max_id;
  }

  function _custom_query($mysql_query)
  {
    $this->load->model('mdl_paypal');
    $query = $this->mdl_paypal->_custom_query($mysql_query);
    return $query;
  }

}
