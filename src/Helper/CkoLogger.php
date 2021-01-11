<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;
use Checkoutcom\Helper\Utilities;
use Psr\Log\LoggerInterface;
use Monolog\Formatter\JsonFormatter;
use Checkoutcom\Handler\DatadogHandler;


/**
 * This class is used to log on clouEvent and shopware 6 without
 * throwing an exception
 * 
 */
class CkoLogger {

    /**
     * @var LoggerInterface
     */
    public static $logger;

    function __construct(LoggerInterface $logger) {
        self::$logger = $logger;
    }
    
    /**
     *  log to cloudEvent and shopware 6
     */
    public function log($message, $scope, $type, $id = false, $logLevel) {

        $body = Utilities::contructLogBody($message, $scope, $type, $id, $logLevel);
        
        $url = Url::getCloudEventUrl();
        $formatter = new JsonFormatter();
        
        // create instance of the datadog handler to log on cloudEvent
        $datadogLogs = new DatadogHandler($url, $logLevel, true);
        $datadogLogs->setFormatter($formatter);
        self::$logger->pushHandler($datadogLogs);
    
        self::$logger->$logLevel(
            json_encode($body)
            );
    }

}