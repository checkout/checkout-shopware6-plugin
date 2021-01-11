<?php

namespace Checkoutcom\Helper;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use RuntimeException;
use Checkoutcom\Helper\Url;
use PackageVersions\Versions;

class Utilities
{
    public function __construct(
        SystemConfigService $systemConfigService
    ) {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Create post request
     */
    public function postRequest( $method, $url, $header, $body = false)
    {
        $request =  new Request( $method, $url, $header, $body );
        $restClient = new Client();
        
        try {
            $response = $restClient->send($request);
            $body = json_decode($response->getBody()->getContents(), true);

            return $body;

        } catch (Exception $e) {
            throw new RuntimeException('An error has occured ' . $e->getMessage());
        }
    }

    /**
     * Get redirection urls success-fail
     */
    public function getRedirectionUrl($server)
    {
        // checking $protocol in HTTP or HTTPS
        if (isset($server['HTTPS']) && $server['HTTPS'] != 'off') {
            $protocol  = "https";
        } else {
            $protocol  = "http";
        }
        $shopDomain = $server['HTTP_HOST'];

        $successRedirectionUrl = $protocol . '://'. $shopDomain . "/cko/successRedirection";
        $failureRedirectionUrl = $protocol . '://'. $shopDomain . "/cko/failRedirection";
        $shopUrl = $protocol . '://'. $shopDomain;

        $arr = [
            'success' => $successRedirectionUrl,
            'fail' => $failureRedirectionUrl,
            'shopUrl' => $shopUrl
        ];

        return $arr;
    }

    /**
     * Get version of platform and plugin
     */
    public function getVersions() {
        
        $pluginRootPath = dirname(__DIR__, 2);
        $pluginVersion = '';

        if (file_exists($pluginRootPath.'/composer.json')) {
                $composerJson = json_decode( file_get_contents($pluginRootPath . '/composer.json'), true);
                $pluginVersion = $composerJson['version'];
        }
        
       return  $version = [
            'shopwareVersion' => Versions::getVersion('shopware/core'),
            'pluginVersion' => $pluginVersion
        ];
    }

    /**
     * Generate UUID
     * https://www.php.net/manual/en/function.uniqid.php
     */
    public function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * Validate status code
     */
    public function isValidResponse($statusCode)
    {
        $isValid = false;

        if ($statusCode >= 200 && $statusCode < 300) {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Convert the decimal amount to inte or vice versa
     */
    public function fixAmount($amount, $currency = '', $reverse = false)
    {
        $multiplier = 100;
        $full = array('BYN', 'BIF', 'DJF', 'GNF', 'ISK', 'KMF', 'XAF', 'CLF', 'XPF', 'JPY', 'PYG', 'RWF', 'KRW', 'VUV', 'VND', 'XOF');
        $thousands = array('BHD', 'LYD', 'JOD', 'KWD', 'OMR', 'TND');

        if (in_array($currency, $thousands)) {
            $multiplier = 1000;
        } elseif (in_array($currency, $full)) {
            $multiplier = 1;
        }

        if ($reverse) {
            $price = round(($amount / $multiplier), 2);
        } else {
            $price = (int) ('' . ($amount * $multiplier));
        }

        return $price;
    }

    /**
     * build log body
     */
    public function contructLogBody($error, $scope, $type, $id = false) {

        $logBody['error'] = $error->getMessage();
        $message = [
            "scope" => $scope,
            "message" => json_encode($logBody),
            "id" => $id,
            "type" => $type
        ];

        return $message;

    }
}