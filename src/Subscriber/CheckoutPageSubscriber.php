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
use RuntimeException;
use Psr\Log\LoggerInterface;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;

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
        $apmData = $this->getApmData($ckoContext);
        
        // check if save card is available in context
        // and save in session, this will be used when payment failed
        $isSaveCard = in_array('id', $apmData->apmName);
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
    public function getCkoContext($token, $publicKey, $currencyCode)
    {
        $session = new Session();

        $uuid = Utilities::uuid();
        $session->set('cko_uuid', $uuid);

        $method = 'POST';
        $url = Url::getCloudContextUrl();

        $body = json_encode(['currency' => $currencyCode, 'reference'=> $token ]);
        $header = [
            'Authorization' => $publicKey,
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

    public function getPaymentInstrument(string $customerId)
    {
        $url = Url::getRetrieveInstrumentUrl($customerId);

        $header = [
            'Authorization' => $this->config::secretKey()
        ];

        try {
            $response = Utilities::postRequest('GET', $url, $header, false);

            return $response;

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