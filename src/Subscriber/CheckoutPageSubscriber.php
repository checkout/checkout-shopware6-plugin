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
use Checkoutcom\Models\Address;

class CheckoutPageSubscriber implements EventSubscriberInterface
{
    protected $config;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     *  GetSubscribedEvents
     *
     * @return void
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
     *
     * @param Config $config config
     */
    public function __construct(Config $config, EntityRepositoryInterface $paymentRepository)
    {
        $this->config = $config;
        $this->restClient = new Client();
        $this->paymentRepository =  $paymentRepository;
    }

    /**
     * Adds the components variable to the storefront.
     *
     * @param CheckoutConfirmPageLoadedEvent $args
     */
    public function addComponentsVariable( $args)
    {
        $session = new Session();
        $publicKey = $this->config::publicKey();
        $token = $args->getPage()->getCart()->getToken();

        $salesChannelContext = $args->getSalesChannelContext()->getContext();
        $context = $args->getSalesChannelContext();
        
        // get customer information from sw context
        $info = self::ckoContextBody($context, $token);
        
        // Get cko context
        $ckoContext = $this->getCkoContext($publicKey,$info);
        $apmData = $this->getApmData($ckoContext);
        
        // check if save card is available in context
        // and save in session, this will be used when payment failed
        $isSaveCard = in_array('saveCard', $apmData->apmName);
        $session->set('saveCard', $isSaveCard);

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
                'activeToken' => $this->getPaymentInstrument($customerInfo->getCustomerId()),
                'isSaveCard' => $isSaveCard,
                'customerBillingAddress' => $billingAddress,
                'apms' => $apmData->apmName,
                'clientToken' => $apmData->clientToken ?? null,
                'sessionData' => $apmData->sessionData ?? null,
                'sepaCreditor' => $apmData->sepaCreditor ?? null,
                'paymentMethodCategory' => $this->getPaymentMethodCategory($apmData->paymentMethodAvailable ?? null) ?? null

            ]
        );
    }


    /**
     * get required fields from the customer object
     * @param  mixed $context
     * @param  $token reference
     */
    public static function ckoContextBody($context, $token) {

        $info = [];

        $info["currency"] = $context->getCurrency()->getIsoCode();
        $info["reference"] = $token;
        $info["customerInfo"]["customer"]["email"] = $context->getCustomer()->getEmail();
        $info["customerInfo"]["customer"]["name"] = $context->getCustomer()->getActiveBillingAddress()->getFirstName()." ".$context->getCustomer()->getActiveBillingAddress()->getLastName();
        $info["customerInfo"]["shipping"]["address"]["address_line1"] = $context->getCustomer()->getActiveShippingAddress()->getStreet();
        $info["customerInfo"]["shipping"]["address"]["city"] = $context->getCustomer()->getActiveShippingAddress()->getCity();
        $info["customerInfo"]["shipping"]["address"]["state"] = $context->getCustomer()->getActiveShippingAddress()->getCountryState()->getName();
        $info["customerInfo"]["shipping"]["address"]["zip"] = $context->getCustomer()->getActiveShippingAddress()->getZipCode();
        $info["customerInfo"]["shipping"]["address"]["country"] = $context->getCustomer()->getActiveShippingAddress()->getCountry()->getIso();
        $info["customerInfo"]["billing"]["address"]["address_line1"] = $context->getCustomer()->getActiveBillingAddress()->getStreet();
        $info["customerInfo"]["billing"]["address"]["city"] = $context->getCustomer()->getActiveBillingAddress()->getCity();
        $info["customerInfo"]["billing"]["address"]["state"] = $context->getCustomer()->getActiveBillingAddress()->getCountryState()->getName();
        $info["customerInfo"]["billing"]["address"]["zip"] = $context->getCustomer()->getActiveBillingAddress()->getZipCode();
        $info["customerInfo"]["billing"]["address"]["country"] = $context->getCustomer()->getActiveBillingAddress()->getCountry()->getIso();

    }
    
    /**
     * Adds the components variable to the storefront.
     *
     * @param accountPageLoadedEvent $arg
     */
    public function accountPageLoadedEvent( $arg)
    {
        $session = new Session();
        $publicKey = $this->config::publicKey();

        $context = $arg->getSalesChannelContext();
        $ckoContext = $session->get('cko_context');
        $isSaveCard = $session->get('saveCard');
        $apmData = $this->getApmData($ckoContext);

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->setCutomerInfo($customerInfo);
        
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $salesChannelContext = $arg->getSalesChannelContext()->getContext();
        $customField = $context->getCustomer()->getCustomFields();

        // Remove session variable
        $session->remove('saveCard');
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
                'activeToken' => $this->getPaymentInstrument($customerInfo->getCustomerId()),
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
     *  @param AccountPaymentMethodPageLoadedEvent $arg
     */
    public function paymentMethodPageLoadedEvent($arg)
    {
        $context = $arg->getSalesChannelContext();
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $customerInfo = $context->getCustomer()->getActiveBillingAddress();

        $arg->getPage()->assign(
            [
                'isLoggedIn' => $isLoggedIn,
                'activeToken' => $this->getPaymentInstrument($customerInfo->getCustomerId()),
                'current_page' => 'paymentMethodPageLoadedEvent'
            ]
        );
    }

    /**
     * validateCutomerInfo
     *
     * @param  mixed $customerInfo
     * @return void
     */
    public function setCutomerInfo($customerInfo)
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
     * Get cko payment id
     */
    private function getPaymentMethodId(Context $context): ?string
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', CheckoutcomCard::class));
        $paymentMethod = $this->paymentRepository->search($criteria, $context)->first();
        
        return $paymentMethod->getId();
    }

    /**
     * Get Context from shopware cloud plugin
     * 
     * @param $token        Card token
     * @param $publicKey    Cko Pubic Key
     * @param $currencyCode Iso code from shopware
     * 
     * @return $ckoContext return context
     */
    public function getCkoContext($publicKey, $info)
    {
        $session = new Session();

        $uuid = Utilities::uuid();
        $session->set('cko_uuid', $uuid);

        $method = 'POST';
        $url = Url::getCloudContextUrl();

        $body = json_encode($info);
        $header = [
            'Authorization' => $publicKey,
            'x-correlation-id' => $uuid,
            'Content-Type' => 'application/json'
        ];

        $ckoContext = Utilities::postRequest($method, $url, $header, $body);

        $session->set('cko_context', $ckoContext);

        return $ckoContext;
    }

    public function getPaymentInstrument(string $customerId)
    {
        $url = Url::getRetrieveInstrumentUrl($customerId);

        $header = [
            'Authorization' => $this->config::secretKey()
        ];

        $response = Utilities::postRequest('GET', $url, $header, false);
        
        return $response;
    }
    
        
    /**
     * getApms
     *
     * @param  mixed $ckoContext
     * @return void
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
     *
     * @param  mixed $apms
     * @return void
     */
    public static function getPaymentMethodCategory($paymentMethodAvailable)
    {
        $paymentMethodCategory = [];

        if (isset($paymentMethodAvailable)) {
            foreach ($paymentMethodAvailable as $method) {
                $paymentMethodCategory[] = $method['identifier'];
            }
        }

        return $paymentMethodCategory;
    }

}