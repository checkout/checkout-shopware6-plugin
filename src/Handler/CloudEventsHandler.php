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
use RuntimeException;
use Checkoutcom\Helper\CkoLogger;

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


    public function __construct($bubble = true) {

        parent::__construct($bubble);
    }

    protected function write(array $record): void
    {
        $environment = Url::isLive(config::publicKey()) ? "PROD" : "SANDBOX";
        $data = json_decode($record["message"]);
        
        $obj = (object)[];
        $obj->specversion = self::SPECVERSION;
        $obj->id = Utilities::uuid();
        $obj->type = $data->type;
        $obj->source = '/shopware6'. '/' . $_SERVER['SERVER_NAME'] . '/' . $environment;
        $obj->data = $data;
        $obj->cko['correlationId'] = $data->id ?? Utilities::uuid();
        $obj->cko['loglevel'] = self::logLevelName($record['level']);

        $header =  [
            'Content-Type' => 'application/cloudevents+json',
        ];

        try {
            $loggingRequest = Utilities::postRequest(
                'POST',
                Url::getCloudEventUrl(),
                $header,
                json_encode($obj)
            );
        } catch (\Exception $e) {
            
            throw new ckoException($e->getMessage(), "cko cloudEvent", "checkout.cloudEvent.logging.error", Utilities::uuid(), "Error", true);
            throw new RuntimeException('Log to cloud event api failed : ' . $e->getMessage());
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