<?php

namespace Checkoutcom\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;

class CkoExceptionSubscriber implements EventSubscriberInterface {
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
                ['logException']
            ],
        ];
    }

    public function __construct(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public function logException(ExceptionEvent $event)
    {

        $exception = $event->getThrowable();

        if (!$exception instanceof RuntimeException)
            CkoLogger::log()->Error(
                "Unhandled Exception",
                [
                    LogFields::MESSAGE => $exception->getMessage()
                ]
            );
    }
}