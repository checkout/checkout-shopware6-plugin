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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Config\Config;
use GuzzleHttp\Psr7\Request;
use Checkoutcom\Checkoutcom;
use GuzzleHttp\Client;
use RuntimeException;
use Exception;
use Checkoutcom\helper\Url;

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
        $publicKey = $this->config::publicKey();
        $token = $args->getPage()->getCart()->getToken();

        $salesChannelContext = $args->getSalesChannelContext()->getContext();
        $context = $args->getSalesChannelContext();
        $currency = $context->getCurrency();
        $currencyCode = $currency->getIsoCode();
        // Get context
        $ckoContext = $this->getCkoContext($token, $publicKey, $currencyCode);

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->validateCutomerInfo($customerInfo);
        $isSaveCard = true;
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $customField = $context->getCustomer()->getCustomFields();
        $activeToken = [];

        // handle apms available in the context
        $apms = array();
        $customerDetails = array();
        $apmsAvailable = false;
        $paymentMethodAvailable = array();
        $paymentMethodCategory = array();
        $clientToken = 'undefined';
        $sessionData = 'undefined';
        $sepaCreditor = 'undefined';

        $customerDetails['firstName'] = $customerInfo->getFirstName();
        $customerDetails['lastName'] = $customerInfo->getLastName();

        if (array_key_exists("apms", $ckoContext) && count($ckoContext['apms']) > 0) {
            $apmArray = $ckoContext['apms'];
            foreach ($apmArray as $apm){
                $apms[] = $apm['name'];

                if ($apm['name'] == 'klarna') {
                    $clientToken = $apm['metadata']['details']['client_token'];
                    $sessionData = $apm['metadata']['session'];
                    $paymentMethodAvailable = $apm['metadata']['details']['payment_method_category'];
                }

                if ($apm['name'] == 'sepa') {
                    $sepaCreditor = $apm['metadata']['creditor'];
                }
            }
        }
        
        // display apms available for the customer
        if (sizeof($apms) > 0) {
            $apmsAvailable = true;
        }

         // get identifier of the payment method category
        if (sizeof($paymentMethodAvailable) > 0) {
            foreach ($paymentMethodAvailable as $method)
                $paymentMethodCategory[] = $method['identifier'];
        }
        
        // Check if custom field is empty
        if (!empty( $customField)) {
            $activeToken = $this->getActiveToken($customField);
        }

        $args->getPage()->assign(
            [
                'ckoPublicKey' => $publicKey,
                'ckoContextId' => $ckoContext['id'],
                'name' => $name,
                'billingAddress' => json_encode($billingAddress),
                'isSaveCard' => $isSaveCard,
                'isLoggedIn' => $isLoggedIn,
                'ckoPaymentMethodId' => $this->getPaymentMethodId($salesChannelContext),
                'framesUrl' => Url::CKO_IFRAME_URL,
                'activeToken' => $activeToken,
                'apms' => $apms,
                'apmAvailable' => $apmsAvailable,
                'clientToken' => $clientToken,
                'sessionData' => $sessionData,
                'paymentMethodCategory' => $paymentMethodCategory,
                'sepaCreditor' => $sepaCreditor,
                'customerBillingAddress' => $billingAddress,
                'customerDetails' => $customerDetails
            ]
        );
    }
    
    /**
     * validateCutomerInfo
     *
     * @param  mixed $customerInfo
     * @return void
     */
    public function validateCutomerInfo($customerInfo)
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
     * Adds the components variable to the storefront.
     *
     * @param accountPageLoadedEvent $arg
     */
    public function accountPageLoadedEvent( $arg)
    {
        $session = new Session();
        $publicKey = $this->config::publicKey();

        $context = $arg->getSalesChannelContext();
        $token = $context->getToken();
        $currency = $context->getCurrency();
        $currencyCode = $currency->getIsoCode();
        $ckoContext = $session->get('cko_context');

        // @todo clear ckoContext sesssion

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->validateCutomerInfo($customerInfo);
        $isSaveCard = true;
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $salesChannelContext = $arg->getSalesChannelContext()->getContext();
        $customField = $context->getCustomer()->getCustomFields();
        $activeToken = [];

         // handle apms available in the context
         $apms = array();
         $apmsAvailable = false;
         $paymentMethodAvailable = array();
         $paymentMethodCategory = array();
         $clientToken = 'undefined';
         $sessionData = 'undefined';
         $sepaCreditor = 'undefined';
 
         $customerDetails['firstName'] = $customerInfo->getFirstName();
         $customerDetails['lastName'] = $customerInfo->getLastName();
 
         if (array_key_exists("apms", $ckoContext) && count($ckoContext['apms']) > 0) {
             $apmArray = $ckoContext['apms'];
             foreach ($apmArray as $apm){
                 $apms[] = $apm['name'];

                 if ($apm['name'] == 'klarna') {
                    $clientToken = $apm['metadata']['details']['client_token'];
                    $sessionData = $apm['metadata']['session'];
                    $paymentMethodAvailable = $apm['metadata']['details']['payment_method_category'];
                }

                if ($apm['name'] == 'sepa') {
                    $sepaCreditor = $apm['metadata']['creditor'];
                }
             }
         }
         
         // display apms available for the customer
         if (sizeof($apms) > 0) {
             $apmsAvailable = true;
         }

        // get identifier of the payment method category
        if (sizeof($paymentMethodAvailable) > 0) {
            foreach ($paymentMethodAvailable as $method)
                $paymentMethodCategory[] = $method['identifier'];
        }
        
        // Check if custom field is empty
        if (!empty( $customField)) {
            $activeToken = $this->getActiveToken($customField);
        }

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
                'activeToken' => $activeToken,
                'apms' => $apms,
                'apmAvailable' => $apmsAvailable,
                'clientToken' => $clientToken,
                'sessionData' => $sessionData,
                'paymentMethodCategory' => $paymentMethodCategory,
                'sepaCreditor' => $sepaCreditor,
                'customerBillingAddress' => $billingAddress,
                'customerDetails' => $customerDetails
            ]
        );
    }

    /**
     *  @param AccountPaymentMethodPageLoadedEvent $arg
     */
    public function paymentMethodPageLoadedEvent($arg)
    {
        $context = $arg->getSalesChannelContext();
        $isSaveCard = true;
        $isLoggedIn = $context->getCustomer()->getGuest() == true ? false : true;
        $salesChannelContext = $arg->getSalesChannelContext()->getContext();
        $customField = $context->getCustomer()->getCustomFields();
        $activeToken = [];
        
        // Check if custom field is empty
        if (!empty( $customField)) {
            $activeToken = $this->getActiveToken($customField);
        }

        $arg->getPage()->assign(
            [
                'isSaveCard' => $isSaveCard,
                'isLoggedIn' => $isLoggedIn,
                'ckoPaymentMethodId' => $this->getPaymentMethodId($salesChannelContext),
                'activeToken' => $activeToken,
                'current_page' => 'paymentMethodPageLoadedEvent'
            ]
        );
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
    public function getCkoContext($token, $publicKey, $currencyCode)
    {
        $session = new Session();

        $uuid = Utilities::uuid();
        $session->set('cko_uuid', $uuid);

        $method = 'POST';
        $url = Url::getCloudContextUrl();
        $body = json_encode(['currency' => $currencyCode, 'reference' => $token]);
        $header = [
            'Authorization' => $publicKey,
            'Content-Type' => 'application/json',
            'x-correlation-id' => $uuid
        ];

        $ckoContext = Utilities::postRequest($method, $url, $header, $body);

        $session->set('cko_context', $ckoContext);

        return $ckoContext;
    }

    public function getActiveToken($customField)
    {
        $activeToken = [];


        // check if token exist and set value in $activeToken
        foreach ( $customField as $key => $value) {
            if (strstr($key, 'active_token_')) {
                // unset the source id from array
                if(!empty($value)) {
                    unset($value['id']);
                
                    $arr = array_merge((array)$key,$value);
    
                    // push data to active token 
                    array_push( $activeToken, $arr);
                }
            }
        }

        return $activeToken;
    }
}