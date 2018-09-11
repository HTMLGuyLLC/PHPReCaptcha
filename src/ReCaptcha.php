<?php
namespace HTMLGuyLLC;

/**
 * ReCaptcha wrapper class
 * Version 1.1.0
 *
 * https://github.com/HTMLGuyLLC/PHPReCaptcha
 */
class ReCaptcha
{
    const API_BASE = 'https://www.google.com/recaptcha/api';
    const VERIFY_ENDPOINT = 'siteverify';
    const POST_KEY = 'g-recaptcha-response';
    
    public function __construct($secret = null, $site_key = null)
    {
        //if not overwriting both
        if( $secret === null || $site_key === null )
        {
            //get from environment variables
            $secret = getenv('RECAPTCHA_SECRET');
            $site_key = getenv('RECAPTCHA_SITE_KEY');
            
            //if one is set and one is missing
            if( ($secret && !$site_key) || (!$secret && $site_key) )
            {
                throw new ReCaptchaException("One of your ReCaptcha google credentials are missing from the environment variables");
            }
            
            //if both are set
            if( $secret && $site_key )
            {
                $this->secret = $secret;
                $this->site_key = $site_key;
            } 
            //otherwise none are set, fallback to .ini
            else
            {
                $creds = parse_ini_file(__DIR__.'/../google_credentials.ini');

                if( !$creds )
                {
                    throw new ReCaptchaException("ReCaptcha google credentials file did not return any values");
                }

                if( !isset($creds['secret']) && $secret === null )
                {
                    throw new ReCaptchaException("ReCaptcha google credentials file missing secret");
                }

                if( !isset($creds['site_key']) && $site_key === null )
                {
                    throw new ReCaptchaException("ReCaptcha google credentials file missing site key");
                }

                $this->secret = $creds['secret'];
                $this->site_key = $creds['site_key'];
            }
        }
        else
        {
            $this->secret = $secret;
            $this->site_key = $site_key;
        }
    }

    /**
     * Throws an exception
     *
     * @param $msg
     *
     * @throws \Exception
     */
    public static function error($msg)
    {
        throw new ReCaptchaException($msg);
    }

    /**
     * Display the captcha
     *
     * @return string
     */
    public function display()
    {
        return '<div class="g-recaptcha" data-sitekey="' . $this->site_key . '"></div>';
    }

    /**
     * Verify the user's response
     */
    public function verify($response = false, $ip_address = false)
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
            'secret' => $this->secret,
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
     * @param $post
     *
     * @return mixed
     * @internal param $url
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
