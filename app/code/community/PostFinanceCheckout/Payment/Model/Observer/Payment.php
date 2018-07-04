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
 * The observer handles payment related events.
 */
class PostFinanceCheckout_Payment_Model_Observer_Payment
{

    /**
     * Stores the invoice during a capture request.
     *
     * This is necessary to be able to collect the line items for partial captures.
     *
     * @param Varien_Event_Observer $observer
     */
    public function capturePayment(Varien_Event_Observer $observer)
    {
        Mage::unregister('postfinancecheckout_payment_capture_invoice');
        Mage::register('postfinancecheckout_payment_capture_invoice', $observer->getInvoice());
    }

    /**
     * Ensures that an invoice with pending capture cannot be cancelled and that the order state is set correctly after cancelling an invoice.
     *
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Exception
     */
    public function cancelInvoice(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getInvoice();

        /* @var Mage_Sales_Model_Order $order */
        $order = $invoice->getOrder();

        // Skip the following checks if the order's payment method is not by PostFinance Checkout.
        if (! ($order->getPayment()->getMethodInstance() instanceof PostFinanceCheckout_Payment_Model_Payment_Method_Abstract)) {
            return;
        }

        // If there is a pending capture, the invoice cannot be cancelled.
        if ($invoice->getPostfinancecheckoutCapturePending()) {
            Mage::throwException('The invoice cannot be cancelled as it\'s capture has already been requested.');
        }

        // This allows to skip the following checks in certain situations.
        if ($order->getPostfinancecheckoutPaymentInvoiceAllowManipulation()) {
            return;
        }

        // The invoice can only be cancelled by the merchant if the transaction is in state 'AUTHORIZED'.
        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        $transaction = $transactionService->getTransaction($order->getPostfinancecheckoutSpaceId(), $order->getPostfinancecheckoutTransactionId());
        if ($transaction->getState() != \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED) {
            Mage::throwException(Mage::helper('postfinancecheckout_payment')->__('The invoice cannot be cancelled.'));
        }

        // Make sure the order is in the correct state after the invoice has been cancelled.
        $methodInstance = $order->getPayment()->getMethodInstance();
        if ($methodInstance instanceof PostFinanceCheckout_Payment_Model_Payment_Method_Abstract) {
            /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $transactionInfo */
            $transactionInfo = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByOrder($order);
            if ($transactionInfo->getState() != \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::DERECOGNIZED) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'processing_postfinancecheckout');
            }
        }
    }

    /**
     * Ensures that an invoice can only be created if possible.
     *
     * - Only one uncancelled invoice can exist per order.
     * - The transaction has to be in state authorized.
     *
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Exception
     */
    public function registerInvoice(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = $observer->getInvoice();

        /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        // Skip the following checks if the order's payment method is not by PostFinance Checkout.
        if (! ($order->getPayment()->getMethodInstance() instanceof PostFinanceCheckout_Payment_Model_Payment_Method_Abstract)) {
            return;
        }

        // Allow creating the invoice if there is no existing one for the order.
        if ($order->getInvoiceCollection()->count() == 1) {
            return;
        }

        // Only allow to create a new invoice if all previous invoices of the order have been cancelled.
        if (! $this->canCreateInvoice($order)) {
            Mage::throwException(Mage::helper('postfinancecheckout_payment')->__('Only one invoice is allowed. To change the invoice, cancel the existing one first.'));
        }

        if ($invoice->getPostfinancecheckoutCapturePending()) {
            return;
        }

        $invoice->setTransactionId($order->getPostfinancecheckoutSpaceId() . '_' . $order->getPostfinancecheckoutTransactionId());

        // This allows to skip the following checks in certain situations.
        if ($order->getPostfinancecheckoutPaymentInvoiceAllowManipulation()) {
            return;
        }

        // The invoice can only be created by the merchant if the transaction is in state 'AUTHORIZED'.
        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        $transaction = $transactionService->getTransaction($order->getPostfinancecheckoutSpaceId(), $order->getPostfinancecheckoutTransactionId());
        if ($transaction->getState() != \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED) {
            Mage::throwException(Mage::helper('postfinancecheckout_payment')->__('The invoice cannot be created.'));
        }

        // Completes the transaction on the gateway if necessary, otherwise just update the line items.
        if ($invoice->getPostfinancecheckoutPaymentNeedsCapture()) {
            $order->getPayment()
                ->getMethodInstance()
                ->complete($order->getPayment(), $invoice, $invoice->getGrandTotal());
        } else {
            /* @var PostFinanceCheckout_Payment_Model_Service_LineItem $lineItemCollection */
            $lineItemCollection = Mage::getSingleton('postfinancecheckout_payment/service_lineItem');
            $lineItems = $lineItemCollection->collectInvoiceLineItems($invoice, $invoice->getGrandTotal());
            $transactionService->updateLineItems($order->getPostfinancecheckoutSpaceId(), $order->getPostfinancecheckoutTransactionId(), $lineItems);
        }
    }

    /**
     * Activates the quote after creating the order to handle the user going back in the browser history correctly.
     *
     * Applies the charge flow to the order after it is placed.
     *
     * @param Varien_Event_Observer $observer
     */
    public function quoteSubmitSuccess(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        if ($order->getPayment()->getMethodInstance() instanceof PostFinanceCheckout_Payment_Model_Payment_Method_Abstract) {
            /* @var Mage_Sales_Model_Quote $quote */
            $quote = $observer->getQuote();
            $quote->setPostfinancecheckoutTransactionId(null);
            $quote->setIsActive(true)->setReservedOrderId(null);
        }

        // Apply a charge flow to the transaction after the order was created from the backend.
        if ($order->getPostfinancecheckoutChargeFlow() && Mage::app()->getStore()->isAdmin()) {
            /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
            $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
            $transaction = $transactionService->getTransaction($order->getPostfinancecheckoutSpaceId(), $order->getPostfinancecheckoutTransactionId());

            /* @var PostFinanceCheckout_Payment_Model_Service_ChargeFlow $chargeFlowService */
            $chargeFlowService = Mage::getSingleton('postfinancecheckout_payment/service_chargeFlow');
            $chargeFlowService->applyFlow($transaction);

            if ($order->getPostfinancecheckoutToken()) {
                /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
                $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
                $transactionService->waitForTransactionState(
                    $order, array(
                    \PostFinanceCheckout\Sdk\Model\TransactionState::CONFIRMED,
                    \PostFinanceCheckout\Sdk\Model\TransactionState::PENDING,
                    \PostFinanceCheckout\Sdk\Model\TransactionState::PROCESSING
                    )
                );
            }
        }
    }

    /**
     * Reset the payment information in the quote.
     *
     * @param Varien_Event_Observer $observer
     */
    public function convertOrderToQuote(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        /* @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();

        if ($order->getPayment()->getMethodInstance() instanceof PostFinanceCheckout_Payment_Model_Payment_Method_Abstract) {
            $quote->setPostfinancecheckoutTransactionId(null);
        }
    }

    /**
     * Returns whether an invoice can be created for the given order, i.e.
     * there is no existing uncancelled invoice.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    private function canCreateInvoice(Mage_Sales_Model_Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getId() && $invoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
                return false;
            }
        }

        return true;
    }
}