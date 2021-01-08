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
        $logBody = [];
    
        $logBody['specversion'] = self::SPECVERSION;
        $logBody['id'] = $record['id'];
        $logBody['type'] = $record['type'];
        $logBody['source'] = self::SOURCE;
        $logBody['data']['scope'] = $record['scope'];
        $logBody['data']['message'] = $record['message'];
        $logBody['cko']['correlationId'] = $record['id'];
        $logBody['cko']['loglevel'] = $this->level;
        
        $header =  [
            'Content-Type' => 'application/cloudevents+json',
        ];
    
        $url = Url::getCloudEventUrl();

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