<?php
$this->load->module('site_security');
$user_id = $this->site_security->_get_user_id();
$signin_signup_url = base_url()."youraccount/start";
$userName = $this->session->userdata('userName');
?>
<div class="toolbar">
  <div class="inner" >
    <div class="tools">
      <div class="account"><a href="#"></a><i class="icon-head"></i>
        <ul class="toolbar-dropdown">
          <?php
          if ($user_id == "") {
            ?>
            <li><a href="<?= $signin_signup_url?>">Signup/Login</a></li>
            <?php
          } else {
            ?>
            <li class="sub-menu-user">
              <div class="user-info">
                <h6 class="user-name"><?= $userName?></h6><span class="text-xs text-muted"></span>
              </div>
            </li>
            <li><a href="<?= base_url() ?>listed_items/manage"><span class="glyphicon glyphicon-tasks"></span> My Items</a></li>
            <li><a href="<?= base_url() ?>lessons/view_my_lessons"><span class="glyphicon glyphicon-tasks"></span> My Booked Lessons</a></li>
            <li><a href="<?= base_url() ?>boat_rental/view_my_rental_boats"><span class="glyphicon glyphicon-tasks"></span> My Booked Rental Boats</a></li>
            <li><a href="<?= base_url() ?>youraccount/manage_account"><span class="glyphicon glyphicon-file"></span> Manage Profile</a></li>
            <li class="sub-menu-separator"></li>
            <li><a href="<?= base_url() ?>youraccount/logout"><span class="glyphicon glyphicon-log-out"></span> Logout</a><li>
            </ul>
            <?php
          }
          ?>
        </div>
        <div class="account"><a href="<?= base_url() ?>cart"></a><i class="icon-bag"></i></div>
        <div class="search"><i class="icon-search"></i></div>
      </div>
    </div>
  </div>
