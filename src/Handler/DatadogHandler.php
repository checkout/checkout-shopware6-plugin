<?php

namespace Checkoutcom\Handler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Koriym\HttpConstants\Method;
use Koriym\HttpConstants\RequestHeader;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Config\Config;

class DatadogHandler extends AbstractProcessingHandler {

    public const SOURCE = '/shopware6';
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
        $this->logLevel = $level;

        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        
        $obj = json_decode($record["message"]);

        $logBody = [];
    
        $logBody['specversion'] = self::SPECVERSION;
        $logBody['id'] = $obj->id;
        $logBody['type'] =$obj->type;
        $logBody['source'] = self::SOURCE;
        $logBody['data']['scope'] = $obj->scope;
        $logBody['data']['message'] = $obj->message;
        $logBody['cko']['correlationId'] = $obj->id;
        $logBody['cko']['loglevel'] = "error";
        
        $header =  [
            'Content-Type' => 'application/cloudevents+json',
        ];

        if (config::logcloudEvent() == true) {
            $loggingRequest = Utilities::postRequest(
                'POST',
                $this->endpoint,
                $header,
                json_encode($logBody)
            );
        }

    }


    
}