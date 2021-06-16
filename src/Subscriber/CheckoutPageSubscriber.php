<?php

namespace Checkoutcom\Subscriber;

use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Checkoutcom\Handler\CheckoutcomCard;
use Shopware\Core\Framework\Context;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Config\Config;
use GuzzleHttp\Client;
use Checkoutcom\Helper\Url;
use RuntimeException;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;
use Checkoutcom\Service\MerchantService;

/**
 * CheckoutPageSubscriber
 */
class CheckoutPageSubscriber implements EventSubscriberInterface
{
    protected $config;
    private $paymentRepository;
    private $merchantService;
    public $restClient;

    /**
     *  GetSubscribedEvents
     */
    public static function getSubscribedEvents()
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'addComponentsVariable',
            AccountEditOrderPageLoadedEvent::class => 'accountPageLoadedEvent',
            AccountPaymentMethodPageLoadedEvent::class => 'paymentMethodPageLoadedEvent'
        ];
    }

    /**
     * Creates a new instance of the checkout confirm page subscriber.
     */
    public function __construct(Config $config, EntityRepositoryInterface $paymentRepository, MerchantService $merchantService)
    {
        $this->config = $config;
        $this->restClient = new Client();
        $this->paymentRepository = $paymentRepository;
        $this->merchantService = $merchantService;
    }

    /**
     * Adds the components variable to the storefront.
     */
    public function addComponentsVariable( $args)
    {
        $session = new Session();
        $publicKey = $this->config::publicKey();
        $token = $args->getPage()->getCart()->getToken();

        $salesChannelContext = $args->getSalesChannelContext()->getContext();
        $context = $args->getSalesChannelContext();
        
        // Get cko context
        $ckoContext = $this->getCkoContext($token);

        $apmData = $this->getApmData($ckoContext);

        if($this->merchantService->isGPayEnabled()) {
            array_push($apmData->apmName, 'gpay');

            $googlePayData = $this->getGooglePayData('checkout', $context, $args);
        }

        // check if save card is available in context
        // and save in session, this will be used when payment failed
        $isSaveCard = in_array('id', $apmData->apmName);
        $session->set('id', $isSaveCard);

        $sepaCreditorId = isset($apmData->sepaCreditor['id']) ? $apmData->sepaCreditor['id'] : null;
        $session->set('cko_sepa_creditor_id', $sepaCreditorId);

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->setCutomerInfo($customerInfo);
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $customField = $context->getCustomer()->getCustomFields();
        
        $args->getPage()->assign(
            [
                'ckoPublicKey' => $publicKey,
                'ckoContextId' => $ckoContext['id'],
                'name' => $name,
                'billingAddress' => json_encode($billingAddress),
                'isLoggedIn' => $isLoggedIn,
                'ckoPaymentMethodId' => $this->getPaymentMethodId($salesChannelContext),
                'framesUrl' => Url::CKO_IFRAME_URL,
                'activeToken' => $this->getPaymentInstrument($context),
                'isSaveCard' => $isSaveCard,
                'customerBillingAddress' => $billingAddress,
                'apms' => $apmData->apmName,
                'clientToken' => $apmData->clientToken ?? null,
                'sessionData' => $apmData->sessionData ?? null,
                'sepaCreditor' => $apmData->sepaCreditor ?? null,
                'paymentMethodCategory' => $this->getPaymentMethodCategory($apmData->paymentMethodAvailable ?? null) ?? null,
                'googlePayData' => $googlePayData ?? null,
                'googlePayEnv' => Url::isLive($publicKey) ? 'PRODUCTION' : 'TEST',
                'shopwareVersion' => strtok(Versions::getVersion('shopware/core'), '@')
            ]
        );
    }
    
    /**
     * Adds the components variable to the storefront.
     */
    public function accountPageLoadedEvent( $arg)
    {
        $session = new Session();
        $publicKey = $this->config::publicKey();

        $context = $arg->getSalesChannelContext();
        $ckoContext = $session->get('cko_context');
        $isSaveCard = $session->get('id');
        $apmData = $this->getApmData($ckoContext);

        if($this->merchantService->isGPayEnabled()) {
            array_push($apmData->apmName, 'gpay');

            $googlePayData = $this->getGooglePayData('order', $context, $arg);
        }

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->setCutomerInfo($customerInfo);
        
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $salesChannelContext = $arg->getSalesChannelContext()->getContext();
        $customField = $context->getCustomer()->getCustomFields();

        // Remove session variable
        $session->remove('id');
        $session->remove('ckoContext');

        $arg->getPage()->assign(
            [
                'ckoPublicKey' => $publicKey,
                'ckoContextId' => $ckoContext['id'],
                'name' => $name,
                'billingAddress' => json_encode($billingAddress),
                'isSaveCard' => $isSaveCard,
                'isLoggedIn' => $isLoggedIn,
                'ckoPaymentMethodId' => $this->getPaymentMethodId($salesChannelContext),
                'framesUrl' => Url::CKO_IFRAME_URL,
                'activeToken' => $this->getPaymentInstrument($context),
                'apms' => $apmData->apmName,
                'clientToken' => $apmData->clientToken ?? null,
                'sessionData' => $apmData->sessionData ?? null,
                'sepaCreditor' => $apmData->sepaCreditor ?? null,
                'paymentMethodCategory' => $this->getPaymentMethodCategory($apmData->paymentMethodAvailable ?? null) ?? null,
                'customerBillingAddress' => $billingAddress
            ]
        );
    }

        
    /**
     * paymentMethodPageLoadedEvent
     */
    public function paymentMethodPageLoadedEvent($arg)
    {
        $context = $arg->getSalesChannelContext();
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $customerInfo = $context->getCustomer()->getActiveBillingAddress();

        $arg->getPage()->assign(
            [
                'isLoggedIn' => $isLoggedIn,
                'activeToken' => $this->getPaymentInstrument($context),
                'current_page' => 'paymentMethodPageLoadedEvent'
            ]
        );
    }

    /**
     * validateCutomerInfo
     */
    public function setCutomerInfo($customerInfo): Array
    {
        $info = [];

        if (!empty($customerInfo->getZipCode())) {
            $info['zip'] = $customerInfo->getZipCode();
        }

        if (!empty($customerInfo->getCity())) {
            $info['city'] = $customerInfo->getCity();
        }

        if (!empty($customerInfo->getCountry()->getIso())) {
            $info['country'] = $customerInfo->getCountry()->getIso();
        }

        if (!empty($customerInfo->getStreet())) {
            $info['addressLine1'] = $customerInfo->getStreet();
        }

        if (!empty($customerInfo->getCountryState())) {
            $info['state'] = $customerInfo->getCountryState()->getName();
        }

        return $info;

    }

        
    /**
     * getPaymentMethodId
     *
     */
    private function getPaymentMethodId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', CheckoutcomCard::class));
        $paymentMethod = $this->paymentRepository->search($criteria, $context)->first();
        
        return $paymentMethod->getId();
    }

    /**
     * Get Context from shopware cloud plugin
     */
    public function getCkoContext($token)
    {
        $session = new Session();
        $uuid = Utilities::uuid();
        $session->set('cko_uuid', $uuid);

        $method = 'POST';
        $url = Url::getCloudContextUrl();

        $body = json_encode(['reference'=> $token ]);
        $header = [
            'Authorization' => $this->config::secretKey(),
            'x-correlation-id' => $uuid,
            'Content-Type' => 'application/json'
        ];
        
        try {
            $ckoContext = Utilities::postRequest($method, $url, $header, $body);

            $session->set('cko_context', $ckoContext);

            return $ckoContext;
            
        } catch (\Exception $e) {

            CkoLogger::log()->Error(
                "Error creating cko context",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.create.context",
                    LogFields::DATA => [ "id" => $uuid ]
                ]
            );

            throw new RuntimeException($e->getMessage());
        }
    }
    
    /**
     * getPaymentInstrument
     */
    public function getPaymentInstrument($context)
    {
        if ($context->getCustomer()->getGuest()) {
           return false;
        }

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $customerId = $customerInfo->getCustomerId();

        $url = Url::getRetrieveInstrumentUrl($customerId);

        $header = [
            'Authorization' => $this->config::secretKey()
        ];

        try {
            $response = Utilities::postRequest('GET', $url, $header, false);

            return $response['payment_instruments'];

        } catch (\Exception $e) {

            CkoLogger::log()->Error(
                "Error getting cko cko payment instrument",
                [
                    LogFields::MESSAGE => $e->getMessage(),
                    LogFields::TYPE => "checkout.payment.instrument",
                    LogFields::DATA => [ "id" => $customerId ]
                ]
            );

            throw new RuntimeException($e->getMessage());
        }
    }
    
        
    /**
     * getApms
     */
    public static function getApmData($ckoContext) : object
    {
        $apmData = new \stdClass();

        if (isset($ckoContext['apms'])) {
            $apmArray = $ckoContext['apms'];
            foreach ($apmArray as $apm) {

                if (isset($apm['name'])) {
                        $apmData->apmName[] = $apm['name'];
                }

                if (isset($apm['metadata']['details']['client_token'])) {
                        $apmData->clientToken = $apm['metadata']['details']['client_token'];
                        $apmData->sessionData = $apm['metadata']['session'];
                        $apmData->paymentMethodAvailable = $apm['metadata']['details']['payment_method_category'];
                }

                if (isset($apm['metadata']['creditor'])) {
                        $apmData->sepaCreditor = $apm['metadata']['creditor'];
                }
            }
        }

        return $apmData;
    }

    /**
     * getPaymentMethodCategory
     */
    public static function getPaymentMethodCategory($paymentMethodAvailable): Array
    {
        $paymentMethodCategory = [];

        if (isset($paymentMethodAvailable)) {
            foreach ($paymentMethodAvailable as $method) {
                $paymentMethodCategory[] = $method['identifier'];
            }
        }

        return $paymentMethodCategory;
    }

    public function getGooglePayData($page, $context, $args) { 
        $currency = $context->getCurrency();
        $price =  $page === 'checkout' ? $args->getPage()->getCart()->getPrice() : $args->getPage()->getOrder()->getPrice();
        $customerInfo = $context->getCustomer()->getActiveBillingAddress();

        return [
            "currency" => $currency->getIsoCode(),
            "totalPrice" => $price->getTotalPrice(),
            "gPayMerchantId" => $this->merchantService->getGPayMerchantId(),
            "gpayButtonStyle" => $this->merchantService->getGPayButtonStyle(),
            "billingCountry" => $customerInfo->getCountry()->getIso()
        ];
    }

}
