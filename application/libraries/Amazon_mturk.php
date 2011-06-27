<?php

    /**
     * @author Hackable https://github.com/hackable/
     * @link https://github.com/hackable/ci_amazon_mturk
     * @package CodeIgniter Amazon MTurk Library
     * @version 1.0
     *
     * Creative Commons Attribution-ShareAlike 3.0 Unported License
     * http://creativecommons.org/licenses/by-sa/3.0/
     */

    class Amazon_mturk
    {
        // production endpoint
        const PRODUCTION = "http://mechanicalturk.amazonaws.com/";

        // sandbox endpoint
        const SANDBOX = "http://mechanicalturk.sandbox.amazonaws.com/";

        // service
        const SERVICE = "AWSMechanicalTurkRequester";

        // WSDL
        const WSDL = "http://mechanicalturk.amazonaws.com/AWSMechanicalTurk/AWSMechanicalTurkRequester.wsdl";

        /* Access Key ID */
        private $AWSAccessKeyId;

        /* Secret Access Key ID */
        private $AWSSecretAccessKeyId;

        /* SOAP client */
        private $client;

        /**
         * Constructor.
         *
         * @param AWSAccessKeyId        Access Key ID
         * @param AWSSecretAccessKeyId  Secret Access Key
         * @param options               [sandbox, trace]
         */
        public function __construct($options = array())
        {
          
	log_message('debug', 'Amazon Mturk Class Initialized');

	$this->_ci =& get_instance();
        $this->_ci->load->config('amazon_mturk');

  // require PHP 5.0.1+
            if (version_compare(PHP_VERSION, "5.0.1") < -1)
                throw new Exception("Amazon Mturk Library requires PHP 5.0.1 or higher");

            // ensure SOAP module exists
            if (!class_exists("SoapClient"))
                throw new Exception("Amazon Mturk Library requires PHP's SOAP module");

            // ensure timezone is set
            if (!ini_get("date.timezone"))
                date_default_timezone_set("UTC");

            // Access Key ID
            $this->AWSAccessKeyId = $this->_ci->config->item('amazon_mturk_access_key');
            if (!isset($this->AWSAccessKeyId))
                throw new Exception("Access Key ID missing.");

            // Secret Access Key

            $this->AWSSecretAccessKeyId = $this->_ci->config->item('amazon_mturk_secret_key');
            if (!isset($this->AWSSecretAccessKeyId))
                throw new Exception("Secret Access Key missing.");

            // sandbox
            if (isset($options["sandbox"]))
            {
                if (!is_bool($options["sandbox"]))
                    throw new Exception("sandbox option must be TRUE or FALSE if present");
                $sandbox = $options["sandbox"];
            }
            else
                $sandbox = TRUE;

            // trace
            if (isset($options["trace"]))
            {
                if (!is_bool($options["trace"]))
                    throw new Exception("trace option must be TRUE or FALSE if present");
                $trace = $options["trace"];
            }
            else
                $trace = FALSE;

            // instantiate SOAP client
            $this->client = new SoapClient(
             (file_exists($file = dirname(__FILE__) . "/" . pathinfo(self::WSDL, PATHINFO_BASENAME))) ? $file : self::WSDL,
             array("trace" => $trace)
            );

            // set endpoint
            $this->client->__setLocation((($sandbox) ? self::SANDBOX : self::PRODUCTION) . "?Service=" . self::SERVICE);
        }

        /**
         * Invokes AWSMechanicalTurkRequester operations (and local SoapClient
         * methods, per http://php.net/manual/en/class.soapclient.php).
         *
         * @param name       operation (or local method) to invoke
         * @param arguments  operation's (or local method's) arguments
         *
         * @return method's response
         */
        public function __call($name, $arguments)
        {
            // invoke local method
            if (preg_match("/^__/", $name) && method_exists($this->client, $name))
                return call_user_func_array(array($this->client, $name), $arguments);

            // invoke SOAP method 
            else
            {
                $Timestamp = date("c");
                try
                {
                    return $this->client->$name(array(
                     "AWSAccessKeyId" => $this->AWSAccessKeyId,
                     "Request" => (isset($arguments[0])) ? $arguments[0] : null,
                     "Signature" => base64_encode($this->hmac_sha1($this->AWSSecretAccessKeyId, self::SERVICE . $name . $Timestamp)),
                     "Timestamp" => $Timestamp
                    ));
                }
                catch (SoapFault $e)
                {
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }
        }

        /**
         * Calculates SHA1 HMAC.
         *
         * Adapted from 
         * http://docs.amazonwebservices.com/AWSMechanicalTurkGettingStartedGuide/2006-10-31/MakingARequest.html.
         *
         * @param key  secret access key
         * @param $s   string to hash
         *
         * @return SHA1 HMAC
         */
        private function hmac_sha1($key, $s)
        {
            return pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . 
             pack("H*", sha1((str_pad($key, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $s))));
        }
    }

?>
