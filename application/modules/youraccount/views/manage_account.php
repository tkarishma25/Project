<?php
$form_location = base_url().'youraccount/update_account';
?>
<div class="container">
  <h1>Update Account</h1>
  <?php
  if (isset($validation_errors)) {
    echo $validation_errors;
  }
  if (isset($flash)) {
    echo $flash;
  }
  ?>
  <form class="form-horizontal" action="<?= $form_location ?>" method="post">
    <fieldset>

      <!-- Text input-->
      <div class="form-group">
        <label class="col-md-4 control-label" for="textinput">First Name</label>
        <div class="col-md-4">
          <input id="textinput" name="first_name" value="<?= $first_name ?>" type="text" class="form-control input-md" required="">
        </div>
      </div>

      <!-- Text input-->
      <div class="form-group">
        <label class="col-md-4 control-label" for="textinput">Last Name</label>
        <div class="col-md-4">
          <input id="textinput" name="last_name" value="<?= $last_name ?>" type="text" class="form-control input-md" required="">
        </div>
      </div>

      <!-- Text input-->
      <div class="form-group">
        <label class="col-md-4 control-label" for="textinput">Username</label>
        <div class="col-md-4">
          <input id="textinput" name="user_name" value="<?= $user_name ?>" type="text" class="form-control input-md" required="">
        </div>
      </div>

      <!-- Text input-->
      <div class="form-group">
        <label class="col-md-4 control-label" for="textinput">E-mail</label>
        <div class="col-md-4">
          <input id="textinput" name="email" value="<?= $email ?>" type="text" class="form-control input-md">
        </div>
      </div>

      <!-- Button -->
      <div class="form-group">
        <div class="col-md-4">
          <button id="singlebutton" name="submit" value="submit" class="btn btn-primary">Update</button>
        </div>
      </div>

    </fieldset>
  </form>
</div>
