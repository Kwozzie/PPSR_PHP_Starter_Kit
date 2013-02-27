<?php
/**
 * A basic class for getting PHP PPSR newbies connected and running with Australia Government PPSR
 * Tested 27/2/2013 with the Discovery environment
 * Please check that you have the latest schemas and DTD's from the PPSR.gov.au website, the ones included with this project may not be correct.
 *
 * @author Justin Swan 2012
 */

// Fill in your own details here.
define("ENVIRONMENT", 'Discovery');
define("WSDL", dirname(__FILE__) . "/schemas.ppsr.gov.au.2011.04.services.wsdl");
define("B2G_USERNAME","");
define("B2G_PASSWORD","");
define("SPGAC","");
define("SPGN","");


class WsseAuthHeader extends SoapHeader {

    private $wss_ns = "";

    public function __construct($b2g_username, $b2g_password, $ns = null) {
        if ($ns) {
            $this->wss_ns = $ns;
        }

        $auth = new stdClass();
        $auth->Username = new SoapVar($b2g_username, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);
        $auth->Password = new SoapVar($b2g_password, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);

        $username_token = new stdClass();
        $username_token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns);

        $security_sv = new SoapVar(
                        new SoapVar($username_token, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns),
                        SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'Security', $this->wss_ns);

        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
    }

}

class ppsrSoapClient extends SoapClient {

    private $wsdl;
    private $wss_ns;
    private $ppsr_ns;
    private $params;
    private $b2g_username;
    private $b2g_password;
    public $environment;
    public $errors = array();
    public $last_error;
    public $notices = array(); // for email and echoing.

    public function __construct() {

        $this->wsdl = WSDL;
        $this->wss_ns = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";
        $this->ppsr_ns = "http://schemas.ppsr.gov.au/2011/04/services";
        $this->environment = ENVIRONMENT;
        $this->b2g_username = B2G_USERNAME;
        $this->b2g_password = B2G_PASSWORD;
        $this->params = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );

        //echo $this->environment;
        parent::__construct($this->wsdl, $this->params);

        // save us manually calling this each time.
        $this->setSoapHeaders();
    }

    private function setSoapHeaders() {
        $headers = array();
        $headers[1] = new WsseAuthHeader($this->b2g_username,$this->b2g_password,$this->wss_ns);
        $headers[2] = new SoapHeader($this->ppsr_ns, 'TargetEnvironment', $this->environment);
        $this->__setSoapHeaders($headers);
    }

    /**
     * Collect errors for displaying later.
     * @param string $error - the error message to add to the errors array;
     */
    public function setError($error) {
        $this->last_error = $error;
        array_push($this->errors, $error);
    }

    /**
     * Handy dandy debugging function
     * @param exception $e
     */
    public function show_debug($e = null) {

        if (!empty($e))
            echo "<h1>Exception</h1>" . $e->getMessage();

        echo "<h1>Debugging Info</h1>";
        echo "<p>REQUEST HEADERS:" . "\n" . "<br><textarea rows=10 cols=50>" . $this->__getLastRequestHeaders() . "</textarea>" . "\n" . "</p>";
        echo "<p>REQUEST:" . "\n" . "<br><textarea rows=10 cols=50>" . $this->__getLastRequest() . "</textarea>" . "\n" . "</p>";

        echo "<p>RESPONSE HEADERS:" . "\n" . "<br><textarea rows=10 cols=50>" . $this->__getLastResponseHeaders() . "</textarea>" . "\n" . "</p>";
        echo "<p>RESPONSE:\n<br><textarea rows=10 cols=50>" . $this->__getLastResponse() . "</textarea>\n</p>";
    }

    /**
     * Build a random number
     * @param int $e length of random number
     * @return random integar of $e length;
     */
    function RandNumber($e) {
        $rand = 0;
        for ($i = 0; $i < $e; $i++)
            $rand = $rand . rand(0, 9);
        return $rand;
    }

    /**
     * Create a ping request - first call that should be made to ensure connection to PPSR
     * @return boolean
     */
    public function ppsrPing() {
        try {
            // build params
            $params = new stdClass();
            $params->PingRequest = new stdClass();
            $params->PingRequest->CustomersRequestMessageId = date("Ymdhis") . __LINE__ . $this->RandNumber(6);

            $result = $this->Ping($params);
            $response = isset($result->PingResponse->CustomersRequestMessageId) ? $result->PingResponse->CustomersRequestMessageId : false;

            if ($response && $response == $params->PingRequest->CustomersRequestMessageId) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->setError("No connection: " . $e->getMessage());
            return false;
        }
    }

}