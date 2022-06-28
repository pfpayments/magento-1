<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html/).
 *
 * @package PostFinanceCheckout_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * Webhook processor to handle transaction completion state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_TransactionCompletion extends PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated
{

    /**
     *
     * @see PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated::loadEntity()
     * @return \PostFinanceCheckout\Sdk\Model\TransactionCompletion
     */
    protected function loadEntity(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        $completionService = new \PostFinanceCheckout\Sdk\Service\TransactionCompletionService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $completionService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getTransactionId($completion)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\TransactionCompletion $completion */
        return $completion->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Mage_Sales_Model_Order $order, $completion)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\TransactionCompletion $completion */
        switch ($completion->getState()) {
            case \PostFinanceCheckout\Sdk\Model\TransactionCompletionState::FAILED:
                $this->failed($completion->getLineItemVersion()
                    ->getTransaction(), $order);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    protected function failed(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order)
    {
        $invoice = $this->getInvoiceForTransaction($transaction->getLinkedSpaceId(), $transaction->getId(), $order);
        if ($invoice != null && $invoice->getPostfinancecheckoutCapturePending() &&
            $invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN) {
            $invoice->setPostfinancecheckoutCapturePending(false);

            $authTransaction = $order->getPayment()->getAuthorizationTransaction();
            $authTransaction->setIsClosed(0);

            Mage::getModel('core/resource_transaction')->addObject($invoice)
                ->addObject($authTransaction)
                ->save();
        }
    }

    /**
     * Returns the invoice for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function getInvoiceForTransaction($spaceId, $transactionId, Mage_Sales_Model_Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if (strpos($invoice->getTransactionId(), $spaceId . '_' . $transactionId) === 0 &&
                $invoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }

        return null;
    }
}