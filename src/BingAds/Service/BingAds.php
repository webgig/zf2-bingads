<?php
namespace BingAds\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container as SessionContainer;
use BingAds\Proxy\ClientProxy;


class BingAds  implements ServiceManagerAwareInterface
{

    const APP_NAME = 'Jimmy - Google Analytics Reporting Application';


    const AUTH_URL = "https://login.live.com/oauth20_authorize.srf";

    const ACCESS_TOKEN_EX_URL = "https://login.live.com/oauth20_token.srf";

    protected $scope        = array("bingads.manage");

    protected $proxy        = null;

    protected $service      = null;

    protected $account_id   = null;

    protected $access_token = null;

    public function __construct($config){

        $this->setConfig($config);

    }


    public function setConfig($config){

        $this->config = $config;
    }

    public function getConfig(){

        return $this->config;
    }

    // For oauth2
    public function setAccessToken($accessToken){

         if($accessToken){
            $this->access_token = $accessToken;
        } else {
            return false;
        }

    }

    public function getAccessToken($accessToken){

        return $this->access_token;
    }

    public function setAccountId($account_id){

            $this->account_id = $account_id;
        return $this;
    }

    public function getAccountId($account_id){

        return $this->account_id;
    }


    public function getService($serviceName){

            switch ($serviceName) {
                case 'CustomerManagementService':
                    # code...
                    $wsdl    = "https://clientcenter.api.bingads.microsoft.com/Api/CustomerManagement/v9/CustomerManagementService.svc?singleWsdl";

                    break;

                case 'CampaignManagementService':
                    $wsdl    = "https://clientcenter.api.bingads.microsoft.com/Api/CampaignManagement/v9/CampaignManagement.svc?singleWsdl";

                default:
                    # code...
                    break;
            }

        $access_token = json_decode($this->getAccessToken())->access_token;
        $proxy   = ClientProxy::ConstructWithCredentials($wsdl, null,null, $this->getConfig()['developer_token'], $access_token);

    return $proxy->GetService();
    }

    public function getAuthorizationUrl(){
        $_SESSION['state']  = rand(0,999999999);

        $queryParams = array(
            'client_id'     => $this->getConfig()['client_id'],
            'redirect_uri'  => $this->getConfig()['redirect_uri'],
            'scope'         => implode(',', $this->scope),
            'response_type' => 'code',
            'state'         => $_SESSION['state']
        );

        return self::AUTH_URL . '?' . http_build_query($queryParams);
    }

    public function authenticate($code){

        $queryParams = array(
            'client_id'     => $this->getConfig()['client_id'],
            'client_secret' => $this->getConfig()['client_secret'],
            'redirect_uri'  => $this->getConfig()['redirect_uri'],
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        );
        $ch = curl_init();

        $query = "";

        while(list($key, $val) = each($queryParams))
            $query[] =  $key . '=' . $val;



        $options = array(
            CURLOPT_URL            => self::ACCESS_TOKEN_EX_URL,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => implode("&", $query));

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if(FALSE === $response)
        {
            $curlErr = curl_error($ch);
            $curlErrNum = curl_errno($ch);

            curl_close($ch);
            throw new Exception($curlErr, $curlErrNum);
        }

        curl_close($ch);

        return $response;
    }

     /**
     * Get client_id.
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * Set client_id.
     *
     * @param string $client_id
     * @return ReportsApi
     */
    public function setAccountId($account_id)
    {
        $this->account_id   =  $account_id;

        return $this;
    }
        /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $serviceManager
     * @return GoogleAdwords
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}
