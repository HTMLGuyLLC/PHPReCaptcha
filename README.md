# PHP - ReCaptcha
A wrapper class for ReCaptcha to make integrating into your site dead simple.

### Created with love by VersatilityWerks http://flwebsites.biz
 
##Important Notes:

* Will use Guzzle by default, if available...otherwise fallsback to CURL with non-ideal settings
(SSL_VERIFY_PEER/SSL_VERIFY_HOST = false) - I recommend you add a cacert and change these to true.
* If ANYTHING fails, it will default to allowing the request. This way you don't miss out if recaptcha is down or something.

## To use: 

Create an account on Google and navigate to their ReCaptcha service here:
```link
https://www.google.com/recaptcha/admin
```

Register a new site, then keep the tab open with your Site Key and Secret visible.

Open the ReCaptcha.php file:
 1. Swap out {{YOUR_SECRET}} for the Secret key on Google.
 2. Swap out {{YOUR_SITE_KEY}} for the Site Key on google.
 3. Add an error handler (log it, send an email, etc - this is for issue regarding connection and such. Not captcha failures)
 
Include this class (or use an autoloader - preferred)
```php
include('ReCaptcha.php');
```

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

##Implementation suggestions/ideas:

* You can add a global AJAX "complete" callback which updates any captcha on the page by default by using the following:

```javascript
$(document).ajaxComplete(function(event,request,settings){
  if( typeof grecaptcha !== 'undefined' )
  {
   grecaptcha.reset()
  }
});
```

* If you're using a templating engine or tokenized HTML, you can set a variable as the return from display()
```php
$captcha = ReCaptcha->display();
```

* Not ideal, but you could just include the JS file as-needed by putting it in the display() method
...assuming you only call display() once per page.

Dependencies
=======
PHP, CURL, ReCaptcha, Guzzle (not required)

License
=======
M.I.T.

Copyright (c) 2015 Versatility Werks

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions: 

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software. 

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
