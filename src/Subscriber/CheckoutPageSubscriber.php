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
        $session = new Session();
        $publicKey = $this->config::publicKey();
        $token = $args->getPage()->getCart()->getToken();

        $salesChannelContext = $args->getSalesChannelContext()->getContext();
        $context = $args->getSalesChannelContext();
        $currency = $context->getCurrency();
        $currencyCode = $currency->getIsoCode();
        
        // Get cko context
        $ckoContext = $this->getCkoContext($token, $publicKey, $currencyCode);
        $apms = $this->getApms($ckoContext);
        
        // check if save card is available in context
        // and save in session, this will be used when payment failed
        $isSaveCard = in_array("saveCard", $apms) == true ? true : false;
        $session->set('saveCard', $isSaveCard);

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->validateCutomerInfo($customerInfo);
        
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
                'activeToken' => $this->getActiveToken($customField),
                'isSaveCard' => $isSaveCard,
                'customerBillingAddress' => $billingAddress,
                'apms' => $apms,
                'clientToken' => $apms['clientToken'],
                'sessionData' => $apms['sessionData'],
                'sepaCreditor' => $apms['sepaCreditor'],
                'paymentMethodCategory' => $this->getPaymentMethodCategory($apms)
            ]
        );
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
        $apms = $this->getApms($ckoContext);

        $customerInfo = $context->getCustomer()->getActiveBillingAddress();
        $name = $customerInfo->getFirstName()." ".$customerInfo->getLastName();
        $billingAddress = $this->validateCutomerInfo($customerInfo);
        
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
                'activeToken' => $this->getActiveToken($customField),
                'apms' => $apms,
                'clientToken' => $apms['clientToken'],
                'sessionData' => $apms['sessionData'],
                'paymentMethodCategory' => $this->getPaymentMethodCategory($apms),
                'sepaCreditor' => $apms['sepaCreditor'],
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
        $customField = $context->getCustomer()->getCustomFields();

        $arg->getPage()->assign(
            [
                'isLoggedIn' => $isLoggedIn,
                'activeToken' => $this->getActiveToken($customField),
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
        $body = json_encode(['currency' => $currencyCode]);
        $header = [
            'Authorization' => $publicKey,
            'sw_context_token' => $token,
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
    
    /**
     * return list of Apms
     *
     * @param  mixed $ckoContext
     * @return void|array
     */
    public function getApms($ckoContext)
    {
        $apmList = null;

        if (array_key_exists("apms", $ckoContext) && count($ckoContext['apms']) > 0) {
            $apmArray = $ckoContext['apms'];
            foreach ($apmArray as $apm) {
                $apmList[] = $apm['name'];

                if ($apm['name'] == 'klarna') {
                    $apmList['clientToken'] = $apm['metadata']['details']['client_token'];
                    $apmList['sessionData'] = $apm['metadata']['session'];
                    $apmList['paymentMethodAvailable'] = $apm['metadata']['details']['payment_method_category'];
                }

                if ($apm['name'] == 'sepa') {
                    $apmList['sepaCreditor'] = $apm['metadata']['creditor'];
                }
            }
        }

        return $apmList;
    }

    /**
     * getPaymentMethodCategory
     *
     * @param  mixed $apms
     * @return void
     */
    public function getPaymentMethodCategory($apms)
    {
        $paymentMethodCategory = [];
        
        if (sizeof($apms['paymentMethodAvailable']) > 0) {
            foreach ($apms['paymentMethodAvailable'] as $method) {
                $paymentMethodCategory[] = $method['identifier'];
            }
        }

        return $paymentMethodCategory;
    }
}