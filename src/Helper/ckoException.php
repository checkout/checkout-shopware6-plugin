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

    public function __construct($message, $scope, $type, $id = false, $logLevel) {
        self::$exceptionMessage = $message;
        self::$exceptionScope = $scope;
        self::$exceptionType = $type;
        self::$exceptionId = $id;
        self::$logLevel = $logLevel;

    }

    /**
     *  get the exception message
     */
    public function getExceptionMessage() {
        
        return self::$exceptionMessage;
    }

     /**
     *  build the body to be logged on cloudEvent and shopware 6
     */
    public function getLogBody() {
        
        $logBody['error'] = self::$exceptionMessage;
        $body = [
            "scope" => self::$exceptionScope,
            "message" => json_encode($logBody),
            "id" => self::$exceptionId,
            "type" => self::$exceptionType,
        ];

        return $body;
    }

    /**
     *  get the log level
     */
    public function getLogLevel() {
        return self::$logLevel;
    }

}