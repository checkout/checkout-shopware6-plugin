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
}
