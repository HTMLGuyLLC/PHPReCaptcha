# PHP - ReCaptcha
A wrapper class for ReCaptcha to make integrating into your site dead simple.

### Created with love by VersatilityWerks http://flwebsites.biz
### MIT Licensed (do as you please)
 
### Important Notes:

* Will use Guzzle by default, if available...otherwise fallsback to CURL with non-ideal settings
(SSL_VERIFY_PEER/SSL_VERIFY_HOST = false) - I recommend you add a cacert and change these to true.
* If ANYTHING fails, it will default to allowing the request. This way you don't miss out if recaptcha is down or something.

### To use:

Create an account on Google and navigate to their ReCaptcha service here:
```link
https://www.google.com/recaptcha/admin
```

Register a new site, then keep the tab open with your Site Key and Secret visible.

Open the ReCaptcha.php file:
 1. Swap out {{YOUR_SECRET}} for the Secret key on Google.
 2. Swap out {{YOUR_SITE_KEY}} for the Site Key on google.
 3. Add an error handler (log it, send an email, etc - this is for issue regarding connection and such. Not captcha failures)

Include the following in your HTML:
```html
<script src='https://www.google.com/recaptcha/api.js'></script>
```

Use the following where you want to display the captcha:

```php
echo ReCaptcha::display();
```

To validate a captcha, run the verify() method.

* If you don't provide the user's IP address, it'll fallback to $_SERVER['REMOTE_ADDR']
* If you don't provide the user's response, it'll fallback to grabbing it from the $_POST

```php
if( !ReCaptcha::verify() )
{
 //error
}
//OR
if( !ReCaptcha::verify($users_response, $users_ip_address) )
{
 //error
}
```

In Javascript, if you use AJAX to submit the form, you'll want to regenerate the captcha.
Use the following in your AJAX complete callback:

```javascript
grecaptcha.reset();
```

### Implementation suggestions/ideas:

 1. You can add a global AJAX "complete" callback which updates any captcha on the page by default by using the following:

```javascript
$(document).ajaxComplete(function(event,request,settings){
  if( typeof grecaptcha !== 'undefined' )
  {
   grecaptcha.reset()
  }
});
```

2. If you're using a templating engine or tokenized HTML, you can set a variable as the return from display()
```php
$captcha = ReCaptcha->display();
```

 3. Not ideal, but you could just include the JS file as-needed by putting it in the display() method
 ...assuming you only call display() once per page.
