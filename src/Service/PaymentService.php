<?php

namespace Checkoutcom\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Checkoutcom\Config\Config;
use Exception;
use RuntimeException;
use Checkoutcom\Helper\Url;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Helper\CkoLogger;

class PaymentService
{
    public const PAYMENT_SUCCESS = 'SUCCESS';
    public const PAYMENT_ERROR = 'ERROR';
    public const PAYMENT_REDIRECT = 'REDIRECT';
    public const PAYMENT_AUTHORIZED = 'Authorized';
    public const PAYMENT_APPROVED = 'APPROVED';

    private $config;

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
                'Authorization' => $this->config::publicKey(),
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'x-correlation-id' => $correlationId
            ], 
            json_encode($param)
        );

        try {

            $paymentResponse = $this->restClient->send($request);
            $paymentResponsebody = json_decode($paymentResponse->getBody()->getContents(), true);

            if ($paymentResponsebody['requiresRedirect'] === true) {
                $response['state'] = self::PAYMENT_REDIRECT;
                $response['url'] = $paymentResponsebody['redirectLink'];
            } else {
                if ($paymentResponsebody['approved'] === true) {
                    $response['state'] = self::PAYMENT_SUCCESS;
                    $response['message'] = $paymentResponsebody['status'];
                } else {
                    $response['state'] = self::PAYMENT_ERROR;
                    $response['message'] = $paymentResponsebody['status'];
                }
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko create payment",
                    "message" =>  $e->getMessage(),
                    "id" => $correlationId,
                    "type" => "checkout.create.payment.error"
                ])
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
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko verify payment",
                    "message" =>  $e->getMessage(),
                    "id" => $id,
                    "type" => "checkout.payment.verify.error"
                ])
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

            if(Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))){
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }
            
            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko void payment",
                    "message" =>  $e->getMessage(),
                    "id" => $param['payment_id'],
                    "type" => "checkout.void.transaction.error"
                ])
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

            if(Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))){
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }
            
            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko klarna void payment",
                    "message" =>  $e->getMessage(),
                    "id" => $param['payment_id'],
                    "type" => "checkout.void.transaction.error"
                ])
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

            if(Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))){
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko capture payment",
                    "message" =>  $e->getMessage(),
                    "id" => $param['payment_id'],
                    "type" => "checkout.capture.transaction.error"
                ])
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
            json_encode($body,JSON_FORCE_OBJECT)
        );

        try {
            $paymentResponse = $this->restClient->send($request);
            $response['statusCode'] = $paymentResponse->getStatusCode();

            if(Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))){
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko klarna capture payment",
                    "message" =>  $e->getMessage(),
                    "id" => $param['payment_id'],
                    "type" => "checkout.capture.transaction.error"
                ])
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

            if(Utilities::isValidResponse(json_decode($paymentResponse->getStatusCode()))){
                $response['state'] = self::PAYMENT_SUCCESS;
            } else {
                $response['state'] = self::PAYMENT_ERROR;
                $response['message'] = "An error has occured";
            }

            return $response;
            
        } catch (Exception $e) {

            CkoLogger::logger()->Error(
                json_encode ([
                    "scope" => "cko refund payment",
                    "message" =>  $e->getMessage(),
                    "id" => $param['payment_id'],
                    "type" => "checkout.refund.transaction.error"
                ])
            );
      
            $response['statusCode'] = 500;
            $response['state'] = self::PAYMENT_ERROR;
            $response['message'] = $e->getMessage();

            return $response;
        }
    }
}