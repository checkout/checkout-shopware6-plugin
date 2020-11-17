<?php

namespace Checkoutcom\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CustomerService
{
    /** @var EntityRepository */
    protected $customerRepository;

    public function __construct( EntityRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Stores the credit card token in the custom fields of the customer.
     *
     * @param CustomerEntity $customer
     * @param string $cardToken
     * @param Context $context
     *
     * @return \Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent
     */
    public function setCardToken(
        CustomerEntity $customer,
        string $cardToken,
        string $ckoContextId,
        string $ckoPaymentType,
        string $isSaveCardCheck,
        Context $context
    ) {
        // Get existing custom fields
        $customFields = $customer->getCustomFields();

        // If custom fields are empty, create a new array
        if (!is_array($customFields)) {
            $customFields = [];
           // echo("<script>console.log('new custom field');</script>");
        }

        // Store the card token in the custom fields
        $customFields['cko_payment']['cko_card_token'] = $cardToken;
        $customFields['cko_payment']['cko_context_id'] = $ckoContextId;
        $customFields['cko_payment']['cko_payment_type'] = $ckoPaymentType;
        $customFields['cko_payment']['cko_save_card_check'] = $isSaveCardCheck;
        

        // Store the custom fields on the customer
        return $this->customerRepository->update([[
                'id' => $customer->getId(),
                'customFields' => $customFields
            ]], $context
        );
    }

    /**
     * Stores the apm selected in the custom fields that the customer selected.
     *
     * @param CustomerEntity $customer
     * @param string $ckoApm
     * @param Context $context
     *
     * @return \Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent
     */
    public function setApm(
        CustomerEntity $customer,
        string $ckoContextId,
        string $ckoApm,
        Context $context
    ) {
        // Get existing custom fields
        $customFields = $customer->getCustomFields();

        // If custom fields are empty, create a new array
        if (!is_array($customFields)) {
            $customFields = [];
        }

        // Store the card token in the custom fields
        $customFields['cko_payment']['cko_context_id'] = $ckoContextId;
        $customFields['cko_payment']['cko_apm'] = $ckoApm;
        
        // Store the custom fields on the customer
        return $this->customerRepository->update([[
                'id' => $customer->getId(),
                'customFields' => $customFields
            ]], $context
        );
    }


    /**
     * Return a customer entity with address associations.
     *
     * @param string $customerId
     * @param Context $context
     * @return CustomerEntity|null
     */
    public function getCustomer(string $customerId, Context $context) : ? CustomerEntity
    {
        $customer = null;

        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $customerId));
            $criteria->addAssociation('activeShippingAddress');
            $criteria->addAssociation('activeBillingAddress');
            $criteria->addAssociation('defaultShippingAddress');
            $criteria->addAssociation('defaultBillingAddress');

            /** @var CustomerEntity $customer */
            $customer = $this->customerRepository->search($criteria, $context)->first();
        } catch (\Exception $e) {
            // @todo log error
        }

        return $customer;
    }

    /**
     * Get customer's source id
     */
    public function getSourceId($identifier, $customer)
    {
        $customFields = $customer->getCustomFields();
        $sourceId = '';

        if ( !empty( $customFields) ) {
            // check if token exist and set value in $activeToken
            foreach ( $customFields as $key => $value) {
                if (strstr($key, $identifier)) {
                    $sourceId = $value['id'];
                }
            }
        }

        return $sourceId;
    }
}