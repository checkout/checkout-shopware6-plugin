<?php 

declare(strict_types=1);

namespace Checkoutcom\Handler;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Checkoutcom\Service\CustomerService;
use Checkoutcom\Service\PaymentService;
use Checkoutcom\Config\Config;
use Checkoutcom\Helper\Utilities;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Checkoutcom\Handler\payloadHandler;

class CheckoutcomCard implements AsynchronousPaymentHandlerInterface
{
    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;

    /**
     * @var customerService
     */
    private $customerService;

    /**
     * @var config
     */
    protected $config;

    /** @var TranslatorInterface */
    private $translator;

    private $paymentService;

    const TYPE_TOKEN = 'token';
    const TYPE_ID = 'id';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        CustomerService $customerService,
        Config $config,
        PaymentService $paymentService
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->customerService = $customerService;
        $this->config = $config;
        // $this->client = HttpClient::create();
        $this->paymentService = $paymentService;

    }

    /**
     * @throws AsyncPaymentProcessException
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {

        $session = new Session();

        $ckoContextId = '';
        $type = self::TYPE_TOKEN;
        $token = '';

        /**
         * Retrieve the order from the transaction.
         */
        $order = $transaction->getOrder();

        /**
         * Retrieve customer details.
         * Get cko card token
         */
        $customer = $this->customerService->getCustomer(
            $order->getOrderCustomer()->getCustomerId(),
            $salesChannelContext->getContext()
        );
        $customFields = $customer->getCustomFields();
        $token = $customFields['cko_payment']['cko_card_token'];
        $ckoContextId = $customFields['cko_payment']['cko_context_id'];
        $ckoPaymentType = $customFields['cko_payment']['cko_payment_type'];

        // get apm selected by the customer from the customFields
        $ckoApmSelected = $customFields['cko_payment']['cko_apm'];

        // If new card set token in payment payload
        if ($ckoPaymentType != 'new_card' && $ckoPaymentType != $ckoApmSelected) {
            // If saved card, set source id in payment payload
            $type = self::TYPE_ID;
            $token = $this->customerService->getSourceId(
                $ckoPaymentType,
                $customer
            );
        }

        /**
         * Throw exception if cko card token is empty
         */
        if (empty($token) || empty($ckoContextId)) {
            // @todo log error in cloud plugin
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'Empty cko card token'
            );

            return new RedirectResponse($transaction->getReturnUrl());
        }

        $correlationId = $session->get('cko_uuid');

        /**
         *  create payload depending on payment method
         */
        if ($ckoApmSelected == 'undefined') {
            $paymentParam = payloadHandler::creditCardPayload($transaction, $customFields, $type, $token, $correlationId);
        } else {
            $paymentParam = payloadHandler::apmPayload($transaction, $customFields, $correlationId);
        }

        $session->set('Redirection', $transaction->getReturnUrl());
        
        $paymentResponse = $this->paymentService->create($paymentParam, $correlationId);

        if ($paymentResponse['state'] == 'ERROR' || $paymentResponse['state'] == PaymentService::PAYMENT_REDIRECT) {
            /**
             * set cko context id in a session if payment failed
             * so that we do not need to create context again on account/edit/ page
             */
            $session->set('cko_context_id', $ckoContextId);
        }

         /**
         *  If there is redirection link, redirect to the given url
         */
        if ($paymentResponse['state'] == PaymentService::PAYMENT_REDIRECT) {
            return new RedirectResponse($paymentResponse['url']);
        }

        $redirectUrl = $transaction->getReturnUrl()
            . '&state='
            . $paymentResponse['state']
            . '&msg='
            . $paymentResponse['message'];

        // return to finalize method using redirect
        return new RedirectResponse($redirectUrl);
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $paymentState = $request->query->get('state');
        
        if ($paymentState == PaymentService::PAYMENT_ERROR ) {
            throw new AsyncPaymentFinalizeException(
                $transaction->getOrderTransaction()->getId(),
                'CKO payment error: ' . $request->query->get('msg')
            );
        }
    }
}