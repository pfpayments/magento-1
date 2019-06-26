<?php
use PostFinanceCheckout\Sdk\Model\TransactionState;

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
 * Abstract model for PostFinance Checkout payment methods.
 */
class PostFinanceCheckout_Payment_Model_Payment_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{

    protected $_paymentMethodConfigurationId;

    /**
     * The payment method configuration.
     *
     * @var PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration
     */
    protected $_paymentMethodConfiguration;

    protected $_formBlockType = 'postfinancecheckout_payment/payment_form';

    protected $_infoBlockType = 'postfinancecheckout_payment/payment_info';

    protected $_isGateway = true;

    protected $_canAuthorize = false;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = true;

    protected $_canVoid = true;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_canUseForMultishipping = false;

    protected $_isInitializeNeeded = true;

    protected $_canFetchTransactionInfo = false;

    protected $_canReviewPayment = true;

    protected $_canManageRecurringProfiles = false;

    /**
     * Returns the id of the payment method configuration.
     *
     * @return int
     */
    public function getPaymentMethodConfigurationId()
    {
        return $this->_paymentMethodConfigurationId;
    }

    /**
     * Returns the payment method configuration.
     *
     * @return PostFinanceCheckout_Payment_Model_Entity_PaymentMethodConfiguration
     */
    public function getPaymentMethodConfiguration()
    {
        if ($this->_paymentMethodConfiguration == null) {
            $this->_paymentMethodConfiguration = Mage::getModel(
                'postfinancecheckout_payment/entity_paymentMethodConfiguration')->load(
                $this->_paymentMethodConfigurationId);
        }

        return $this->_paymentMethodConfiguration;
    }

    public function canVoid(Varien_Object $payment)
    {
        if (! parent::canVoid($payment)) {
            return false;
        }

        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        try {
            $transaction = $transactionService->getTransaction(
                $payment->getOrder()
                    ->getPostfinancecheckoutSpaceId(),
                $payment->getOrder()
                    ->getPostfinancecheckoutTransactionId());
            if ($transaction instanceof \PostFinanceCheckout\Sdk\Model\Transaction) {
                return $transaction->getState() == TransactionState::AUTHORIZED;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function canRefund()
    {
        if (! parent::canRefund()) {
            return false;
        }

        return ! $this->hasExistingRefundJob($this->getInfoInstance()
            ->getOrder());
    }

    public function canRefundPartialPerInvoice()
    {
        if (! parent::canRefundPartialPerInvoice()) {
            return false;
        }

        return ! $this->hasExistingRefundJob($this->getInfoInstance()
            ->getOrder());
    }

    /**
     * Returns whether there is an existing refund job for the given order.
     *
     * @param int|Mage_Sales_Model_Order $order
     * @return boolean
     */
    protected function hasExistingRefundJob($order)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_RefundJob $existingRefundJob */
        $existingRefundJob = Mage::getModel('postfinancecheckout_payment/entity_refundJob');
        $existingRefundJob->loadByOrder($order);
        return $existingRefundJob->getId() > 0;
    }

    /**
     * Returns whether this payment method can be used with the given quote.
     *
     * It will perform an online check on PostFinance Checkout.
     *
     * @see Mage_Payment_Model_Method_Abstract::isAvailable()
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        if (! $isAvailable) {
            return false;
        }

        if ($quote != null && $quote->getGrandTotal() < 0.0001) {
            return false;
        }

        $client = $this->getHelper()->getApiClient(true);
        if ($quote != null && $client) {
            $spaceId = $quote->getStore()->getConfig('postfinancecheckout_payment/general/space_id');
            if ($spaceId) {
                /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
                $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
                try {
                    $possiblePaymentMethods = $transactionService->getPossiblePaymentMethods($quote);
                    $paymentMethodPossible = false;
                    foreach ($possiblePaymentMethods as $possiblePaymentMethod) {
                        if ($possiblePaymentMethod->getId() ==
                            $this->getPaymentMethodConfiguration()->getConfigurationId()) {
                            $paymentMethodPossible = true;
                            break;
                        }
                    }

                    if (! $paymentMethodPossible) {
                        return false;
                    }
                } catch (Exception $e) {
                    Mage::log(
                        'The payment method ' . $this->getTitle() . ' is not available because of an exception: ' .
                        $e->getMessage(), null, 'postfinancecheckout.log');
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Initializes the payment.
     *
     * An invoice is created and the transaction updated to match the order.
     *
     * The order state will be set to {@link Mage_Sales_Model_Order::STATE_PENDING_PAYMENT}.
     *
     * @see Mage_Payment_Model_Method_Abstract::initialize()
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();

        /* @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $this->setCheckoutOrder($order);

        $order->setCanSendNewEmailFlag(false);
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        /* @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->setStore($order->getStore())
            ->load($order->getQuoteId());
        $this->setCheckoutQuote($quote);

        $invoice = $this->createInvoice($quote->getPostfinancecheckoutSpaceId(),
            $quote->getPostfinancecheckoutTransactionId(), $order);
        $this->setCheckoutInvoice($invoice);

        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        $order->getResource()->addCommitCallback(array(
            $this,
            'onOrderCreation'
        ));
    }

    public function onOrderCreation()
    {
        $order = Mage::getModel('sales/order')->load($this->getCheckoutOrder()
            ->getId());
        $quote = $this->getCheckoutQuote();

        $token = null;
        if (Mage::app()->getStore()->isAdmin()) {
            $tokenInfoId = $quote->getPayment()->getData('postfinancecheckout_token');
            if ($tokenInfoId) {
                /* @var PostFinanceCheckout_Payment_Model_Entity_TokenInfo $tokenInfo */
                $tokenInfo = Mage::getModel('postfinancecheckout_payment/entity_tokenInfo')->load($tokenInfoId);
                $token = new \PostFinanceCheckout\Sdk\Model\Token();
                $token->setId($tokenInfo->getTokenId());
            }
        }

        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');

        if ($order->getPostfinancecheckoutSpaceId() != null ||
            $order->getPostfinancecheckoutTransactionId() != null) {
            Mage::throwException(
                $this->getHelper()->__('The PostFinance Checkout payment transaction has already been set on the order.'));
        }

        $this->getCheckoutOrder()->setPostfinancecheckoutSpaceId($quote->getPostfinancecheckoutSpaceId());
        $this->getCheckoutOrder()->setPostfinancecheckoutTransactionId(
            $quote->getPostfinancecheckoutTransactionId());
        $order->setPostfinancecheckoutSpaceId($quote->getPostfinancecheckoutSpaceId());
        $order->setPostfinancecheckoutTransactionId($quote->getPostfinancecheckoutTransactionId());
        $order->save();

        if (! $this->checkTransactionInfo($order)) {
            return;
        }

        $transaction = $transactionService->getTransaction($quote->getPostfinancecheckoutSpaceId(),
            $quote->getPostfinancecheckoutTransactionId());
        $transactionService->updateTransactionInfo($transaction, $order);

        $transaction = $transactionService->confirmTransaction($transaction, $order, $this->getCheckoutInvoice(),
            Mage::app()->getStore()
                ->isAdmin(), $token);
        $transactionService->updateTransactionInfo($transaction, $order);

        if (Mage::app()->getStore()->isAdmin()) {
            // Set the selected token on the order and tell it to apply the charge flow after it is saved.
            $this->getCheckoutOrder()->setPostfinancecheckoutChargeFlow(true);
            $this->getCheckoutOrder()->setPostfinancecheckoutToken($token);
        }
    }

    /**
     * Checks whether the transaction info for the transaction linked to the order is already linked to another order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    protected function checkTransactionInfo(Mage_Sales_Model_Order $order)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $info */
        $info = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByTransaction(
            $order->getPostfinancecheckoutSpaceId(), $order->getPostfinancecheckoutTransactionId());
        if ($info->getId() && $info->getOrderId() != $order->getId()) {
            return false;
        } else {
            return true;
        }
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
        $invoice->register();
        $invoice->setTransactionId($spaceId . '_' . $transactionId);

        /* @var Mage_Core_Model_Resource_Transaction $transactionSave */
        $transactionSave = Mage::getModel('core/resource_transaction');
        $transactionSave->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();
        return $invoice;
    }

    /**
     * Accepts the payment by marking the delivery indication as suitable.
     *
     * @see Mage_Payment_Model_Method_Abstract::acceptPayment()
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        Mage::getSingleton('postfinancecheckout_payment/service_deliveryIndication')->markAsSuitable($payment);
        return true;
    }

    /**
     * Denies the payment by marking the delivery indication as not suitable.
     *
     * @see Mage_Payment_Model_Method_Abstract::denyPayment()
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);
        Mage::getSingleton('postfinancecheckout_payment/service_deliveryIndication')->markAsNotSuitable($payment);
        $payment->getOrder()->setPostfinancecheckoutPaymentInvoiceAllowManipulation(true);
        return true;
    }

    /**
     * Voids the payment.
     *
     * @see Mage_Payment_Model_Method_Abstract::void()
     */
    public function void(Varien_Object $payment)
    {
        parent::void($payment);
        $this->voidOnline($payment);
        return $this;
    }

    /**
     * Cancels the payment.
     *
     * @see Mage_Payment_Model_Method_Abstract::cancel()
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);

        $order = $payment->getOrder();
        if ($order->getPostfinancecheckoutDerecognized()) {
            return $this;
        }

        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        try {
            $transaction = $transactionService->getTransaction($order->getPostfinancecheckoutSpaceId(),
                $order->getPostfinancecheckoutTransactionId());
            if (! ($transaction instanceof \PostFinanceCheckout\Sdk\Model\Transaction)) {
                Mage::throwException($this->getHelper()->__('The transaction linked to the order could not be loaded.'));
            }
        } catch (Exception $e) {
            Mage::throwException($this->getHelper()->__('The transaction linked to the order could not be loaded.'));
        }

        if ($transaction->getState() == TransactionState::AUTHORIZED) {
            $this->voidOnline($payment);
        } else {
            $this->refundCancelledOrder($payment);
        }
        return $this;
    }

    /**
     * Voids the transaction online.
     *
     * @param Varien_Object $payment
     * @throws Mage_Core_Exception
     */
    protected function voidOnline(Varien_Object $payment)
    {
        /* @var \PostFinanceCheckout\Sdk\Model\TransactionVoid $void */
        $void = Mage::getSingleton('postfinancecheckout_payment/service_void')->void($payment);
        if ($void->getState() == \PostFinanceCheckout\Sdk\Model\TransactionVoidState::FAILED) {
            Mage::throwException($this->getHelper()->__('The void of the payment failed on the gateway.'));
        }
    }

    /**
     * Refunds the transaction online in case the order has been cancelled.
     *
     * @param Varien_Object $payment
     * @throws Mage_Core_Exception
     */
    protected function refundCancelledOrder(Varien_Object $payment)
    {
        $order = $payment->getOrder();

        $this->checkExistingRefundJob($order);

        /* @var PostFinanceCheckout_Payment_Model_Service_Refund $refundService */
        $refundService = Mage::getSingleton('postfinancecheckout_payment/service_refund');
        $refund = $refundService->createForPayment($payment);

        $refundJob = $this->createRefundJob($order, $refund);

        try {
            $refund = $refundService->refund($refundJob->getSpaceId(), $refund);
        } catch (\PostFinanceCheckout\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \PostFinanceCheckout\Sdk\Model\ClientError) {
                $refundJob->delete();
                Mage::throwException($e->getResponseObject()->getMessage());
            } else {
                Mage::throwException(
                    $this->getHelper()->__('There has been an error while sending the refund to the gateway.'));
            }
        } catch (Exception $e) {
            Mage::throwException(
                $this->getHelper()->__('There has been an error while sending the refund to the gateway.'));
        }

        if ($refund->getState() == \PostFinanceCheckout\Sdk\Model\RefundState::FAILED) {
            $refundJob->delete();
            Mage::throwException($this->getHelper()->translate($refund->getFailureReason()
                ->getDescription()));
        } elseif ($refund->getState() == \PostFinanceCheckout\Sdk\Model\RefundState::PENDING) {
            Mage::throwException(
                $this->getHelper()->__('The refund was requested successfully, but is still pending on the gateway.'));
        }

        $refundJob->delete();
    }

    /**
     * Captures the payment with the given amount.
     *
     * @see Mage_Payment_Model_Method_Abstract::capture()
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        /* @var Mage_Sales_Model_Order_Invoice $invoice */
        $invoice = Mage::registry('postfinancecheckout_payment_capture_invoice');

        if ($invoice->getPostfinancecheckoutCapturePending()) {
            Mage::throwException(
                $this->getHelper()->__(
                    'The capture has already been requested but could not be completed yet. The invoice will be updated, as soon as the capture is done.'));
        }

        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        try {
            $transaction = $transactionService->getTransaction(
                $payment->getOrder()
                    ->getPostfinancecheckoutSpaceId(),
                $payment->getOrder()
                    ->getPostfinancecheckoutTransactionId());
            if (! ($transaction instanceof \PostFinanceCheckout\Sdk\Model\Transaction)) {
                Mage::throwException($this->getHelper()->__('The transaction linked to the order could not be loaded.'));
            }
        } catch (Exception $e) {
            Mage::throwException($this->getHelper()->__('The transaction linked to the order could not be loaded.'));
        }

        if ($transaction->getState() == TransactionState::AUTHORIZED) {
            if ($invoice->getId()) {
                $this->complete($payment, $invoice, $amount);
            } else {
                $invoice->setPostfinancecheckoutPaymentNeedsCapture(true);
            }
        } else {
            Mage::throwException($this->getHelper()->__('The invoice cannot be captured manually.'));
        }

        return $this;
    }

    /**
     * Complete the transaction.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param float $amount
     */
    public function complete(Mage_Sales_Model_Order_Payment $payment, Mage_Sales_Model_Order_Invoice $invoice, $amount)
    {
        $this->updateLineItems($payment, $invoice, $amount);

        /* @var \PostFinanceCheckout\Sdk\Model\TransactionCompletion $completion */
        $completion = Mage::getSingleton('postfinancecheckout_payment/service_transactionCompletion')->complete(
            $payment);
        if ($completion->getState() == \PostFinanceCheckout\Sdk\Model\TransactionCompletionState::FAILED) {
            Mage::throwException($this->getHelper()->__('The capture of the invoice failed on the gateway.'));
        }

        /* @var \PostFinanceCheckout\Sdk\Model\TransactionInvoice $transactionInvoice */
        $transactionInvoice = Mage::getSingleton('postfinancecheckout_payment/service_transactionInvoice')->getTransactionInvoiceByCompletion(
            $completion->getLinkedSpaceId(), $completion->getId());
        if ($transactionInvoice->getState() != \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::PAID &&
            $transactionInvoice->getState() != \PostFinanceCheckout\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE) {
            $invoice->setPostfinancecheckoutCapturePending(true);
        }

        $authTransaction = $payment->getAuthorizationTransaction();
        $authTransaction->close(false);
        $invoice->getOrder()->addRelatedObject($authTransaction);
    }

    /**
     * Updates the transaction's line items.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param number $amount
     */
    protected function updateLineItems(Mage_Sales_Model_Order_Payment $payment, Mage_Sales_Model_Order_Invoice $invoice,
        $amount)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $transactionInfo */
        $transactionInfo = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByOrder(
            $payment->getOrder());
        if ($transactionInfo->getState() == \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED) {
            /* @var PostFinanceCheckout_Payment_Model_Service_LineItem $lineItemCollection */
            $lineItemCollection = Mage::getSingleton('postfinancecheckout_payment/service_lineItem');
            $lineItems = $lineItemCollection->collectInvoiceLineItems($invoice, $amount);

            /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
            $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
            $transactionService->updateLineItems($payment->getOrder()
                ->getPostfinancecheckoutSpaceId(), $payment->getOrder()
                ->getPostfinancecheckoutTransactionId(), $lineItems);
        }
    }

    /**
     * Refunds the payment with the given amount.
     *
     * @see Mage_Payment_Model_Method_Abstract::refund()
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        /* @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();

        $this->checkExistingRefundJob($creditmemo->getOrder());

        /* @var PostFinanceCheckout_Payment_Model_Service_Refund $refundService */
        $refundService = Mage::getSingleton('postfinancecheckout_payment/service_refund');
        $refund = $refundService->create($payment, $creditmemo);

        $refundJob = $this->createRefundJob($creditmemo->getOrder(), $refund);

        try {
            $refund = $refundService->refund($refundJob->getSpaceId(), $refund);
        } catch (\PostFinanceCheckout\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \PostFinanceCheckout\Sdk\Model\ClientError) {
                $refundJob->delete();
                Mage::throwException($e->getResponseObject()->getMessage());
            } else {
                Mage::throwException(
                    $this->getHelper()->__('There has been an error while sending the refund to the gateway.'));
            }
        } catch (Exception $e) {
            Mage::throwException(
                $this->getHelper()->__('There has been an error while sending the refund to the gateway.'));
        }

        if ($refund->getState() == \PostFinanceCheckout\Sdk\Model\RefundState::FAILED) {
            $refundJob->delete();
            Mage::throwException($this->getHelper()->translate($refund->getFailureReason()
                ->getDescription()));
        } elseif ($refund->getState() == \PostFinanceCheckout\Sdk\Model\RefundState::PENDING) {
            Mage::throwException(
                $this->getHelper()->__('The refund was requested successfully, but is still pending on the gateway.'));
        }

        $creditmemo->setPostfinancecheckoutExternalId($refund->getExternalId());
        $refundJob->delete();

        return $this;
    }

    /**
     * Creates a new refund job for the given order and refund.
     *
     * @param Mage_Sales_Model_Order $order
     * @param \PostFinanceCheckout\Sdk\Model\RefundCreate $refund
     * @return PostFinanceCheckout_Payment_Model_Entity_RefundJob
     */
    protected function createRefundJob(Mage_Sales_Model_Order $order,
        \PostFinanceCheckout\Sdk\Model\RefundCreate $refund)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_RefundJob $refundJob */
        $refundJob = Mage::getModel('postfinancecheckout_payment/entity_refundJob');
        $refundJob->setOrderId($order->getId());
        $refundJob->setSpaceId($order->getPostfinancecheckoutSpaceId());
        $refundJob->setExternalId($refund->getExternalId());
        $refundJob->setRefund($refund);
        $refundJob->save();
        return $refundJob;
    }

    /**
     * Checks if there is an existing refund job for the given order.
     *
     * If yes, the refund is sent to the gateway and an exception is thrown.
     *
     * @param Mage_Sales_Model_Order $order
     * @throws Mage_Core_Exception
     */
    protected function checkExistingRefundJob(Mage_Sales_Model_Order $order)
    {
        /* @var PostFinanceCheckout_Payment_Model_Entity_RefundJob $existingRefundJob */
        $existingRefundJob = Mage::getModel('postfinancecheckout_payment/entity_refundJob');
        $existingRefundJob->loadByOrder($order);
        if ($existingRefundJob->getId() > 0) {
            try {
                /* @var PostFinanceCheckout_Payment_Model_Service_Refund $refundService */
                $refundService = Mage::getSingleton('postfinancecheckout_payment/service_refund');
                $refundService->refund($existingRefundJob->getSpaceId(), $existingRefundJob->getRefund());
            } catch (Exception $e) {
                Mage::log('Failed to refund because of an exception: ' . $e->getMessage(), null,
                    'postfinancecheckout.log');
            }

            Mage::throwException(
                $this->getHelper()->__(
                    'As long as there is an open creditmemo for the order, no new creditmemo can be created.'));
        }
    }

    /**
     * Processes the invoice.
     *
     * Ensures that the invoice is not set as paid if the capture is pending.
     *
     * @see Mage_Payment_Model_Method_Abstract::processInvoice()
     */
    public function processInvoice($invoice, $payment)
    {
        parent::processInvoice($invoice, $payment);

        if ($invoice->getPostfinancecheckoutCapturePending()) {
            $invoice->setIsPaid(false);
        }

        return $this;
    }

    /**
     * Restores the quote after the payment has been cancelled.
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function fail(Mage_Sales_Model_Order $order)
    {
        /* @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        /* @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setPostfinancecheckoutTransactionId(null);
            $quote->setIsActive(1)
                ->setReservedOrderId(NULL)
                ->save();
            $session->replaceQuote($quote);
        }

        $session->unsLastRealOrderId();
        $session->replaceQuote($quote);

        /* @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        /* @var PostFinanceCheckout_Payment_Model_Service_Transaction $transactionService */
        $transactionService = Mage::getSingleton('postfinancecheckout_payment/service_transaction');
        try {
            $transaction = $transactionService->getTransaction($order->getPostfinancecheckoutSpaceId(),
                $order->getPostfinancecheckoutTransactionId());
            if ($transaction instanceof \PostFinanceCheckout\Sdk\Model\Transaction &&
                $transaction->getUserFailureMessage() != null) {
                $coreSession->addError($transaction->getUserFailureMessage());
                return;
            }
        } catch (Exception $e) {
            Mage::log(
                'Failed to get the failure message from the transaction because of an exception: ' . $e->getMessage(),
                null, 'postfinancecheckout.log');
        }

        $coreSession->addError(
            $this->getHelper()
                ->__('The payment process could not have been finished successfully.'));
    }

    /**
     * Redirects the customer to the redirection controller action.
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('postfinancecheckout/transaction/redirect', array(
            '_secure' => true
        ));
    }

    /**
     * Returns the data helper.
     *
     * @return PostFinanceCheckout_Payment_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('postfinancecheckout_payment');
    }
}