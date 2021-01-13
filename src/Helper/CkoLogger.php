<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;
use Checkoutcom\Helper\Utilities;
use Psr\Log\LoggerInterface;
use Monolog\Formatter\JsonFormatter;
use Checkoutcom\Handler\CloudEventsHandler;


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

        if (config::logcloudEvent() == true) {
            self::$logger->pushHandler(new CloudEventsHandler(
                Url::getCloudEventUrl(), "error", true
            ));
        }
    }
    
    /**
     *  log to cloudEvent and shopware 6
     */
    public function log($message, $scope, $type, $id = false, $logLevel) {

        $body = Utilities::contructLogBody($message, $scope, $type, $id, $logLevel);
    
        self::$logger->$logLevel(
            json_encode($body)
            );
    }

    public static function logger() {

        return self::$logger;
    }

}