<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout/).
 *
 * @package PostFinanceCheckout_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * Webhook processor to handle transaction state transitions.
 */
class PostFinanceCheckout_Payment_Model_Webhook_Transaction extends PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated
{

    /**
     *
     * @see PostFinanceCheckout_Payment_Model_Webhook_AbstractOrderRelated::loadEntity()
     * @return \PostFinanceCheckout\Sdk\Model\Transaction
     */
    protected function loadEntity(PostFinanceCheckout_Payment_Model_Webhook_Request $request)
    {
        $transactionService = new \PostFinanceCheckout\Sdk\Service\TransactionService(
            Mage::helper('postfinancecheckout_payment')->getApiClient());
        return $transactionService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getTransactionId($transaction)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\Transaction $transaction */
        return $transaction->getId();
    }

    protected function processOrderRelatedInner(Mage_Sales_Model_Order $order, $transaction)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\Transaction $transaction */
        /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $transactionInfo */
        $transactionInfo = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByOrder($order);
        if ($transaction->getState() != $transactionInfo->getState()) {
            switch ($transaction->getState()) {
                case \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED:
                case \PostFinanceCheckout\Sdk\Model\TransactionState::COMPLETED:
                    $this->authorize($transaction, $order);
                    break;
                case \PostFinanceCheckout\Sdk\Model\TransactionState::DECLINE:
                    $this->authorize($transaction, $order);
                    $this->decline($transaction, $order);
                    break;
                case \PostFinanceCheckout\Sdk\Model\TransactionState::FAILED:
                    $this->failed($transaction, $order);
                    break;
                case \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL:
                    $this->authorize($transaction, $order);
                    $this->fulfill($transaction, $order);
                    break;
                case \PostFinanceCheckout\Sdk\Model\TransactionState::VOIDED:
                    $this->authorize($transaction, $order);
                    $this->voided($transaction, $order);
                    break;
                default:
                    // Nothing to do.
                    break;
            }
        }

        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionStoreService */
        $transactionStoreService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        $transactionStoreService->updateTransactionInfo($transaction, $order);
    }

    protected function authorize(\PostFinanceCheckout\Sdk\Model\Transaction $transaction,
        Mage_Sales_Model_Order $order)
    {
        if (! $order->getPostfinancecheckoutAuthorized()) {
            $order->getPayment()
                ->setTransactionId($transaction->getLinkedSpaceId() . '_' . $transaction->getId())
                ->setIsTransactionClosed(false);
            $order->getPayment()->registerAuthorizationNotification($transaction->getAuthorizationAmount());
            $this->sendOrderEmail($order);
            if ($transaction->getState() != \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL) {
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'processing_postfinancecheckout',
                    Mage::helper('postfinancecheckout_payment')->__(
                        'The order should not be fulfilled yet, as the payment is not guaranteed.'));
            }
            $order->setPostfinancecheckoutAuthorized(true);
            $order->save();
            try {
                $this->updateShopCustomer($transaction, $order);
            } catch (Exception $e) {
                // Try to update the customer, ignore if it fails.
                Mage::log('Failed to update the customer: ' . $e->getMessage(), null, 'postfinancecheckout.log');
            }
        }
    }

    protected function decline(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order)
    {
        if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            $order->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
            $order->getPayment()->setNotificationResult(true);
            $order->getPayment()->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
        }

        $order->save();
    }

    protected function failed(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order)
    {
        $invoice = $this->getInvoiceForTransaction($transaction->getLinkedSpaceId(), $transaction->getId(), $order);
        if ($invoice != null && $invoice->canCancel()) {
            $order->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);
        }

        if (! $order->isCanceled()) {
            $order->registerCancellation(null, false)->save();
        } else {
            Mage::log('Tried to cancel the order ' . $order->getIncrementId() . ' but it was already cancelled.', null,
                'postfinancecheckout.log');
        }
    }

    protected function fulfill(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order)
    {
        if ($order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
            $order->getPayment()->setNotificationResult(true);
            $order->getPayment()->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_ACCEPT,
                false);
        } elseif ($order->getStatus() == 'processing_postfinancecheckout') {
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true,
                Mage::helper('postfinancecheckout_payment')->__('The order can be fulfilled now.'));
        }

        $order->save();
    }

    protected function voided(\PostFinanceCheckout\Sdk\Model\Transaction $transaction, Mage_Sales_Model_Order $order)
    {
        $order->getPayment()->registerVoidNotification();
        $invoice = $this->getInvoiceForTransaction($transaction->getLinkedSpaceId(), $transaction->getId(), $order);
        if ($invoice != null && $invoice->canCancel()) {
            $order->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
            $invoice->cancel();
            $order->addRelatedObject($invoice);
        }

        $order->save();
    }

    /**
     * Sends the order email if not already sent.
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function sendOrderEmail(Mage_Sales_Model_Order $order)
    {
        if ($order->getStore()->getConfig('postfinancecheckout_payment/email/order') &&
            $order->getPayment()
                ->getMethodInstance()
                ->getConfigData('order_email') && ! $order->getEmailSent()) {
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
            if (strpos($invoice->getTransactionId(), $spaceId . '_' . $transactionId) === 0 &&
                $invoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }

        return null;
    }

    protected function updateShopCustomer(\PostFinanceCheckout\Sdk\Model\Transaction $transaction,
        Mage_Sales_Model_Order $order)
    {
        if ($order->getCustomerIsGuest() || $order->getBillingAddress() == null ||
            ! $order->getBillingAddress()->getCustomerAddressId()) {
            return;
        }

        /* @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

        $billingAddress = $customer->getAddressById($order->getBillingAddress()
            ->getCustomerAddressId());

        $this->updateDateOfBirth($customer, $transaction);
        $this->updateSalutation($customer, $billingAddress, $transaction);
        $this->updateGender($customer, $transaction);
        $this->updateSalesTaxNumber($customer, $billingAddress, $transaction);
        $this->updateCompany($customer, $billingAddress, $transaction);

        $billingAddress->save();
        $customer->save();
    }

    protected function updateDateOfBirth(Mage_Customer_Model_Customer $customer,
        \PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        if ($customer->getDob() == null && $transaction->getBillingAddress()->getDateOfBirth() != null) {
            $customer->setDob($transaction->getBillingAddress()
                ->getDateOfBirth());
        }
    }

    protected function updateSalutation(Mage_Customer_Model_Customer $customer,
        Mage_Customer_Model_Address $billingAddress, \PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        if ($transaction->getBillingAddress()->getSalutation() != null) {
            if ($customer->getPrefix() == null) {
                $customer->setPrefix($transaction->getBillingAddress()
                    ->getSalutation());
            }

            if ($billingAddress->getPrefix() == null) {
                $billingAddress->setPrefix($transaction->getBillingAddress()
                    ->getSalutation());
            }
        }
    }

    protected function updateGender(Mage_Customer_Model_Customer $customer,
        \PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        if ($customer->getGender() == null && $transaction->getBillingAddress()->getGender() != null) {
            if ($transaction->getBillingAddress()->getGender() == \PostFinanceCheckout\Sdk\Model\Gender::MALE) {
                $customer->setGender(1);
            } elseif ($transaction->getBillingAddress()->getGender() == \PostFinanceCheckout\Sdk\Model\Gender::FEMALE) {
                $customer->setGender(2);
            }
        }
    }

    protected function updateSalesTaxNumber(Mage_Customer_Model_Customer $customer,
        Mage_Customer_Model_Address $billingAddress, \PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        if ($transaction->getBillingAddress()->getSalesTaxNumber() != null) {
            if ($customer->getTaxvat() == null) {
                $customer->setTaxvat($transaction->getBillingAddress()
                    ->getSalesTaxNumber());
            }

            if ($billingAddress->getVatId() == null) {
                $billingAddress->setVatId($transaction->getBillingAddress()
                    ->getSalesTaxNumber());
            }
        }
    }

    protected function updateCompany(Mage_Customer_Model_Customer $customer, Mage_Customer_Model_Address $billingAddress,
        \PostFinanceCheckout\Sdk\Model\Transaction $transaction)
    {
        if ($billingAddress->getCompany() == null && $transaction->getBillingAddress()->getOrganizationName() != null) {
            $billingAddress->setCompany($transaction->getBillingAddress()
                ->getOrganizationName());
        }
    }
}