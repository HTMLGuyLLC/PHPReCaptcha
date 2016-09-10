<?php

/**
 * Displays and tests a capcha from Google's ReCaptcha service.
 *
 * Will use Guzzle by default, if available...otherwise fallsback to CURL with non-ideal settings
 * (SSL_VERIFY_PEER/SSL_VERIFY_HOST = false) - I recommend you add a cacert and change these to true.
 *
 * If ANYTHING fails, it will default to allowing the request. This way you don't miss out if recaptcha is down or something.
 *
 * To use:
 *
 * Create an account on Google and navigate to their ReCaptcha service here:
 *   https://www.google.com/recaptcha/admin
 *
 * Register a new site, then keep the tab open with your Site Key and Secret visible.
 *
 * Open the ReCaptcha.php file:
 *   1. Swap out {{YOUR_SECRET}} for the Secret key on Google.
 *   2. Swap out {{YOUR_SITE_KEY}} for the Site Key on google.
 *   3. Add an error handler (log it, send an email, etc - this is for issue regarding connection and such. Not captcha failures)
 *
 * Include the following in your HTML:
 *   <script src='https://www.google.com/recaptcha/api.js'></script>
 *
 * Use the following where you want to display the captcha:
 *   <?php echo ReCaptcha::display(); ?>
 *
 * To validate a captcha, run the verify() method.
 * If you don't provide the user's IP address, it'll fallback to $_SERVER['REMOTE_ADDR']
 * If you don't provide the user's response, it'll fallback to grabbing it from the $_POST
 *    <?php
 *    if( !ReCaptcha::verify($users_response, $users_ip_address) )
 *    {
 *      //error
 *    }
 *    ?>
 *
 * In Javascript, if you use AJAX to submit the form, you'll want to regenerate the captcha.
 * Use the following in your AJAX complete callback:
 *   grecaptcha.reset()
 *
 *
 * Implementation suggestions/ideas:
 *
 * 1. You can add a global AJAX "complete" callback which updates any captcha on the page by default by using the following:
 *   $(document).ajaxComplete(function(event,request,settings){
 *      if( typeof grecaptcha !== 'undefined' )
 *      {
 *         grecaptcha.reset()
 *      }
 *   });
 *
 * 2. If you're using a templating engine or tokenized HTML, you can set a variable as the return of
 *    ReCaptcha->display() and avoid spaghetti.
 *
 * 3. Not ideal, but you could just include the JS file as-needed by putting it in the display() method
 *    ...assuming you only call display() once per page.
 *
 * Class ReCaptcha
 */
class ReCaptcha
{
    const API_BASE = 'https://www.google.com/recaptcha/api';
    const VERIFY_ENDPOINT = 'siteverify';
    const POST_KEY = 'g-recaptcha-response';
    const SECRET = '{{YOUR_SECRET}}';
    const SITE_KEY = '{{YOUR_SITE_KEY}}';

    /**
     * Sends an error message to the site owner
     * @param $msg
     */
    public static function error($msg)
    {
        //or log it. whatev. It's up to your implementation.
    }

    /**
     * Display the captcha
     *
     * @return string
     */
    public static function display()
    {
        return '<div class="g-recaptcha" data-sitekey="' . ReCaptcha::SITE_KEY . '"></div>';
    }

    /**
     * Verify the user's response
     */
    public static function verify($response = false, $ip_address = false)
    {
        ///if no IP address, just immediately deny the request
        if(!$ip_address)
        {
            if( !isset($_SERVER['REMOTE_ADDR']) )
            {
                return false;
            }

            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        //if no response to the captcha passed
        if(!$response)
        {
            //if it's in the POST, get it from there
            if(!empty($_POST[ReCaptcha::POST_KEY]))
            {
                $response = $_POST[ReCaptcha::POST_KEY];
            }
            //otherwise fail
            else
            {
                return false;
            }
        }

        //array of data to post to google
        $send = [
            'secret' => ReCaptcha::SECRET,
            'response' => $response,
            'remoteip' => $ip_address
        ];

        //set Guzzle Client class as a var
        $guzzle = 'Guzzle\Http\Client';

        //if guzzle exists, use it
        if( class_exists($guzzle) )
        {
            $body = ReCaptcha::guzzle_validate($guzzle, $send);
        }
        //fallback - use CURL
        else
        {
            $body = ReCaptcha::curl_validate($send);
        }

        //if something failed with the curl request
        if( !$body )
        {
            return true;
        }

        //decode the response
        try
        {
            $decoded = json_decode($body, true);
        }
            //if fails to decode due to exception
        catch(\Exception $e)
        {
            ReCaptcha::error("Exception occurred when trying to JSON decode result for recaptcha: " . $e->getMessage() . "<br><br>Body: " . $body);
            return true;
        }

        //if body is not json, allow the request to go through anyway and email us
        if(!is_array($decoded))
        {
            ReCaptcha::error("Did not get JSON string back from google for recaptcha. Received: " . $body);
            return true;
        }

        //if they sent back success = false, the user DID NOT respond correctly, so return false to block the request
        if(isset($decoded['success']) && $decoded['success'] === false)
        {
            //if not due to user entry error, send us an email
            if(isset($decoded['error-codes']) && isset($decoded['error-codes'][0]) && $decoded['error-codes'][0] != 'invalid-input-response')
            {
                ReCaptcha::error("Recaptcha replied with failure, but it was not due to the user's response: " . $body);
                return true;
            }

            return false;
        }
        //malformed response (no success param)
        elseif(!isset($decoded['success']))
        {
            ReCaptcha::error("Recaptcha response doesn't include the key 'success':" . $body);
            return true;
        }

        //assume it was good otherwise.
        return true;
    }

    /**
     * Uses Guzzle to validate a captcha.
     * Returns the response, OR false if it failed
     *
     * @param $guzzle
     * @param $send
     * @return bool
     */
    public static function guzzle_validate($guzzle, $send)
    {
        $client = new $guzzle(ReCaptcha::API_BASE);

        //set post
        $request = $client->post(ReCaptcha::VERIFY_ENDPOINT, null, $send);

        //get response
        try
        {
            $response = $request->send();
        }
            //if exception occurs, return true anyway and email us
        catch(\Exception $e)
        {
            ReCaptcha::error("Exception occurred when trying to get result for recaptcha: " . $e->getMessage());
            return false;
        }

        //if http status code isn't 200, allow anyway and email us.
        if($response->getStatusCode() != 200)
        {
            ReCaptcha::error("Failed to get response from google for recaptcha (http status code: " . $response->getStatusCode() . "). User was allowed to submit content anyway.");
            return false;
        }

        return $response->getBody();
    }

    /**
     * Fallback CURL request to validate a captcha
     *
     * @param $url
     * @param $post
     * @return mixed
     */
    public static function curl_validate($post)
    {
        try
        {
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, ReCaptcha::API_BASE . '/' . ReCaptcha::VERIFY_ENDPOINT);
            curl_setopt($ch, CURLOPT_POST, count($post));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //execute post
            $result = curl_exec($ch);

            //get error (if any)
            $error = curl_error($ch);

            //get http code
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //close connection
            curl_close($ch);

            //if there was a curl error
            if($error)
            {
                ReCaptcha::error("Error getting result from recaptcha using CURL: " . $error);
                return false;
            }

            //if http code != 200
            if($http_code != 200)
            {
                ReCaptcha::error("Error getting result from recaptcha using CURL - HTTP Code is not 200: " . $http_code);
                return false;
            }
        }
        //if exception occurs, return true anyway and email us
        catch(\Exception $e)
        {
            ReCaptcha::error("Exception occurred when trying to get result for recaptcha: " . $e->getMessage());
            return false;
        }

        return $result;
    }
}