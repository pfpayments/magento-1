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
 * Webhook processor to handle delivery indication state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_DeliveryIndication extends PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated
{

    /**
     *
     * @see PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated::loadEntity()
     * @return \PostFinanceCheckout\Sdk\Model\DeliveryIndication
     */
    protected function loadEntity(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        $deliveryIndicationService = new \PostFinanceCheckout\Sdk\Service\DeliveryIndicationService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $deliveryIndicationService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getTransactionId($deliveryIndication)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\DeliveryIndication $deliveryIndication */
        return $deliveryIndication->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Mage_Sales_Model_Order $order, $deliveryIndication)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\DeliveryIndication $deliveryIndication */
        switch ($deliveryIndication->getState()) {
            case \PostFinanceCheckout\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
                $this->review($order);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    protected function review(Mage_Sales_Model_Order $order)
    {
        if ($order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true,
                Mage::helper('postfinancecheckout_payment')->__(
                    'A manual decision about whether to accept the payment is required.'));
            $order->save();
        }
    }
}