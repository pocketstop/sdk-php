<?php
    /*
    Copyright (c) 2012 Pocketstop, LLC.

    Permission is hereby granted, free of charge, to any person
    obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without
    restriction, including without limitation the rights to use,
    copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following
    conditions:

    The above copyright notice and this permission notice shall be
    included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
    HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
    OTHER DEALINGS IN THE SOFTWARE.
    */    
    
    // VERSION: 1.0.0
    
    // Pocketstop REST Helpers
    // ========================================================================
    
    // ensure Curl is installed
    if(!extension_loaded("curl"))
        throw(new Exception(
            "Curl extension is required for PocketstopRestClient to work"));
    
    /* 
     * PocketstopRestResponse holds all the REST response data 
     * Before using the reponse, check IsError to see if an exception 
     * occurred with the data sent to Pocketstop
     * ResponseXml will contain a SimpleXml object with the response xml
     * ResponseText contains the raw string response
     * Url and QueryString are from the request
     * HttpStatus is the response code of the request
     */
    class PocketstopRestResponse {
        
        public $ResponseText;
        public $ResponseJson;
        public $HttpStatus;
        public $Url;
        public $QueryString;
        public $IsError;
        public $ErrorMessage;
        
        public function __construct($url, $text, $status) {
            preg_match('/([^?]+)\??(.*)/', $url, $matches);
            $this->Url = $matches[1];
            $this->QueryString = $matches[2];
            $this->ResponseText = str_replace('ï»¿','',$text);
            $this->HttpStatus = $status;
            if($this->HttpStatus != 204)
                $this->ResponseJson = json_decode(str_replace('ï»¿','',$text));
            
            if($this->IsError = ($status >= 400))
                $this->ErrorMessage =
                    (string)$this->ResponseJson->Message;
            
        }
        
    }
    
    /* PocketstopRestClient throws PocketstopException on error 
     * Useful to catch this exception separately from general PHP
     * exceptions, if you want
     */
    class PocketstopException extends Exception {}
    
    /*
     * PocketstopRestBaseClient: the core Rest client, talks to the Pocketstop REST             
     * API. Returns a PocketstopRestResponse object for all responses if Pocketstop's 
     * API was reachable Throws a PocketstopException if Pocketstop's REST API was
     * unreachable
     */
     
    class PocketstopRestClient {

        protected $Endpoint;
        protected $AccountId;
        protected $ApiKey;
        
        /*
         * __construct 
         *   $username : Your AccountId (MerchantID)
         *   $password : Your account's ApiKey
         *   $endpoint : The Pocketstop REST Service URL, currently defaults to
         * the proper URL
         */
        public function __construct($accountId, $apiKey, $endpoint = "https://api.pocketstop.com/v1") {
            
            $this->AccountId = $accountId;
            $this->ApiKey = $apiKey;
            $this->Endpoint = $endpoint;
        }
        
        /*
         * sendRequst
         *   Sends a REST Request to the Pocketstop REST API
         *   $path : the URL (relative to the endpoint URL, after the /v1)
         *   $method : the HTTP method to use, defaults to GET
         *   $vars : for POST or PUT, a key/value associative array of data to
         * send, for GET will be appended to the URL as query params
         */
        public function request($path, $method = "GET", $vars = array(), $headers = array( 'Accept' => 'application/json', 'Content-Type' => 'application/json' ) ) {

            $encoded = "";
            /*foreach($vars AS $key=>$value)
                $encoded .= "$key=".urlencode($value)."&";*/
            //$encoded = substr($encoded, 0, -1);
            $encoded = json_encode($vars);
            $tmpfile = "";
            $fp = null;
            
            // construct full url
            $url = "{$this->Endpoint}/$path";
            
            // if GET and vars, append them
            if($method == "GET") 
                $url .= (FALSE === strpos($path, '?')?"?":"&").$encoded;

            // initialize a new curl object   
            $curl = curl_init($url);
            
            $opts = array();
            foreach ($headers as $k => $v) $opts[CURLOPT_HTTPHEADER][] = "$k: $v";
            curl_setopt_array($curl, $opts);
            
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            switch(strtoupper($method)) {
                case "GET":
                    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
                    break;
                case "POST":
                    curl_setopt($curl, CURLOPT_POST, TRUE);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
                    break;
                case "PUT":
                    // curl_setopt($curl, CURLOPT_PUT, TRUE);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    file_put_contents($tmpfile = tempnam("/tmp", "put_"),
                        $encoded);
                    curl_setopt($curl, CURLOPT_INFILE, $fp = fopen($tmpfile,
                        'r'));
                    curl_setopt($curl, CURLOPT_INFILESIZE, 
                        filesize($tmpfile));
                    break;
                case "DELETE":
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                    break;
                default:
                    throw(new PocketstopException("Unknown method $method"));
                    break;
            }
            
            // send credentials
            curl_setopt($curl, CURLOPT_USERPWD,
                $pwd = "{$this->AccountId}:{$this->ApiKey}");
            
            // do the request. If FALSE, then an exception occurred    
            if(FALSE === ($result = curl_exec($curl)))
                throw(new PocketstopException(
                    "Curl failed with error " . curl_error($curl)));
            
            // get result code
            $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // unlink tmpfiles
            if($fp)
                fclose($fp);
            if(strlen($tmpfile))
                unlink($tmpfile);
                
            return new PocketstopRestResponse($url, $result, $responseCode);
        }
    }
    
    // Pocketstop Utility function and Request Validation
    // ========================================================================
    
    class PocketstopUtils {
        
        protected $AccountId;
        protected $ApiKey;
        
        function __construct($id, $token){
            $this->ApiKey = $token;
            $this->AccountId = $id;
        }
    
        public function validateRequest($expected_signature, $url, $data = array()) {
           
           // sort the array by keys
           ksort($data);
           
           // append them to the data string in order 
           // with no delimiters
           foreach($data AS $key=>$value)
                   $url .= "$key$value";

           // This function calculates the HMAC hash of the data with the key 
           // passed in
           // Note: hash_hmac requires PHP 5 >= 5.1.2 or PECL hash:1.1-1.5
           // Or http://pear.php.net/package/Crypt_HMAC/
           $calculated_signature = base64_encode(hash_hmac("sha1",$url, $this->ApiKey, true));
           
           return $calculated_signature == $expected_signature;
           
        }
        
    }        

?>