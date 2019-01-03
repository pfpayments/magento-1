<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/).
 *
 * @package PostFinanceCheckout_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * This service provides functions to deal with PostFinance Checkout delivery indications.
 */
class PostFinanceCheckout_Payment_Model_Service_DeliveryIndication extends PostFinanceCheckout_Payment_Model_Service_Abstract
{

    /**
     * The delivery indication API service.
     *
     * @var \PostFinanceCheckout\Sdk\Service\DeliveryIndicationService
     */
    protected $_deliveryIndicationService;

    /**
     * Marks the delivery indication belonging to the given payment as suitable.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \PostFinanceCheckout\Sdk\Model\DeliveryIndication
     */
    public function markAsSuitable(Mage_Sales_Model_Order_Payment $payment)
    {
        $deliveryIndication = $this->getDeliveryIndicationForTransaction(
            $payment->getOrder()
                ->getPostfinancecheckoutSpaceId(), $payment->getOrder()
                ->getPostfinancecheckoutTransactionId());
        return $this->getDeliveryIndicationService()->markAsSuitable($deliveryIndication->getLinkedSpaceId(),
            $deliveryIndication->getId());
    }

    /**
     * Marks the delivery indication belonging to the given payment as not suitable.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return \PostFinanceCheckout\Sdk\Model\DeliveryIndication
     */
    public function markAsNotSuitable(Mage_Sales_Model_Order_Payment $payment)
    {
        $deliveryIndication = $this->getDeliveryIndicationForTransaction(
            $payment->getOrder()
                ->getPostfinancecheckoutSpaceId(), $payment->getOrder()
                ->getPostfinancecheckoutTransactionId());
        return $this->getDeliveryIndicationService()->markAsNotSuitable($deliveryIndication->getLinkedSpaceId(),
            $deliveryIndication->getId());
    }

    /**
     * Returns the delivery indication API service..
     *
     * @return \PostFinanceCheckout\Sdk\Service\DeliveryIndicationService
     */
    protected function getDeliveryIndicationService()
    {
        if ($this->_deliveryIndicationService == null) {
            $this->_deliveryIndicationService = new \PostFinanceCheckout\Sdk\Service\DeliveryIndicationService(
                $this->getHelper()->getApiClient());
        }

        return $this->_deliveryIndicationService;
    }

    /**
     * Returns the delivery indication for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \PostFinanceCheckout\Sdk\Model\DeliveryIndication
     */
    protected function getDeliveryIndicationForTransaction($spaceId, $transactionId)
    {
        $query = new \PostFinanceCheckout\Sdk\Model\EntityQuery();
        $query->setFilter($this->createEntityFilter('transaction.id', $transactionId));
        $query->setNumberOfEntities(1);
        return current($this->getDeliveryIndicationService()->search($spaceId, $query));
    }
}