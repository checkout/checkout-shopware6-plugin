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
     * @param mixed $systemConfigService 
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

    public static function secretKey() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'secretKey'
        );
    }

    public static function ckoUrl() : string
    {
        return (string) self::$systemConfigService->get(
            self::SYSTEM_CONFIG_DOMAIN. 'ckoUrl'
        );
    }

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

}