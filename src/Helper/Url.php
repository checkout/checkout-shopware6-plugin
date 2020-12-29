<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;

class Url {
    
    /**
     * url
     */
    public const CKO_IFRAME_URL = "https://cdn.checkout.com/js/framesv2.min.js";
    public const CLOUDEVENT_QA_URL = "https://cloudevents.cko-qa.ckotech.co/logging";
    public const CLOUDEVENT_LIVE_URL = "https://cloudevents.cko-prod.ckotech.co/logging";

    /**
     * cloud plugin create payment url
     */ 
    public function createPaymentUrl(): string {
        return  self::checkUrlSlash(config::cloudPluginUrl())."payments";
    }

    /**
     * cko verify payment url
     */
    public function checkPaymentUrl(String $paymentId): string {
        return self::checkUrlSlash(config::ckoUrl()). 'payments/'. $paymentId;
    }

    /**
     * cko void payment url
     */
    public function voidPaymentUrl($param, string $key) {
        $isLive = self::isLive($key);
        
        if($param['payment_method'] === "Klarna") {
            $url = $isLive ? self::checkUrlSlash(config::ckoUrl()). 'klarna/'. 'orders/'. $param['payment_id']. '/voids' : self::checkUrlSlash(config::ckoUrl()). 'klarna-external/'. 'orders/'. $param['payment_id']. '/voids' ;
        } else {
            $url = self::checkUrlSlash(config::ckoUrl()). 'payments/'. $param['payment_id']. '/voids';
        }

        return $url;
    }

    /**
     * cko refund payment url
     */
    public function refundPaymentUrl(String $paymentId) {
        return self::checkUrlSlash(config::ckoUrl()). 'payments/'. $paymentId . '/refunds';
    }

    /**
     * cko capture payment url
     */
    public function capturePaymentUrl($param, String $key) {
        $isLive = self::isLive($key);
        
        if($param['payment_method'] === "Klarna") {
            $url = $isLive ? self::checkUrlSlash(config::ckoUrl()). 'klarna/'. 'orders/'. $param['payment_id']. '/captures' : self::checkUrlSlash(config::ckoUrl()). 'klarna-external/'. 'orders/'. $param['payment_id']. '/captures' ;
        } else {
            $url = self::checkUrlSlash(config::ckoUrl()). 'payments/'. $param['payment_id']. '/captures';
        }

        return $url;
    }

    /**
     * cloud plugin create context url
     */
    public function getCloudContextUrl() {
        return self::checkUrlSlash(config::cloudPluginUrl())."context";
    }

    /**
     * cloud plugin delete card url
     */
    public function getDeleteInstrumentUrl(String $customerId, String $cardId) {
        return self::checkUrlSlash(config::cloudPluginUrl()) . 'customer'. '/'. $customerId. '/payment-instruments'. '/'. $cardId;
    }

    /**
     * cloud plugin retrieve instrument url
     */
    public function getRetrieveInstrumentUrl(string $customerId) {
        return self::checkUrlSlash(config::cloudPluginUrl()).'customer/' . $customerId;
    }

    /**
     * cloudEvent logging url
     */
    public function getCloudEventUrl(String $publicKey) {
        $isLive = self::isLive($publicKey);
        $url = $isLive ? self::CLOUDEVENT_LIVE_URL : self::CLOUDEVENT_QA_URL;

        return $url;
    }

    /**
     * check environment
     */
    public static function isLive(String $key): bool {
        return (strpos($key, "test") !== false) ?  false : true;
    }
    
    /**
     * check slash in the configured url 
     */
    public static function checkUrlSlash($url) {
        return rtrim($url,"/").'/';
    }
}