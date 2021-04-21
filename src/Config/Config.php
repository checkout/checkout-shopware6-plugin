<?php 

declare(strict_types=1);

namespace Checkoutcom\Config;

use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Config
 */
class Config
{
    public const SYSTEM_CONFIG_DOMAIN = 'Checkoutcom.config.';

    protected static $systemConfigService;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        self::$systemConfigService = $systemConfigService;
    }
    
    /**
     *  Cko publicKey
     *
     * @return string
     */
    public static function publicKey() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'publicKey'
        );
    }
    
    /**
     * secretKey
     *
     * @return string
     */
    public static function secretKey() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'secretKey'
        );
    }
    
    /**
     * ckoUrl
     *
     * @return string
     */
    public static function ckoUrl() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'ckoUrl'
        );
    }
    
    /**
     * cloudPluginUrl
     *
     * @return string
     */
    public static function cloudPluginUrl() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'cloudPluginUrl'
        );
    }

    public static function enableGpay() : bool
    {
        return (bool) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'enableGpay'
        );
    }

    public static function gpayMerchantId() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'gpayMerchantId'
        );
    }

    public static function gpayButtonStyle() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'gpayButtonStyle'
        );
    }
  
    /**
     * logcloudEvent
     *
     * @return string
     */
    public static function logcloudEvent() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'cloudEventLog'
        );
    }
}