<?php
//include the class
include('ReCaptcha.php');

//if the form was posted
if( !empty($_POST) )
{
  //verify the captcha
  if( !ReCaptcha::verify() )
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
      echo ReCaptcha::display();
      ?>
      <input type='submit' value='Submit'>
    </form>
  </body>
</html>
