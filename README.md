# PHP - ReCaptcha
A wrapper class for ReCaptcha to make integrating into your site dead simple.

### Created with love by HTMLGuy, LLC
 
## Important Notes:

* Will use Guzzle by default, if available...otherwise fallsback to CURL with non-ideal settings
(SSL_VERIFY_PEER/SSL_VERIFY_HOST = false) - I recommend you add a cacert and change these to true.
* If ANYTHING fails, it will default to allowing the request. This way you don't miss out if recaptcha is down or something.

## To use:

Create an account on Google and navigate to their ReCaptcha service here:
```link
https://www.google.com/recaptcha/admin
```
___
Include this package with composer:
```bash
composer require versatilitywerks/phprecaptcha
```
___
Register a new site, then keep the tab open with your Site Key and Secret visible.
___
+ __Either__ Copy the google_credentials.example.ini and create a file named google_credentials.ini in the same folder:
 1. Swap out {{YOUR_SECRET}} for the Secret key on Google.
 2. Swap out {{YOUR_SITE_KEY}} for the Site Key on google.
 
+ __OR__ set your credentials in environment variables with the following keys:
 1. RECAPTCHA_SECRET
 2. RECAPTCHA_SITE_KEY
 
+ __OR__ store our credentials however you want and pass them during instantiation:
 ```php
 $captcha = new \HTMLGuyLLC\ReCaptcha($secret, $site_key);
 ```
___

Include the following in your HTML:
```html
<script src='https://www.google.com/recaptcha/api.js'></script>
```
___
Use the following where you want to display the captcha (presumably in a form):

```php
$captcha = new \HTMLGuyLLC\ReCaptcha();
echo $captcha->display();
```
___
To validate a captcha after it's been completed and the form has been posted, run the verify() method.
```php
try
{
    $captcha = new \HTMLGuyLLC\ReCaptcha();
    if( !$captcha->verify() )
    {
        //user failed to complete the captcha correctly
    }
}
catch(\HTMLGuyLLC\ReCaptchaExeption $e)
{
    //do something specific for errors with recaptcha or this class
}
catch(\Exception $e)
{
    //catch any unexpected exceptions
}
```
___
In Javascript, if you use AJAX to submit the form, you'll want to regenerate the captcha.
Use the following in your AJAX complete callback:

```javascript
grecaptcha.reset();
```
___
## Implementation idea:

* You can add a global AJAX "complete" callback which updates any captcha on the page by default by using the following:

```javascript
$(document).ajaxComplete(function(event,request,settings){
  if( typeof grecaptcha !== 'undefined' )
  {
   grecaptcha.reset()
  }
});
```

Dependencies
=======
PHP, CURL, ReCaptcha, Guzzle (optional)

License
=======
MIT License

Copyright (c) 2018 Shane Stebner

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
