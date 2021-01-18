<?php

namespace Checkoutcom\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use monolog\Logger;
use Monolog\Formatter\JsonFormatter;
use RuntimeException;
use Checkoutcom\Handler\CloudEventsHandler;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Config\Config;
use Checkoutcom\Helper\CkoLogger;

class CkoExceptionSubscriber implements EventSubscriberInterface {

    protected $config;
    
    /**
     *  @var LoggerInterface
     */
    public static $logger;

    /**
     *  get the substribed events
     */
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return [
            KernelEvents::EXCEPTION => [
                ['logCkoException', 10]
            ],
        ];
    }

    public function __construct(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public function logCkoException(ExceptionEvent $event)
    {
        $exception = $event->getException();

        // check if exception is an instance of ckoException
        if ($exception instanceof ckoException) {

            $logLevel = $exception::$logLevel;

            CkoLogger::logger()->$logLevel(
                json_encode ([
                    "scope" => $exception::$exceptionScope,
                    "message" =>  $exception::$exceptionMessage,
                    "id" => $exception::$exceptionId,
                    "type" => $exception::$exceptionType
                ])
            );

            throw new RuntimeException($exception::$exceptionMessage);
        }
    }
}