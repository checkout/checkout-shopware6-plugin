<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use Psr\Log\LoggerInterface;
use Checkoutcom\Handler\CloudEventsHandler;
use Monolog\Logger;

/**
 * CkoLogger
 */
class CkoLogger {

    protected $config;
    public static $logger;
    
    /**
     * __construct
     */
    public function __construct(Config $config, LoggerInterface $logger) {
        self::$logger = $logger;
        $this->config = $config;

        self::$logger = new Logger('cko-logger');
        self::$logger->pushHandler(new CloudEventsHandler(true));
    }

    public static function log() {
        return self::$logger;
    }
}