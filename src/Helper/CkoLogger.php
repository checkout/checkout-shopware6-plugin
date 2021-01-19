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

class CkoLogger {

    protected $config;
    public static $logger;

    public function __construct(Config $config, LoggerInterface $logger) {
        self::$logger = $logger;
        $this->config = $config;

        if ($this->config::logcloudEvent()) {
            self::$logger->pushHandler(new CloudEventsHandler(true));
        }
    }

    public static function log() {
        return self::$logger;
    }
}