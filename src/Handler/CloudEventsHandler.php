<?php

namespace Checkoutcom\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Config\Config;
use Checkoutcom\Helper\Url;
use RuntimeException;

/**
 *  custom monolog handler to log to cloudEvent
 */
class CloudEventsHandler extends AbstractProcessingHandler {
    
    public const SPECVERSION = '1.0';
    
    /**
     *  cloudEvent Url
     */
    protected $endpoint;

    /**
     *  log level
     */
    protected $level;

    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct($bubble = true) {
        parent::__construct((int) $bubble);
    }
    
    /**
     * write
     *
     * @return void
     */
    protected function write(array $record): void
    {   
        $environment = Url::isLive(config::publicKey()) ? "PROD" : "SANDBOX";
        $data = $record["context"];
        
        $obj = (object)[];
        $obj->specversion = self::SPECVERSION;
        $obj->id = Utilities::uuid();
        $obj->type = $data["type"] ?? $record["message"];
        $obj->source = '/shopware6'. '/' . $_SERVER['SERVER_NAME'] . '/' . $environment;
        $obj->data = $data["message"];
        $obj->cko['correlationId'] = $data["data"]["id"] ?? Utilities::uuid();
        $obj->cko['loglevel'] = strtolower($record['level_name']);

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

            throw new RuntimeException('Log to cloud event api failed : ' . $e->getMessage());
        }
    }
}