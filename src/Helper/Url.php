<?php

namespace Checkoutcom\Helper;

use Checkoutcom\Config\Config;

/**
 * Url
 */
class Url {
    
    /**
     * url
     */
    public const CKO_IFRAME_URL = "https://cdn.checkout.com/js/framesv2.min.js";
    public const CLOUDEVENT_LIVE_URL = "https://cloudevents.integration.checkout.com/logging";
    public const CKOURL_SBOX = "https://api.sandbox.checkout.com/";
    public const CKOURL_PROD = "https://api.checkout.com/";
    public const CLOUDPLUGINURL_SBOX = "https://shopware6.integration.sandbox.checkout.com/";
    public const CLOUDPLUGINURL_PROD = "https://shopware6.integration.checkout.com/";
    
    /**
     * cloudPluginUrl
     *
     */
    private static function cloudPluginUrl(): string {
        $url = self::isLive(config::secretKey()) === true ? self::CLOUDPLUGINURL_PROD : self::CLOUDPLUGINURL_SBOX;

        return $url;
    }
    
    /**
     * ckoUrl
     *
     */
    private static function ckoUrl(): string {
        $url = self::isLive(config::secretKey()) === true ? self::CKOURL_PROD : self::CKOURL_SBOX;

        return $url;
    }

    /**
     * cloud plugin create payment url
     */ 
    public static function createPaymentUrl(): string {
        return  self::cloudPluginUrl()."payments";
    }

    /**
     * cko verify payment url
     */
    public static function checkPaymentUrl(String $paymentId): string {
        return self::ckoUrl(). 'payments/'. $paymentId;
    }

    /**
     * cko void payment url
     */
    public static function voidPaymentUrl($param, string $key) {
        $isLive = self::isLive($key);
        
        if($param['payment_method'] === "klarna") {
            $url = $isLive ? self::checkUrlSlash(self::ckoUrl()). 'klarna/'. 'orders/'. $param['payment_id']. '/voids' : self::checkUrlSlash(self::ckoUrl()). 'klarna-external/'. 'orders/'. $param['payment_id']. '/voids' ;
        } else {
            $url = self::checkUrlSlash(self::ckoUrl()). 'payments/'. $param['payment_id']. '/voids';
        }

        return $url;
    }

    /**
     * cko refund payment url
     */
    public static function refundPaymentUrl(String $paymentId) {
        return self::ckoUrl(). 'payments/'. $paymentId . '/refunds';
    }

    /**
     * cko capture payment url
     */
    public static function capturePaymentUrl($param, String $key) {
        $isLive = self::isLive($key);
        
        if($param['payment_method'] === "klarna") {
            $url = $isLive ? self::checkUrlSlash(self::ckoUrl()). 'klarna/'. 'orders/'. $param['payment_id']. '/captures' : self::checkUrlSlash(self::ckoUrl()). 'klarna-external/'. 'orders/'. $param['payment_id']. '/captures' ;
        } else {
            $url = self::checkUrlSlash(self::ckoUrl()). 'payments/'. $param['payment_id']. '/captures';
        }

        return $url;
    }

    /**
     * cloud plugin create context url
     */
    public static function getCloudContextUrl() {
        return self::cloudPluginUrl()."contexts";
    }

    /**
     * cloud plugin merchant configuration url
     */
    public static function getCloudMerchantUrl() {
        return self::cloudPluginUrl()."merchant/configuration";
    }

    /**
     * cloud plugin delete card url
     */
    public static function getDeleteInstrumentUrl(String $customerId, String $cardId) {
        return self::cloudPluginUrl().'customers'.'/'.$customerId.'/payment-instruments'.'/'.$cardId;
    }

    /**
     * cloud plugin retrieve instrument url
     */
    public static function getRetrieveInstrumentUrl(string $customerId) {
        return self::cloudPluginUrl().'customers/'.$customerId;
    }

       
    /**
     * getCloudEventUrl
     *
     */
    public static function getCloudEventUrl(): string {
        $url = self::CLOUDEVENT_LIVE_URL;

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
        return rtrim($url, "/").'/';
    }
}
