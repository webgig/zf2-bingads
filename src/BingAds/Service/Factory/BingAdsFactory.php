<?php

namespace BingAds\Service\Factory;

use JimmyBase\Provider\Identity\ZfcUserZendDb;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class BingAdsFactory implements FactoryInterface
{


    public function createService(ServiceLocatorInterface $serviceLocator)
    {

        $config          = $serviceLocator->get('Config');
        $bing_api_config = $config['bing-api-config'];

        $bing_api_config['redirect_uri'] = "http://jimmy.com/authcallback";
        $config = array(
                    'user_agent'      => $bing_api_config['user_agent'],
                    'client_id'       => $bing_api_config['client_id'],
                    'client_secret'   => $bing_api_config['client_secret'],
                    'redirect_uri'    => $bing_api_config['redirect_uri'],
                    'developer_token' => $bing_api_config['developer_token']
         );
        //var_dump($config);exit;
        $bing_api_service = new \BingAds\Service\BingAds($config);
        return $bing_api_service;
    }
}

