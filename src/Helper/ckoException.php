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
     *  build the body to be logged on cloudEvent
     */
    public function getLogBody() {
        $body = Utilities::contructLogBody(
            self::$exceptionMessage,
            self::$exceptionScope,
            self::$exceptionType,
            self::$exceptionId,
            self::$logLevel
        );

        return $body;
    }

    /**
     *  get the log level
     */
    // public function getLogLevel() {
    //     return self::$logLevel;
    // }

}