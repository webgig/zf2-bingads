<?php
namespace BingAds\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container as SessionContainer;
use BingAds\Proxy\ClientProxy;


class BingAds  implements ServiceManagerAwareInterface
{

    const APP_NAME            = 'Your App Name';
    const AUTH_URL            = "https://login.live.com/oauth20_authorize.srf";
    const ACCESS_TOKEN_EX_URL = "https://login.live.com/oauth20_token.srf";
    const REFRESH_BUFFER      = 60;

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

        $this->access_token = $accessToken;

       return $this;
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


    public function constructProxy($serviceName){

            switch ($serviceName) {
                case 'CustomerManagementService':
                    # code...
                    $wsdl    = "https://clientcenter.api.bingads.microsoft.com/Api/CustomerManagement/v9/CustomerManagementService.svc?singleWsdl";

                    break;

                case 'CampaignManagementService':
                    $wsdl    = "https://api.bingads.microsoft.com/Api/Advertiser/CampaignManagement/V9/CampaignManagementService.svc?singleWsdl";

                    break;

                case 'ReportingService':
                    $wsdl    = "https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl";
                    break;
                default:
                    # code...
                    break;
            }

        $access_token = $this->getAccessToken()['access_token'];

        $proxy   = ClientProxy::ConstructWithAccountId($wsdl, null,null, $this->getConfig()['developer_token'], $this->getAccountId(), $access_token);


    return $proxy;
    }

    public function getProxy($serviceName){

        return $this->constructProxy($serviceName);
    }

    public function getService($serviceName){

        return $this->constructProxy($serviceName)->GetService();
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

        $response = $this->makeRequest($queryParams);

        if(!$response) return false;

        $response_arr = (array)json_decode($response);

        return array_merge($response_arr, array('timestamp' => time()));
    }

    public function verifyApiAccess(array $credentials){

        if($this->isAccessTokenExpiring($credentials)){
            if( $credentials = $this->getAccessTokenFromRefreshToken($credentials)){
                return array(true,$credentials);
            }
        }

        return array(false,$credentials);
    }

    public function getAccessTokenFromRefreshToken(array $credentials){

        $queryParams = array(
            'client_id'     => $this->getConfig()['client_id'],
            'client_secret' => $this->getConfig()['client_secret'],
            'redirect_uri'  => $this->getConfig()['redirect_uri'],
            'grant_type'    => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
        );

        $response = $this->makeRequest($queryParams);

        if(!$response) return false;

        $response_arr = (array)json_decode($response);

        return array_merge($response_arr, array('timestamp' => time()));
    }

    public function makeRequest(array $queryParams){

        if(!$queryParams)
            return false;

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

    public function isAccessTokenExpiring(array $credentials) {
        $expiry = $this->getExpiryTimestamp($credentials);

        if ($expiry) {
          // Subtract the refresh buffer.
          $expiry -= self::REFRESH_BUFFER;

          // Test if expiry has passed.
          return $expiry < time();
        }

        return FALSE;
      }

      private function getExpiryTimestamp(array $credentials) {
        if (empty($credentials['timestamp'])
            || empty($credentials['expires_in'])) {
          return FALSE;
        }

        // Set to refreshed time.
        $expires = intval($credentials['timestamp']);

        // Add the expiry value.
        $expires += intval($credentials['expires_in']);

        return $expires;
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
