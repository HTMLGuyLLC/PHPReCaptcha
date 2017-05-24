<?php
//include the class from composer
include('vendor/autoload.php');

//errors could occur here or during verify, you should always wrap in try{}catch{} blocks, see details in README.
$captcha = new \VersatilityWerks\ReCaptcha();

//if the form was posted
if( !empty($_POST) )
{
  //verify the captcha
  if( !$captcha->verify() )
  {
    echo 'Failed!<br>';
  }
  else
  {
    echo 'Success!<br>';
  }
}
?>
<html>
  <head>
  <!-- Include Google's JS -->
  <script src='https://www.google.com/recaptcha/api.js'></script>
  </head>
  <body>
    <form action='Sample.php' method='post'>
      <!-- Display the captcha here -->
      <?php
      echo $captcha->display();
      ?>
      <input type='submit' value='Submit'>
    </form>
  </body>
</html>
