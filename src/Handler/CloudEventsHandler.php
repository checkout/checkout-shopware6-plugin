<?php

namespace Checkoutcom\Handler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Koriym\HttpConstants\Method;
use Koriym\HttpConstants\RequestHeader;
use Monolog\Handler\AbstractProcessingHandler;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Config\Config;
use Checkoutcom\Helper\Url;

class CloudEventsHandler extends AbstractProcessingHandler {
    
    public const SPECVERSION = '1.0';
    
    /**
     *  Datadog Url
     */
    protected $endpoint;

    /**
     *  log level
     */
    protected $level;


    public function __construct($endpoint, $level, $bubble = true) {
        $this->endpoint = $endpoint;

        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $obj = json_decode($record["message"]);

        // $logLevel = $this->logLevelName($this->level);
        $environment = Url::isLive(config::publicKey()) ? "PROD" : "SANDBOX";

        $logBody = [];
    
        $logBody['specversion'] = self::SPECVERSION;
        $logBody['id'] = $obj->id;
        $logBody['type'] =$obj->type;
        $logBody['source'] = '/shopware6'. '/' . $_SERVER['SERVER_NAME'] . '/' . $environment;
        $logBody['data']['scope'] = $obj->scope;
        $logBody['data']['message'] = $obj->message;
        $logBody['cko']['correlationId'] =$obj->id;
        $logBody['cko']['loglevel'] = $this->level;

        $header =  [
            'Content-Type' => 'application/cloudevents+json',
        ];

        try {
            $loggingRequest = Utilities::postRequest(
                'POST',
                $this->endpoint,
                $header,
                json_encode($logBody)
            );
        } catch (\Exception $e) {
            
            throw new ckoException($e->getMessage(), "Datadog handler log", "Datadog.log.error", "Error");
        }
        
    }

    public function logLevelName($logLevel) {

        $errorMapping = array();

        $errorMapping[100] = 'debug';
        $errorMapping[200] = 'info';
        $errorMapping[250] = 'notice';
        $errorMapping[300] = 'warning';
        $errorMapping[400] = 'error';
        $errorMapping[500] = 'critical';
        $errorMapping[550] = 'alert';
        $errorMapping[600] = 'emergency';

        return  $errorMapping[$logLevel];
    }
}