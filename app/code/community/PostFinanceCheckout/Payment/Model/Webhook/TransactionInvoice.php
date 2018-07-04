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
 * Webhook processor to handle transaction inovice transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_TransactionInvoice extends PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated
{

    /**
     *
     * @see PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated::loadEntity()
     * @return \PostFinanceCheckout\Sdk\Model\TransactionInvoice
     */
    protected function loadEntity(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        $transactionInvoiceService = new \PostFinanceCheckout\Sdk\Service\TransactionInvoiceService(Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $transactionInvoiceService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getTransactionId($transactionInvoice)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\TransactionInvoice $transactionInvoice */
        return $transactionInvoice->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Mage_Sales_Model_Order $order, $transactionInvoice)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\TransactionInvoice $transactionInvoice */
        $invoice = $this->getInvoiceForTransaction(
            $transactionInvoice->getLinkedSpaceId(), $transactionInvoice->getCompletion()
            ->getLineItemVersion()
            ->getTransaction()
            ->getId(), $order
        );
        if (! $invoice || $invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_OPEN) {
            switch ($transactionInvoice->getState()) {
                case \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
                case \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::PAID:
                    $this->capture(
                        $transactionInvoice->getCompletion()
                        ->getLineItemVersion()
                        ->getTransaction(), $order, $transactionInvoice->getAmount(), $invoice
                    );
                    break;
                case \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
                    $this->derecognize(
                        $transactionInvoice->getCompletion()
                        ->getLineItemVersion()
                        ->getTransaction(), $order, $invoice
                    );
                default:
                    // Nothing to do.
                    break;
            }
        }
    }

    protected function capture(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order, $amount, Mage_Sales_Model_Order_Invoice $invoice = null)
    {
        $isOrderInReview = ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);

        if (! $invoice) {
            $order->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
            $invoice = $this->createInvoice($transaction->getLinkedSpaceId(), $transaction->getId(), $order);
        }

        if (Mage_Sales_Model_Order_Invoice::STATE_OPEN == $invoice->getState()) {
            $order->getPayment()->registerCaptureNotification($amount);
            $invoice->setPostfinancecheckoutCapturePending(false)->save();
        }

        $this->sendOrderEmail($order);
        if ($transaction->getState() == \PostFinanceCheckout\Sdk\Model\TransactionState::COMPLETED) {
            $order->setStatus('processing_postfinancecheckout');
        }

        if ($isOrderInReview) {
            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);
        }

        $order->save();
    }

    protected function derecognize(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice = null)
    {
        if ($invoice && Mage_Sales_Model_Order_Invoice::STATE_OPEN == $invoice->getState()) {
            $isOrderInReview = ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);

            $order->getPayment()->registerVoidNotification();

            $invoice->setPostfinancecheckoutCapturePending(false);
            $order->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);

            if ($isOrderInReview) {
                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);
            }

            $order->save();
        }
    }

    /**
     * Sends the order email if not already sent.
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function sendOrderEmail(Mage_Sales_Model_Order $order)
    {
        if ($order->getStore()->getConfig('postfinancecheckout_payment/email/order') && ! $order->getEmailSent()) {
            $order->sendNewOrderEmail();
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
            if (strpos($invoice->getTransactionId(), $spaceId . '_' . $transactionId) === 0 && $invoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }

        return null;
    }

    /**
     * Creates an invoice for the order.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function createInvoice($spaceId, $transactionId, Mage_Sales_Model_Order $order)
    {
        $invoice = $order->prepareInvoice();
        $invoice->setPostfinancecheckoutAllowCreation(true);
        $invoice->register();
        $invoice->setTransactionId($spaceId . '_' . $transactionId);
        $invoice->save();
        return $invoice;
    }
}