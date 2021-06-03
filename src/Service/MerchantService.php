<?php

namespace Checkoutcom\Service;
use Checkoutcom\Config\Config;
use Exception;
use RuntimeException;
use Checkoutcom\Helper\Url;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;

class MerchantService
{

    protected $merchant = [];

    public const FIELD_GPAY_ENABLE = "enableGpay";
    public const FIELD_GPAY_BUTTON_STYLE = "gpayButtonStyle";
    public const FIELD_GPAY_MERCHANT_ID = "gpayMerchantId";

    public function __construct(Config $config)
    {

        $uuid = Utilities::uuid();

    	$header = [
            'Authorization' => $config::secretKey(),
            'x-correlation-id' => $uuid,
            'Content-Type' => 'application/json'
        ];

    	$method = "GET";
        $url = Url::getCloudMerchantUrl();

    	try {

            $merchants = Utilities::postRequest($method, $url, $header);

            if(count($merchants) > 0) {

            	$this->merchant = $merchants[0];

            }

        } catch (\Exception $e) {

            CkoLogger::log()->Error(
                "Error retrieving merchant configuration",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.merchant.configuration"
                ]
            );

            throw new RuntimeException($e->getMessage());
        }

    }

    public function isGPayEnabled()
    {
    	return isset($this->merchant[self::FIELD_GPAY_ENABLE]) ? boolval($this->merchant[self::FIELD_GPAY_ENABLE]) : false;
    }

    public function getGPayButtonStyle()
    {

    	$color = "default";
    	$defaults = ["black", "white", $color];

    	if(isset($this->merchant[self::FIELD_GPAY_BUTTON_STYLE]) && in_array($this->merchant[self::FIELD_GPAY_BUTTON_STYLE], $defaults)) {
    		$color = $this->merchant[self::FIELD_GPAY_BUTTON_STYLE];
    	}

    	return $color;

    }

    public function getGPayMerchantId()
    {
    	return isset($this->merchant[self::FIELD_GPAY_MERCHANT_ID]) ? $this->merchant[self::FIELD_GPAY_MERCHANT_ID] : '';
    }


}
