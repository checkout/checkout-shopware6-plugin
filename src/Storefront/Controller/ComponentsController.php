<?php

namespace Checkoutcom\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Checkoutcom\Service\CustomerService;
use Checkoutcom\Config\Config;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Component\HttpFoundation\Session\Session;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Service\PaymentService;
use Checkoutcom\Helper\Url;
use Checkoutcom\helper\CkoLogging;

class ComponentsController extends StorefrontController
{
    
    /** @var CustomerService */
    protected $customerService;

     /** @var PaymentService */
     protected $paymentService;
    
    /** @var CartService */
    private $cartService;

    /** @var Config */
    protected $config;

    public function __construct(
        CustomerService $customerService,
        Config $config,
        CartService $cartService
        //PaymentService $paymentService
    ) {
        $this->customerService = $customerService;
        $this->config = $config;
        $this->cartService = $cartService;
        //$this->paymentService = $paymentService;
    }

     /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/css/",name="frontend.cko.components.css", options={"seo"="false"}, methods={"GET"})
     *
     * @param SalesChannelContext $context param
     * @param string $type param
     * @return Response response
     */
    public function componentsCss(SalesChannelContext $context): Response
    {
        /**
         * Get the contents of the css file.
         */
        $stylesheet = file_get_contents(
            __DIR__ . '/../../Resources/public/css/components.cko-iframe.css'
        );

        /**
         * Output the css stylesheet.
         */
        return new Response($stylesheet, 200, [
                'Content-Type' => 'text/css'
            ]
        );
    }

     /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/js/",name="frontend.cko.components.js", options={"seo"="false"}, methods={"GET"})
     *
     * @param SalesChannelContext $context param
     * @param string $type param
     * @return Response response
     */
    public function componentsJs(SalesChannelContext $context): Response
    {
        /**
         * Get the contents of the css file.
         */
        $js = file_get_contents(
            __DIR__ . '/../../Resources/public/js/components.cko-js.js'
        );

        /**
         * Output the js.
         */
        return new Response($js, 200, [
                'Content-Type' => 'text/javascript'
            ]
        );
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/store-card-token/{customerId}/{cardToken}/{ckoContextId}/{ckoPaymentType}/{isSaveCardCheck}", 
     * name="frontend.cko.components.storeCardToken", options={"seo"="false"}, methods={"GET"})
     *
     * @param SalesChannelContext $context
     * @param string $customerId
     * @param string $cardToken
     * @return Response
     */
    public function storeCardToken(
        SalesChannelContext $context,
        string $customerId,
        string $cardToken,
        string $ckoContextId,
        string $ckoPaymentType,
        string $isSaveCardCheck
    ): Response {
        $result = null;

        /** @var CustomerEntity $customer */
        $customer = $this->customerService->getCustomer($customerId, $context->getContext());

        if ($customer !== null) {
            $result = $this->customerService->setCardToken(
                $customer,
                $cardToken,
                $ckoContextId,
                $ckoPaymentType,
                $isSaveCardCheck,
                $context->getContext()
            );
        }
        
        return new Response(
            json_encode(
                [
                    'success' => (bool) $result,
                    'customerId' => $customerId,
                    'result' => $result->getErrors()
                ]
            ), 200, ['Content-Type' => 'text/javascript']
        );
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/store-authorization-token/{AuthorizationToken}", 
     * name="frontend.cko.components.storeAuthToken", options={"seo"="false"}, methods={"GET"})
     * 
     * @param string $AuthorizationToken
     * 
     */
    public function setKlarnaAuthorizationToken(string $AuthorizationToken): Response {

        $session = new Session();
        $session->set('AuthorizationToken', $AuthorizationToken);

        return new Response(
            json_encode(
                [
                    'success' => true,
                ]
            ), 200, ['Content-Type' => 'text/javascript']
        );
    }



    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/store-apm-selected/{customerId}/{ckoContextId}/{ckoApm}", 
     * name="frontend.cko.components.storeApm", options={"seo"="false"}, methods={"GET"})
     *
     * @param SalesChannelContext $context
     * @param string $customerId
     * @param string $ckoApm
     * @return Response
     */
    public function storeApmSelected(
        SalesChannelContext $context,
        string $customerId,
        string $ckoContextId,
        string $ckoApm
    ): Response {

        /** @var CustomerEntity $customer */
        $customer = $this->customerService->getCustomer($customerId, $context->getContext());

        if ($customer !== null) {
            $result = $this->customerService->setApm(
                $customer,
                $ckoContextId,
                $ckoApm,
                $context->getContext()
            );
        }
        
        return new Response(
            json_encode(
                [
                    'success' => (bool) $result,
                    'customerId' => $customerId,
                    'result' => $result->getErrors()
                ]
            ), 200, ['Content-Type' => 'text/javascript']
        );
    }

     /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/getCustomerData/{customerId}/", name="frontend.cko.components.getCustomerData",
     * options={"seo"="false"}, methods={"GET"})
     */
    public function getCustomerData(SalesChannelContext $context, $customerId) : Response 
    {
        $customer = $this->customerService->getCustomer($customerId, $context->getContext());
        $customFields = $customer->getCustomFields();

        $publicKey = $this->config::publicKey();

        return new Response(
            json_encode(
                [
                    'success' => (bool) $customFields,
                    'result' => $customFields,
                    'pk' => $publicKey
                ]
            ), 200, ['Content-Type' => 'text/javascript']
        );
    }

     /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/cko/components/remove-card/{card}/", name="frontend.cko.components.removeCard",
     * options={"seo"="false"}, methods={"GET"})
     */
    public function removeCard(SalesChannelContext $context, $card) : Response 
    {
        $customerId = $context->getCustomer()->getId();
        $uuid = Utilities::uuid();
        $header =  [
            'Authorization' => config::secretKey(),
            'x-correlation-id' => $uuid,
            'Accept' => 'application/json'
        ];

        $url = Url::getDeleteInstrumentUrl($customerId, $card);
        try {
            $deleteCardRequest = Utilities::postRequest( 'DELETE', $url, $header );
            
        } catch (\Exception $e) {
            $logMessage = Utilities::contructLogBody($e, "cko context", "checkout.context.error", $uuid);
            CkoLogging::log($logMessage);
        }

        return new Response(
            json_encode(
                []
            ), 200, ['Content-Type' => 'text/javascript']
        );
    }
}