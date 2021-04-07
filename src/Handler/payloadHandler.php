<?php

namespace Checkoutcom\Handler;

use Checkoutcom\Service\CustomerService;
use Checkoutcom\Helper\Utilities;
use Checkoutcom\Config\Config;
use Symfony\Component\HttpFoundation\Session\Session;

class payloadHandler {

    const CREDITCARD = 'cc';

    /**
     *  create payment payload for credit card
     */
    public function creditCardPayload($transaction, $customFields, $type, $token, $correlationId) {

        $isSaveCardCheck = false;
        $ckoContextId = '';
        $paymentParam = [];

        $redirectionUrl = Utilities::getRedirectionUrl($_SERVER);

        /**
         * Retrieve the order from the transaction.
         */
        $order = $transaction->getOrder();
        $orderTransactionId = $transaction->getOrderTransaction()->getId();
        $orderNumber = $order->getOrderNumber();
        $orderId = $order->getId();
        
        /**
         * Retrieve customer details.
         * Get cko card token
         */
        $ckoContextId = $customFields['cko_payment']['cko_context_id'];
        $isSaveCardCheck = $customFields['cko_payment']['cko_save_card_check'] == 'true' ? true : false ;
        
        // Payment request payload
        $paymentParam['type'] = $type;
        $paymentParam['token'] = $token;
        $paymentParam['context'] = $ckoContextId;
        $paymentParam['reference'] = $orderNumber;
        $paymentParam['success_url'] = $redirectionUrl['success'];
        $paymentParam['failure_url'] = $redirectionUrl['fail'];
        $paymentParam['3ds']['enabled'] = true;
        $paymentParam['metadata']['correlation_id'] = $correlationId;
        $paymentParam['order_transaction_id'] = $orderTransactionId;
        $paymentParam['order_id'] = $orderId;
        $paymentParam['save_payment_instrument'] = $isSaveCardCheck;
        $paymentParam['customer_id'] = $order->getOrderCustomer()->getCustomerId();
        $paymentParam['metadata']['udf5'] = self::getIntegrationData();;
       
        
        return $paymentParam;
    }

    /**
     *  create payment payload for apms
     */
    public function apmPayload($transaction, $customFields, $correlationId) {
        $session = new Session();
        $paymentParam = [];
        $isSaveCardCheck = false;
        $ckoContextId = '';
        $redirectionUrl = Utilities::getRedirectionUrl($_SERVER);
        


        if ($session->get('AuthorizationToken')) {
            $klarnaAuthorizationToken = $session->get('AuthorizationToken');
        }

        if ($session->get('iban')) {
            $iban = $session->get('iban');
        }

        $order = $transaction->getOrder();
        $orderTransactionId = $transaction->getOrderTransaction()->getId();
        $orderNumber = $order->getOrderNumber();
        $orderId = $order->getId();

        $isSaveCardCheck = $customFields['cko_payment']['cko_save_card_check'] == 'true' ? true : false ;
        $ckoContextId = $customFields['cko_payment']['cko_context_id'];

        /**
         *  get apm selected by the customer from the customFields
         */
        $ckoApmSelected = $customFields['cko_payment']['cko_apm'];

        $paymentParam['context'] = $ckoContextId;
        $paymentParam['type'] = $ckoApmSelected;
        $paymentParam['reference'] = $orderNumber;
        $paymentParam['success_url'] = $redirectionUrl['success'];
        $paymentParam['failure_url'] = $redirectionUrl['fail'];
        $paymentParam['metadata']['correlation_id'] = $correlationId;
        $paymentParam['order_transaction_id'] = $orderTransactionId;
        $paymentParam['order_id'] = $orderId;
        $paymentParam['save_payment_instrument'] = $isSaveCardCheck;
        $paymentParam['customer_id'] = $order->getOrderCustomer()->getCustomerId();
        $paymentParam['metadata']['udf5'] = self::getIntegrationData();;

        //  payload for klarna
        if ($ckoApmSelected == 'klarna') {
            $paymentParam['token'] = $klarnaAuthorizationToken;
            $paymentParam['capture'] = false;

        }

        // payload for sepa
        if ($ckoApmSelected == 'sepa') {
            $paymentParam['banking']['iban'] = $iban;
        }

        return $paymentParam;
    }
  
    public function getIntegrationData() {
        $redirectionUrl = Utilities::getRedirectionUrl($_SERVER);
        $platformVersion = Utilities::getVersions();

        $integrationData = "Server url: " . $redirectionUrl['shopUrl'] . ", Platform_data: " . 'Shopware 6 /'. $platformVersion['shopwareVersion']
        . ", Integration Data - Checkout.com: " . $platformVersion['pluginVersion'];

        return $integrationData;
    }
}