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

    protected $config;
    public static $logger;

    public function __construct(Config $config, LoggerInterface $logger) {
        self::$logger = $logger;
        $this->config = $config;

        if ($this->config::logcloudEvent()) {
            self::$logger->pushHandler(new CloudEventsHandler(
                "info", true
            ));
        }
    }
    
    public function log($message, $scope, $type, $id = false, $logLevel) {

        $body = Utilities::contructLogBody(
            $message,
            $scope,
            $type,
            $id,
            $logLevel
        );
        
        self::$logger->$logLevel(json_encode($body));
    }
}