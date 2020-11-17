<?php

declare(strict_types=1);

namespace Checkoutcom;

use Exception;
use Checkoutcom\Handler\CheckoutcomCard;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class Checkoutcom
 */
class Checkoutcom extends Plugin
{
    /**
     * Build
     *
     * @param mixed $container 
     * 
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Load dependency injection
        $this->container = $container;

        // Load services
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . 'Resources/config')
        );

        try {
            $loader->load('services.xml');
        } catch (Exception $e) {
            // @todo handle exceptions
        }
    }
    
    /**
     * Install CKO Plugin
     *
     * @param mixed $context 
     * 
     * @return void
     */
    public function install(InstallContext $context): void
    {
        // parent::install($context);
        $this->_addPaymentMethod($context->getContext());
    }
    
    /**
     * Function _addPaymentMethod
     *
     * @param mixed $context 
     * 
     * @return void
     */
    private function _addPaymentMethod(Context $context): void
    {
        $paymentMethodExists = $this->_getPaymentMethodId();

        // Payment method exists already, no need to continue here
        if ($paymentMethodExists) {
            return;
        }

        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(
            get_class($this),
            $context
        );

        $ckoPaymentData = [
            // payment handler will be selected by the identifier
            'handlerIdentifier' => CheckoutcomCard::class,
            'name' => 'Checkout.com Payment Method',
            'description' => 'Pay with Checkout.com',
            'pluginId' => $pluginId,
            'customFields' => [
                'cko_payment_method_name' => 'ckocreditcard'
            ]
        ];

        $paymentRepository = $this->container->get('payment_method.repository');
        $paymentRepository->create([$ckoPaymentData], $context);
    }

        
    /**
     * Function _getPaymentMethodId
     *
     * @return string
     */
    public function _getPaymentMethodId(): ?string
    {
        $paymentRepository = $this->container->get('payment_method.repository');

        // Fetch ID for update
        $paymentCriteria = (new Criteria())->addFilter(
            new EqualsFilter('handlerIdentifier', CheckoutcomCard::class)
        );

        $paymentIds = $paymentRepository->searchIds(
            $paymentCriteria,
            Context::createDefaultContext()
        );

        if ($paymentIds->getTotal() === 0) {
            return null;
        }

        return $paymentIds->getIds()[0];
    }
    
    /**
     * Update CKO Plugin
     *
     * @param mixed $context 
     * 
     * @return void
     */
    public function update(UpdateContext $context): void
    {
        parent::update($context);
    }
    
    /**
     * Activate CKO Plugin
     *
     * @param mixed $context 
     * 
     * @return void
     */
    public function activate(ActivateContext $context): void
    {
        parent::activate($context);
    }
    
    /**
     * Deactivate CKO Plugin
     *
     * @param mixed $context 
     * 
     * @return void
     */
    public function deactivate(DeactivateContext $context): void
    {
        parent::deactivate($context);
    }
    
    /**
     * Uninstall CKO Plugin
     *
     * @param mixed $context 
     * 
     * @return void
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
    }
}