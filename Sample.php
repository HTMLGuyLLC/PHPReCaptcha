<?php
include('ReCaptcha.php');

if( !empty($_POST) )
{
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
  <script src='https://www.google.com/recaptcha/api.js'></script>
  </head>
  <body>
    <form action='Sample.php' method='post'>
      <?php
      echo ReCaptcha::display();
      ?>
      <input type='submit' value='Submit'>
    </form>
  </body>
</html>
