<?php
$first_bit = $this->uri->segment(1);
$signin_form_location = base_url().$first_bit.'/submit_login';
$signup_form_location = base_url().'youraccount/submit';
$reset_form_location = base_url().'youraccount/recover_password';
if ($first_bit == "youraccount") {
  $label = "Sign in";
} else {
  $label = "Admin Login";
}
?>
<div class="container padding-bottom-3x mb-2">
  <?php
  if (isset($validation_errors))
  echo $validation_errors;
  ?>
  <div class="row">
    <div class="col-md-6">
      <form class="login-box" method="post" action="<?= $signin_form_location ?>">
        <h4 class="margin-bottom-1x"><?= $label ?></h4>
        <div class="form-group input-group">
          <input class="form-control" type="text" id="loginEmail" name="loginEmail" placeholder="Email" required><span class="input-group-addon"><i class="icon-mail"></i></span>
        </div>
        <div class="form-group input-group">
          <input class="form-control" name="loginPassword" id="loginPassword" type="password" placeholder="Password" required><span class="input-group-addon"><i class="icon-lock"></i></span>
        </div>
        <div class="d-flex flex-wrap justify-content-between padding-bottom-1x">
          <div class="custom-control custom-checkbox">
            <?php
            if ($first_bit == "youraccount") {
              ?>
              <input class="custom-control-input" type="checkbox" id="remember" value="remember" name="remember">
              <label class="custom-control-label" for="remember">Remember me</label>
              <?php
            }
            ?>
          </div><a class="navi-link" href="<?= $reset_form_location ?>">Forgot password?</a>
        </div>
        <div class="text-center text-sm-right">
          <button class="btn btn-primary margin-bottom-none" name="submit" value="submit" type="submit">Sign In</button>
        </div>
      </form>
    </div>
    <?php
    if ($first_bit == "youraccount") {
      ?>
      <div class="col-md-6">
        <div class="padding-top-3x hidden-md-up"></div>
        <h3 class="margin-bottom-1x">No Account? Sign Up</h3>
        <p>Registration takes less than a minute but gives you full control over your orders and posts.</p>
        <form class="row" method="post" action="<?= $signup_form_location ?>">
          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-email">First Name</label>
              <input class="form-control" name="signupFirstName" value="<?= $signupFirstName ?>" type="text" required>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-email">Last Name</label>
              <input class="form-control" name="signupLastName" value="<?= $signupLastName ?>" type="text" required>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-email">Username</label>
              <input class="form-control" name="signupUserName" value="<?= $signupUserName ?>" type="text" required>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-email">E-mail Address</label>
              <input class="form-control" name="signUpEmail" value="<?= $signUpEmail ?>" type="email" required>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-pass">Password</label>
              <input class="form-control" name="signUpPassword" value="<?= $signUpPassword ?>" type="password" required>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <label for="reg-pass-confirm">Confirm Password</label>
              <input class="form-control" name="signUpconfirmPassword" value="<?= $signUpconfirmPassword ?>" type="password" required>
            </div>
          </div>
          <div class="col-12 text-center text-sm-right">
            <button class="btn btn-primary margin-bottom-none" name="submit" value="submit" type="submit">Register</button>
          </div>
        </form>
      </div>
      <?php
    }
    ?>
  </div>

</div>
