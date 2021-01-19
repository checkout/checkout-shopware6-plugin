<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;
use Checkoutcom\Helper\Utilities;

class ckoException extends \Exception {

    /**
     * Exception message
     */
    public static $exceptionMessage;

    /**
     * Exception scope
     */
    public static $exceptionScope;

    /**
     * Exception type
     */
    public static $exceptionType;

    /**
     * Exception id
     */
    public static $exceptionId;

    /**
     * Exception log level
     * [DEBUG , INFO , NOTICE , WARNING , ERROR , CRITICAL , ALERT , EMERGENCY]
     */
    public static $logLevel;

    /**
     *  log only to shopware if error occurs while logging in cloudEvent
     */
    public static $logToCloudApi;

    public function __construct($message, $scope, $type, $id = false, $logLevel, $logToCloudApi = true) {
        self::$exceptionMessage = $message;
        self::$exceptionScope = $scope;
        self::$exceptionType = $type;
        self::$exceptionId = $id;
        self::$logLevel = $logLevel;
        self::$logToCloudApi = $logToCloudApi;
    }
}