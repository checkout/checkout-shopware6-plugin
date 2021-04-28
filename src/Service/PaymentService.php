<?php

namespace Checkoutcom\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Checkoutcom\Config\Config;
use Exception;
use RuntimeException;
use Checkoutcom\Helper\Url;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;

class PaymentService
{
    public const PAYMENT_SUCCESS = 'SUCCESS';
    public const PAYMENT_ERROR = 'ERROR';
    public const PAYMENT_REDIRECT = 'REDIRECT';
    public const PAYMENT_AUTHORIZED = 'Authorized';
    public const PAYMENT_APPROVED = 'APPROVED';

    private $config;
    public $restClient;

    public function __construct(Config $config)
    {
        $this->restClient = new Client();
        $this->config = $config;
    }

    /**
     * Create payment
     */
    public function create($param, $correlationId)
    {

        $errorMessage = '';
        $response = [];
        
        $url = Url::createPaymentUrl();

        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $this->config::secretKey(),
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'x-correlation-id' => $correlationId
            ], 
            json_encode($param)
        );

        try {

            $paymentResponse = $this->restClient->send($request);
            $paymentResponsebody = json_decode($paymentResponse->getBody()->getContents(), true);

            if (isset($paymentResponsebody['redirect_url'])) {
                $response['state'] = self::PAYMENT_REDIRECT;
                $response['url'] = $paymentResponsebody['redirect_url'];
            } else {
                if (isset($paymentResponsebody['error_type'])) {
                    $response['state'] = self::PAYMENT_ERROR;
                    $response['message'] = self::PAYMENT_ERROR;
                } else {
                    $response['state'] = self::PAYMENT_SUCCESS;
                    $response['message'] = self::PAYMENT_SUCCESS;
                }
            }

            return $response;
            
        } catch (Exception $e) {
            
            CkoLogger::log()->Error(
                "Error creating cko payment",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.create.payment",
                    LogFields::DATA => [ "id" => $correlationId ]
                ]
            );

            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = "Error Processing Payment";

            return  $response;
        }
    }

    /**
     * check payment
     */
    public function checkPayment($id)
    {
        $secretKey = $this->config::secretKey();
        $url = Url::checkPaymentUrl($id);
        $response = [];

        $request =  new Request(
            'GET',
            $url,
            [
                'Authorization' => $secretKey,
                'Content-Type' => 'application/json',
            ]
        );
        
        try {
            $paymentResponse = $this->restClient->send($request);
            $paymentResponsebody = json_decode($paymentResponse->getBody()->getContents(), true);
            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {

                // check card payment
                if ($paymentResponsebody['source']['type'] === 'card' && $paymentResponsebody['approved']) {
                    $response['state'] = self::PAYMENT_APPROVED;
                }
                // check paypal payment
                if ($paymentResponsebody['source']['type'] === 'paypal' && $paymentResponsebody['approved']) {
                    $response['state'] = self::PAYMENT_APPROVED;
                }
                // check sofort payment
                if ($paymentResponsebody['source']['type'] === 'sofort' && $paymentResponsebody['status'] === 'Pending' || $paymentResponsebody['status'] == 'Captured') {
                    $response['state'] = self::PAYMENT_APPROVED;
                }
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occurred";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::log()->Error(
                "Error verifying cko payment",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.payment.verify",
                    LogFields::DATA => [ "id" => $id ]
                ]
            );
        
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function void($param)
    {
        $secretKey = $this->config::secretKey();
        $url = Url::voidPaymentUrl($param, $secretKey);
        $response = [];
    
        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $secretKey,
                'Content-Type' => 'application/json',
            ]
        );
        

        try {
            $paymentResponse = $this->restClient->send($request);

            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }
            
            return $response;
            
        } catch (Exception $e) {
            
            CkoLogger::log()->Error(
                "Error voiding payment",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.void.transaction",
                    LogFields::DATA => [ "id" => $param['payment_id'] ]
                ]
            );
       
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function klarnaVoid($param)
    {
        $publicKey = $this->config::publicKey();
        $url = Url::voidPaymentUrl($param, $publicKey);
        $response = [];
    
        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $publicKey,
                'Content-Type' => 'application/json',
            ]
        );

        try {
            $paymentResponse = $this->restClient->send($request);

            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occurred";
            }
            
            return $response;
            
        } catch (Exception $e) {

            CkoLogger::log()->Error(
                "Error voiding klarna payment",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.klarna.void.transaction",
                    LogFields::DATA => [ "id" => $param['payment_id'] ]
                ]
            );
     
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function capture($param)
    {
        $secretKey = $this->config::secretKey();
        $url = Url::capturePaymentUrl($param, $secretKey);
        $response = [];

        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $secretKey,
                'Content-Type' => 'application/json',
            ],
            json_encode($param)
        );

        try {
            $paymentResponse = $this->restClient->send($request);
            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::log()->Error(
                "Error capturing payment",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.capture.transaction",
                    LogFields::DATA => [ "id" => $param['payment_id'] ]
                ]
            );
       
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function klarnaCapture($param)
    {
        $publicKey = $this->config::publicKey();
        $url = Url::capturePaymentUrl($param, $publicKey);
        $response = [];

        $body = ["Klarna" => array()];
        
        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $publicKey,
                'Content-Type' => 'application/json',
            ],
            json_encode($body, JSON_FORCE_OBJECT)
        );

        try {
            $paymentResponse = $this->restClient->send($request);
            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::log()->Error(
                "Error capturing klarna transaction",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.capture.klarna.transaction",
                    LogFields::DATA => [ "id" => $param['payment_id'] ]
                ]
            );
       
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function refund($param)
    {
        $secretKey = $this->config::secretKey();
        $url = Url::refundPaymentUrl($param['payment_id']);
        $response = [];

        $request =  new Request(
            'POST',
            $url,
            [
                'Authorization' => $secretKey,
                'Content-Type' => 'application/json',
            ],
            json_encode($param)
        );

        try {
            $paymentResponse = $this->restClient->send($request);
            $response['statusCode'] = $paymentResponse->getStatusCode();

            if (Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))) {
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occurred";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::log()->Error(
                "Error refunding transaction",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.refund.transaction",
                    LogFields::DATA => [ "id" => $param['payment_id'] ]
                ]
            );
      
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }
}