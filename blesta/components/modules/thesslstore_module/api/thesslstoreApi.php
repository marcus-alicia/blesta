<?php
set_time_limit(500);
/**
 * User: Parag Mehta<parag@paragm.com>
 * Date: 2/23/12
 * Time: 7:42 PM
 * This file is created by www.thesslstore.com for your use. You are free to change the file as per your needs.
 */
include_once "abstractions.php";
include_once "requests.php";
include_once "responses.php";

class thesslstoreApi
{
    public static $API_MODE_LIVE='LIVE';
    public static $API_MODE_TEST='TEST';
    public static $LOG_ALLAPICALLS=false;

    private $_apimode='TEST';
    private $_partnerCode='';
    private $_authToken='';
    private $_token='';
    private $_tokenID='';
    private $_tokenCode='';
    private $_IsUsedForTokenSystem=false;
    private $_userAgent='';

    function __construct($partnerCode,$authToken,$token,$tokenID,$tokenCode,$IsUsedForTokenSystem,$apimode, $userAgent = 'Blesta-1.4.0')
    {
            $this->EnsurePHPVersion();
            $this->_apimode = $apimode;
            $this->_partnerCode = $partnerCode;
            $this->_authToken = $authToken;
            $this->_token = $token;
            $this->_tokenID = $tokenID;
            $this->_tokenCode = $tokenCode;
            $this->_IsUsedForTokenSystem = $IsUsedForTokenSystem;
            $this->_userAgent = $userAgent;
    }

    public function EnsurePHPVersion()
    {
            if(floatval(phpversion())< 5.2)
            {
                    throw new Exception('Not Supported version of PHP. Requires atleast 5.2 or greater version of PHP.');
            }
    }

    private function getAPIRequest()
    {
            $AuthRequest = new apirequest();
            $AuthRequest->AuthToken = $this->_authToken;
            $AuthRequest->PartnerCode = $this->_partnerCode;
            $AuthRequest->UserAgent = $this->_userAgent;
            $AuthRequest->Token = $this->_token;
            $AuthRequest->TokenID = $this->_tokenID;
            $AuthRequest->TokenCode = $this->_tokenCode;
            $AuthRequest->IsUsedForTokenSystem = $this->_IsUsedForTokenSystem;
            $AuthRequest->ReplayToken = uniqid('SSLSTORE-Blesta');
            return $AuthRequest;
    }

    private function cloneObjectFromJson($obj,$jsonobj)
    {
        if($jsonobj!=null && is_object($jsonobj))
        {
            foreach ($jsonobj AS $key => $val) $obj->{$key} = $val;
            return $obj;
        }
        else
            return $jsonobj; //No need to map as it's a scalar value
    }

    private function getCURL($url,$method,$message='')
    {
            $ch = curl_init();
            if (!$ch)
            {
                    die("Couldn't initialize a cURL handle");
            }
            curl_setopt($ch, CURLOPT_URL,$url);
            if($method=='POST')
            {
                    curl_setopt($ch, CURLOPT_POST, 1);
            }
            else
            {
                    curl_setopt($ch,CURLOPT_HTTPGET,1);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if($message!='')
                    curl_setopt($ch, CURLOPT_POSTFIELDS,$message); //Append POST messages
            curl_setopt($ch,CURLOPT_HTTPHEADER,array ("Content-Type: application/json; charset=utf-8"));
            return $ch;
    }

    private function getCURLResponse($curl)
    {
            $returnresp = new curlresponse();
            $returnresp->response = curl_exec($curl);
            if(curl_errno($curl))
                    $returnresp->error = curl_error($curl);
            $returnresp->info = curl_getinfo($curl);
            curl_close($curl); // close cURL handler
            return $returnresp; //Return Result
    }

    private function postToCurl($url,$requestData,$responseData,$HttpMethod='POST')
    {
        $logid = uniqid('api-without-token-'); //for calls without ID
        if(isset($requestData->AuthRequest))
        {
            $requestData->AuthRequest = $this->getAPIRequest();
            $logid = $requestData->AuthRequest->ReplayToken;
        }
        $msg = '';
        /*echo "<pre>";
        print_r($requestData);
        die();*/
        if($requestData!=null)
            $msg = json_encode($requestData); //SET JSON FORMAT if not null
       	$curl = $this->getCURL($url,$HttpMethod, $msg);
       	$response = $this->getCURLResponse($curl);

        if(self::$LOG_ALLAPICALLS)
        {
            $requestfile = $logid . '-request.json';
            $responsefile = $logid . '-response.json';
            file_put_contents($requestfile, $msg);
            file_put_contents($responsefile, $response);
        }
        if($response->error=='')
       	{
       		$respobj = json_decode($response->response);
       		if($responseData!=null) //Indicates if Casting required to a class type
               $result = $this->cloneObjectFromJson($responseData,$respobj);
            else
               $result = $respobj; //Returns raw response if not

           if(isset($result->AuthRequest))
           {
                if($result->AuthResponse->ReplayToken!=$requestData->AuthRequest->ReplayToken)
                {
                       $result = $responseData;
                       die('Something wrong with API, ReplayTokens does not match!');
                }
           }
            return $result;
        }
        else
        {
            $error = array('isError' => true, 'Message' => array($response->error));
            $responseData->isError = $error['isError'];
            $responseData->Message = $error['Message'];
            $responseData->AuthResponse = (object)$error;

            return $responseData;
        }
    }

	public function getURL()
	{
		if(strtoupper($this->_apimode)=='LIVE')
		{
		   return 'https://api.thesslstore.com/rest';
		}
		else
        {
            return 'https://sandbox-wbapi.thesslstore.com/rest';
        }
	}

    /**
     * @param csr_request $csr_request
     * @return csr_response
    */
    public function csr($csr_request)
    {
        $url = $this->getURL() . '/csr/';
        $csrresp = new csr_response();
        return $this->postToCurl($url,$csr_request,$csrresp);
    }

    /**
     * @param ssl_validation_request $ssl_validation_request
     * @return ssl_validation_response
    */
    public function ssl_validation($ssl_validation_request)
    {
        $url = $this->getURL() . '/sslchecker/';
        $resp =  new ssl_validation_response();
        return $this->postToCurl($url,$ssl_validation_request,$resp);
    }

    /**
     * @param whois_request $whois_request
     * @return whois_response
    */
    public function whois($whois_request)
    {
        $url = $this->getURL() . '/whois/';
        $resp =  new whois_response();
        return $this->postToCurl($url,$whois_request,$resp);
    }

    /**
     * @param free_claimfree_request $free_claimfree_request
     * @return free_claimfree_response
    */
    public function free_claimfree($free_claimfree_request)
    {
        $url = $this->getURL() . '/free/claimfree/';
        $resp = new free_claimfree_response();
        return $this->postToCurl($url,$free_claimfree_request,$resp);
    }

    /**
     * @param free_cuinfo_request $free_cuinfo_request
     * @return free_cuinfo_response
    */
    public function free_cuinfo($free_cuinfo_request)
    {
        $url = $this->getURL() . '/free/cuinfo/';
        $resp = new free_cuinfo_response();
        return $this->postToCurl($url,$free_cuinfo_request,$resp);
    }

    /**
         * @return apiresponse
    */
    public function health_status()
  	{
        $url = $this->getURL() . '/health/status/';
        $resp = new apiresponse();
        return $this->postToCurl($url,null,$resp,'GET');
    }

    /**
     * @param health_validate_request $health_validate_request
     * @return health_validate_response
    */
    public function health_validate($health_validate_request)
    {
        $url = $this->getURL() . '/health/validate/';
        $resp = new health_validate_response();
        $apidetails = $this->getAPIRequest();
        $health_validate_request->AuthToken = $apidetails->AuthToken;
        $health_validate_request->PartnerCode = $apidetails->PartnerCode;
        $health_validate_request->UserAgent = $apidetails->UserAgent;
        $health_validate_request->ReplayToken = $apidetails->ReplayToken;

        return $this->postToCurl($url,$health_validate_request,$resp);
    }

    /**
     * @param health_validate_request $health_validate_token_request
     * @return health_validate_token_response
     */
    public function health_validate_token($health_validate_token_request)
    {
        $url = $this->getURL() . '/health/validatetoken/';
        $resp = new health_validate_response();
        $apidetails = $this->getAPIRequest();
        $health_validate_token_request->IsUsedForTokenSystem = true;
        $health_validate_token_request->UserAgent = $apidetails->UserAgent;
        $health_validate_token_request->ReplayToken = $apidetails->ReplayToken;

        return $this->postToCurl($url,$health_validate_token_request,$resp);
    }

    /**
     * @param order_agreement_request $order_agreement_request
     * @return string
    */
    public function order_agreement($order_agreement_request)
    {
        $url = $this->getURL() . '/order/agreement/';
        $resp = new order_agreement_response();
        return $this->postToCurl($url,$order_agreement_request,$resp);
    }

    /**
     * @param order_neworder_request $order_approverlist_request
     * @return order_approverlist_response
    */
    public function order_approverlist($order_approverlist_request)
    {
        $url = $this->getURL() . '/order/approverlist/';
        $resp = new order_approverlist_response();
        return $this->postToCurl($url,$order_approverlist_request,$resp);
    }

    /**
     * @param order_download_request $order_download_request
     * @return order_download_response
    */
    public function order_download($order_download_request)
    {
        $url = $this->getURL() . '/order/download/';
        $resp = new order_download_response();
        return $this->postToCurl($url,$order_download_request,$resp);
    }

    /**
     * @param order_download_request $order_download_request
     * @return order_download_zipresponse
     */
    public function order_download_zip($order_download_request)
    {
        $url = $this->getURL() . '/order/downloadaszip/';
        $resp = new order_download_zip_response();
        return $this->postToCurl($url,$order_download_request,$resp);
    }


    /**
     * @param order_inviteorder_request $order_inviteorder_request
     * @return order_response
    */
    public function order_inviteorder($order_inviteorder_request)
    {
        $url = $this->getURL() . '/order/inviteorder/';
        $resp = new order_response();
        return $this->postToCurl($url,$order_inviteorder_request,$resp);
    }

    /**
     * @param order_neworder_request $order_neworder_request
     * @return order_response
    */
    public function order_neworder($order_neworder_request)
    {
            $url = $this->getURL() . '/order/neworder/';
            $resp = new order_response();
            return $this->postToCurl($url,$order_neworder_request,$resp);
    }

    /**  Should return array(order_response())
     * @param order_query_request $order_query_request
     * @return object
    */
    public function order_query($order_query_request)
    {
            $url = $this->getURL() . '/order/query/';
            $resp =  new order_query_response();
            return $this->postToCurl($url,$order_query_request,$resp);
    }

    /**
     * @param order_vulnerabilityscanrequest_request $order_refundrequest_request
     * @return order_vulnerabilityscanrequest_response
    */
    public function order_certificaterevokerequest($order_certificaterevokerequest_request)
    {
            $url = $this->getURL() . '/order/certificaterevokerequest/';
            $resp =  new apiresponse();
            return $this->postToCurl($url,$order_certificaterevokerequest_request,$resp);
    }

    /**
     * @param order_vulnerabilityscanrequest_request $order_refundrequest_request
     * @return order_vulnerabilityscanrequest_response
    */
    public function order_vulnerabilityscanrequest($order_vulnerabilityscanrequest_request)
    {
            $url = $this->getURL() . '/order/vulnerabilityscanrequest/';
            $resp = new order_vulnerabilityscanrequest_response();
            return $this->postToCurl($url,$order_vulnerabilityscanrequest_request,$resp);
    }

    /**
     * @param order_refundrequest_request $order_refundrequest_request
     * @return order_response
    */
    public function order_refundrequest($order_refundrequest_request)
    {
            $url = $this->getURL() . '/order/refundrequest/';
            $resp = new order_response();
            return $this->postToCurl($url,$order_refundrequest_request,$resp);
    }

    /**
     * @param order_refundstatus_request $order_refundstatus_request
     * @return order_response
    */
    public function order_refundstatus($order_refundstatus_request)
    {
            $url = $this->getURL() . '/order/refundstatus/';
            $resp = new order_response();
            return $this->postToCurl($url,$order_refundstatus_request,$resp);
    }

    /**
     * @param order_reissue_request $order_reissue_request
     * @return order_response
    */
    public function order_reissue($order_reissue_request)
    {
            $url = $this->getURL() . '/order/reissue/';
            $resp = new order_response();
            return $this->postToCurl($url,$order_reissue_request,$resp);
    }

	/**
     * @param order_changeapproveremail_request $order_changeapproveremail_request
     * @return apiresponse
    */
    public function  order_changeapproveremail($order_changeapproveremail_request)
    {
            $url = $this->getURL() . '/order/changeapproveremail/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$order_changeapproveremail_request,$resp);
    }


    /**
     * @param order_resend_request $order_resend_request
     * @return apiresponse
    */
    public function  order_resend($order_resend_request)
    {
            $url = $this->getURL() . '/order/resend/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$order_resend_request,$resp);
    }

    /**
     * @param order_status_request $order_status_request
     * @return order_response
    */
    public function  order_status($order_status_request)
    {
            $url = $this->getURL() . '/order/status/';
            $resp = new order_response();
            return $this->postToCurl($url,$order_status_request,$resp);
    }


    /**
     * @param order_neworder_request $order_validate_request
     * @return apiresponse
    */
    public function  order_validate($order_validate_request)
    {
            $url = $this->getURL() . '/order/validate/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$order_validate_request,$resp);
    }

    /**
     * @param product_query_request $product_query_request
     * @return object
    */
    public function  product_query($product_query_request)
    {
            $url = $this->getURL() . '/product/query/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$product_query_request,$resp);
    }

    /**
     * @param setting_setordercallback_request $setting_setordercallback_request
     * @return apiresponse
    */
    public function  setting_setordercallback($setting_setordercallback_request)
    {
            $url = $this->getURL() . '/setting/setordercallback/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$setting_setordercallback_request,$resp);
    }

    /**
     * @param setting_setpricecallback_request $setting_setpricecallback_request
     * @return apiresponse
    */
    public function  setting_setpricecallback($setting_setpricecallback_request)
    {
            $url = $this->getURL() . '/setting/setpricecallback/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$setting_setpricecallback_request,$resp);
    }

    /**
     * @param setting_settemplate_request $setting_settemplate_request
     * @return apiresponse
    */
    public function setting_settemplate($setting_settemplate_request)
    {
            $url = $this->getURL() . '/setting/settemplate/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$setting_settemplate_request,$resp);
    }

    /**
     * @param setting_cancelnotification_request $setting_cancelnotification_request
     * @return apiresponse
    */
    public function  setting_setcancelnotification($setting_cancelnotification_request)
    {
            $url = $this->getURL() . '/setting/cancelnotification/';
            $resp = new apiresponse();
            return $this->postToCurl($url,$setting_cancelnotification_request,$resp);
    }

    /**
     * @param user_add_request $user_add_request
     * @return user_subuser_response
    */
    public function  user_add($user_add_request)
    {
            $url = $this->getURL() . '/user/add/';
            $resp = new user_subuser_response();
            return $this->postToCurl($url,$user_add_request,$resp);
    }

    /**
     * @param user_activate_request $user_activate_request
     * @return user_subuser_response
    */
    public function  user_activate($user_activate_request)
    {

            $url = $this->getURL() . '/user/activate/';
            $resp = new user_subuser_response();
            return $this->postToCurl($url,$user_activate_request,$resp);
    }

    /**
     * @param user_deactivate_request $user_deactivate_request
     * @return user_subuser_response
    */
    public function user_deactivate($user_deactivate_request)
    {
            $url = $this->getURL() . '/user/deactivate/';
            $resp = new user_subuser_response();
            return $this->postToCurl($url,$user_deactivate_request,$resp);
    }

    /**
     * @param user_query_request $user_query_request
     * @return object
    */
    public function user_query($user_query_request)
    {
            $url = $this->getURL() . '/user/query/';
            $resp = new user_query_response();
            return $this->postToCurl($url,$user_query_request,$resp);
    }

    /**
     * @param user_newuser_request $user_newuser_request
     * @return object
    */
    public function user_newuser($user_newuser_request)
    {
            $url = $this->getURL() . '/user/newuser/';
            $resp = new user_newuser_response();
            return $this->postToCurl($url,$user_newuser_request,$resp);
    }

    /**
     * @param order_replacement_request $order_replacement_request
     * @return object
     */
    public function order_replacement($order_replacement_request)
    {
        $url = $this->getURL() . '/order/getsymantecreplacementorders/';
        $resp = new order_replacement_response();
        return $this->postToCurl($url,$order_replacement_request,$resp);
    }



}

class messagehelper
{
    static function writeinfo($msg)
    {
        echo '<strong>' . $msg . '</strong><br/>';
        echo "\n";
    }

    static function writeerror($msg)
    {
        echo '<strong><span style="color:red">';
        echo '<pre>';
        var_dump($msg);
        echo '</pre>';
        echo '</span></strong><br/>';
        echo "\n";
    }

    static function writevarinfo($msg)
    {
        echo '<pre>';
        var_dump($msg);
        echo "</pre></br>\n";
    }
}


?>