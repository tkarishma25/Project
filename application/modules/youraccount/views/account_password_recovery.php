<?php
$first_bit = $this->uri->segment(1);
$forgot_password_form_location = base_url().'youraccount/forgot_password';
?>
<div class="container">
  <h3 class="col-md-6">Forgot Password?</h3>
  <?php
  if (isset($validation_errors)) {
    echo $validation_errors;
  }
  ?>
  <div id="validation-message">
    <?php if(!empty($error_message)) { ?>
      <?php echo $error_message; ?>
    <?php } ?>
  </div>
  <div style="margin-top: 40px"></div>
  <div class="container padding-bottom-3x mb-2">
    <div class="row">
      <div class="col-md-4">
        <form class="reset-box" method="post" action="<?= $forgot_password_form_location ?>">
          <div class="form-group input-group">
            <input class="form-control" type="text" id="email" name="email" placeholder="Email" required><span class="input-group-addon"><i class="icon-mail"></i></span>
          </div>
          <div class="col-12 text-center text-sm-right">
            <button class="btn btn-primary margin-bottom-none" name="submit" value="submit" type="submit">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div style="margin-bottom: 200px;"></div>
</div>
