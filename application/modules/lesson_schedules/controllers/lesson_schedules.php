<?php
class Lesson_schedules extends MX_Controller {

  function __construct() {
    parent::__construct();
    $this->load->module('custom_pagination');
  }

  function view_booked_users($lesson_id, $lesson_schedule_id) {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    $mysql_query = "SELECT u.first_name, u.last_name, u.email FROM users u JOIN lesson_bookings lb ON u.id = lb.user_id WHERE lb.lesson_schedule_id = $lesson_schedule_id";
    $query = $this->_custom_query($mysql_query);

    $data['query'] = $query;
    $data['lesson_id'] = $lesson_id;
    $data['view_file'] = "view_booked_users";
    $data['headline'] = "View Members";
    $data['num_of_users'] = $query->num_rows();
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  // shows all the schedules in a table
  function manage_lesson_schedules($lesson_id) {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();
    $this->load->module('lessons');

    $mysql_query = "SELECT * FROM lesson_schedules WHERE lesson_id = $lesson_id ORDER BY lesson_start_date DESC";
    $lesson_capacity = $this->lessons->get_where($lesson_id)->row()->lesson_capacity;
    $lesson_name = $this->lessons->get_where($lesson_id)->row()->lesson_name;

    $query = $this->_custom_query($mysql_query);
    $total_lesson_schedules = $query->num_rows();

    $pagination_data['template'] = "public_bootstrap";
    $pagination_data['target_base_url'] = $this->get_target_pagination_base_url();
    $pagination_data['total_rows'] = $total_lesson_schedules;
    $pagination_data['offset_segment'] = 4;
    $pagination_data['limit'] = $this->get_pagination_limit();
    $data['pagination'] = $this->custom_pagination->_generate_pagination($pagination_data);

    $data['headline'] = "Manage Schedules";
    $data['lesson_name'] = $lesson_name;
    $data['lesson_id'] = $lesson_id;
    $data['lesson_capacity'] = $lesson_capacity;
    $data['query'] = $query;
    $data['view_file'] = "manage_lesson_schedules";
    $data['flash'] = $this->session->flashdata('item');
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function create_lesson_schedule($lesson_id) {
    $this->load->module('site_security');
    $this->load->module('timedate');
    $this->load->library('session');
    $this->site_security->_make_sure_is_admin();

    $submit = $this->input->post('submit', true);
    $lesson_schedule_id = $this->uri->segment(4);

    if ($submit == "cancel") {
      redirect('lesson_schedules/manage_lesson_schedules/'.$lesson_id);
    } else if ($submit == "submit") {
      $this->load->module('custom_validation');
      $this->custom_validation->set_rules('lesson_start_time', 'Start Time', 'min_length[7]|max_length[8]');
      $this->custom_validation->set_rules('lesson_end_time', 'End Time', 'min_length[7]|max_length[8]');
      if ($this->custom_validation->run()) {
        $data = $this->fetch_data_from_post();
        if (isset($lesson_schedule_id)) {
          // update
          $data['lesson_id'] = $lesson_id;
          unset($data['lesson_date']);
          $this->_update($lesson_schedule_id, $data);
          $flash_msg = "The schedule was successfully updated.";
          $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
          $this->session->set_flashdata('item', $value);
          redirect("lesson_schedules/create_lesson_schedule/".$lesson_id."/".$lesson_schedule_id);
        } else {
          // insert
          unset($data['lesson_date']);
          $data['lesson_id'] = $lesson_id;
          $this->_insert($data);
          $flash_msg = "The schedule was successfully added.";
          $value = '<div class="alert alert-success" role="alert">'.$flash_msg.'</div>';
          $this->session->set_flashdata('item', $value);
          redirect('lesson_schedules/manage_lesson_schedules/'.$lesson_id);
        }
      }
    }

    if ((isset($lesson_schedule_id)) && ($submit != "submit")) {
      $data = $this->fetch_data_from_db($lesson_schedule_id);
    } else {
      $data = $this->fetch_data_from_post();
    }

    if (!isset($lesson_schedule_id)) {
      $lesson_schedule_id = "";
      $data['headline'] = "Craete New Lesson Schedule";
    } else {
      $data['headline'] = "Update Lesson Schedule";
    }

    $data['lesson_id'] = $lesson_id;
    $data['lesson_schedule_id'] = $lesson_schedule_id;
    $data['view_file'] = "create_lesson_schedule";
    $data['flash'] = $this->session->flashdata('item');
    if ($this->session->has_userdata('validation_errors')) {
      $data['validation_errors'] = $this->session->userdata('validation_errors');
      $this->session->unset_userdata('validation_errors');
    }
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function lesson_schedule_deleteconf($lesson_id, $lesson_schedule_id) {
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();

    if (!is_numeric($lesson_schedule_id)) {
      redirect('site_security/not_allowed');
    }
    $data['lesson_id'] = $lesson_id;
    $data['lesson_schedule_id'] = $lesson_schedule_id;
    $data['headline'] = "Delete Lesson Schedule";

    $data['flash'] = $this->session->flashdata('item');
    $data['view_file'] = "lesson_schedule_deleteconf";
    $this->load->module('templates');
    $this->templates->admin($data);
  }

  function delete_lesson_schedule($lesson_id, $lesson_schedule_id) {
    if (!is_numeric($lesson_schedule_id)) {
      redirect('site_security/not_allowed');
    }
    $this->load->module('site_security');
    $this->site_security->_make_sure_is_admin();
    $this->load->library('session');

    $submit = $this->input->post('submit', true);

    if ($submit == "cancel") {
      redirect('lesson_schedules/create_lesson_schedule/'.$lesson_id.'/'.$lesson_schedule_id);
    } else if ($submit == "delete") {
      $this->_process_delete_lesson_schedule($lesson_schedule_id);
      $flash_msg = "The schedule was successfully deleted.";
      $value = '<div class="alert alert-success role="alert">'.$flash_msg.'</div>';
      $this->session->set_flashdata('item', $value);
      redirect('lesson_schedules/manage_lesson_schedules/'.$lesson_id);
    }
  }

  function _process_delete_lesson_schedule($lesson_schedule_id) {
    $this->_delete($lesson_schedule_id);
  }

  // beginning of pagination methods
  function get_pagination_limit() {
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

  function fetch_data_from_post() {
    $this->load->module('timedate');
    $lesson_date = $this->input->post('lesson_date', true);
    $lesson_start_time = $this->input->post('lesson_start_time', true);
    $lesson_end_time = $this->input->post('lesson_end_time', true);
    $data['lesson_date'] = $lesson_date;
    $data['lesson_start_date'] = $this->timedate->make_timestamp_from_datetime($lesson_date.' '.$lesson_start_time);
    $data['lesson_end_date'] = $this->timedate->make_timestamp_from_datetime($lesson_date.' '.$lesson_end_time);
    return $data;
  }

  function fetch_data_from_db($lesson_schedule_id) {
    $this->load->module('site_security');
    $this->load->module('timedate');
    $this->site_security->_make_sure_is_admin();
    if (!is_numeric($lesson_schedule_id)) {
      redirect(base_url());
    }
    $query = $this->get_where($lesson_schedule_id);
    $row = $query->row();
    $data['lesson_date'] = $this->timedate->get_date($row->lesson_start_date, "datepicker_us");
    $data['lesson_start_date'] = $this->timedate->get_time($row->lesson_start_date);
    $data['lesson_end_date'] = $this->timedate->get_time($row->lesson_end_date);
    return $data;
  }

  function get($order_by) {
    $this->load->model('mdl_lesson_schedules');
    $query = $this->mdl_lesson_schedules->get($order_by);
    return $query;
  }

  function get_with_limit($limit, $offset, $order_by) {
    if ((!is_numeric($limit)) || (!is_numeric($offset))) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_lesson_schedules');
    $query = $this->mdl_lesson_schedules->get_with_limit($limit, $offset, $order_by);
    return $query;
  }

  function get_where($id) {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_lesson_schedules');
    $query = $this->mdl_lesson_schedules->get_where($id);
    return $query;
  }

  function get_where_custom($col, $value) {
    $this->load->model('mdl_lesson_schedules');
    $query = $this->mdl_lesson_schedules->get_where_custom($col, $value);
    return $query;
  }

  function _insert($data) {
    $this->load->model('mdl_lesson_schedules');
    $this->mdl_lesson_schedules->_insert($data);
  }

  function _update($id, $data) {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_lesson_schedules');
    $this->mdl_lesson_schedules->_update($id, $data);
  }

  function _delete($id) {
    if (!is_numeric($id)) {
      die('Non-numeric variable!');
    }

    $this->load->model('mdl_lesson_schedules');
    $this->mdl_lesson_schedules->_delete($id);
  }

  function _delete_where($col, $value) {
    $this->load->model('mdl_lesson_schedules');
    $this->mdl_lesson_schedules->_delete_where($col, $value);
  }

  function count_where($column, $value) {
    $this->load->model('mdl_lesson_schedules');
    $count = $this->mdl_lesson_schedules->count_where($column, $value);
    return $count;
  }

  function get_max() {
    $this->load->model('mdl_lesson_schedules');
    $max_id = $this->mdl_lesson_schedules->get_max();
    return $max_id;
  }

  function _custom_query($mysql_query) {
    $this->load->model('mdl_lesson_schedules');
    $query = $this->mdl_lesson_schedules->_custom_query($mysql_query);
    return $query;
  }

}
