<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;
use Checkoutcom\Helper\Utilities;
use Psr\Log\LoggerInterface;

class CkoLogger {

    public const SOURCE = '/shopware6';
    public const SPECVERSION = '1.0';

    /**
     * @var LoggerInterface
     */
    public static $logger;

    function __construct(LoggerInterface $logger) {
        self::$logger = $logger;
    }
    
    /**
     * construct logging body for cloudEvent API
     * post request
     */
    private function postLoggingRequest($data) {
    
        $logBody = [];
    
        $logBody['specversion'] = self::SPECVERSION;
        $logBody['id'] = $data['id'];
        $logBody['type'] = $data['type'];
        $logBody['source'] = self::SOURCE;
        $logBody['data']['scope'] = $data['scope'];
        $logBody['data']['message'] = $data['message'];
        $logBody['cko']['correlationId'] = $data['id'];
        $logBody['cko']['loglevel'] = 'info';
    
        $header =  [
            'Content-Type' => 'application/cloudevents+json',
        ];
    
        $url = Url::getCloudEventUrl(config::publicKey());
        
        $loggingRequest = Utilities::postRequest(
            'POST',
            $url,
            $header,
            json_encode($logBody)
        );
    
    }
    
    /**
     *  function to get message of the event
     */
    public function log($log) {

        // check if cloudEvent logging is enabled
        if (config::logcloudEvent() == true) {
            try {
                self::postLoggingRequest($log);
            } catch (Exception $e) {
                return $e;
            }
        }
    
        // log to shopware 6
        self::$logger->error(
            json_encode($log)
            );
    
    }

}