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
 * The block renders the payment information.
 */
class PostFinanceCheckout_Payment_Block_Payment_Info extends Mage_Payment_Block_Info
{

    private $transaction = null;

    private $transactionInfo = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('postfinancecheckout/payment/info.phtml');
    }

    /**
     * Returns whether the payment information are to be displayed in the creditmemo detail view in the backend.
     *
     * @return boolean
     */
    public function isCreditmemo()
    {
        return Mage::app()->getStore()->isAdmin() && strstr($this->getRequest()->getControllerName(), 'creditmemo') !== false;
    }

    /**
     * Returns whether the payment information are to be displayed in the invoice detail view in the backend.
     *
     * @return boolean
     */
    public function isInvoice()
    {
        return Mage::app()->getStore()->isAdmin() && strstr($this->getRequest()->getControllerName(), 'invoice') !== false;
    }

    /**
     * Returns whether the payment information are to be displayed in the shipment detail view in the backend.
     *
     * @return boolean
     */
    public function isShipment()
    {
        return Mage::app()->getStore()->isAdmin() && strstr($this->getRequest()->getControllerName(), 'shipment') !== false;
    }

    /**
     * Returns whether the customer is allowed to download invoice documents.
     *
     * @return boolean
     */
    public function isCustomerDownloadInvoiceAllowed()
    {
        return $this->getInfo()->getOrder() != null && Mage::getStoreConfigFlag('postfinancecheckout_payment/document/customer_download_invoice', $this->getInfo()
            ->getOrder()
            ->getStore());
    }

    /**
     * Returns whether the customer is allowed to download packing slips.
     *
     * @return boolean
     */
    public function isCustomerDownloadPackingSlipAllowed()
    {
        return $this->getInfo()->getOrder() != null && Mage::getStoreConfigFlag('postfinancecheckout_payment/document/customer_download_packing_slip', $this->getInfo()
            ->getOrder()
            ->getStore());
    }

    /**
     * Returns the URL to update the transaction's information.
     *
     * @return string
     */
    public function getUpdateTransactionUrl()
    {
        if ($this->getTransactionInfo() && Mage::app()->getStore()->isAdmin()) {
            /* @var Mage_Adminhtml_Helper_Data $adminHelper */
            $adminHelper = Mage::helper('adminhtml');
            return $adminHelper->getUrl('adminhtml/postfinancecheckout_transaction/update', array(
                'transaction_id' => $this->getTransactionInfo()
                    ->getTransactionId(),
                'space_id' => $this->getTransactionInfo()
                    ->getSpaceId(),
                '_secure' => true
            ));
        }
    }

    /**
     * Returns the URL to download the transaction's invoice PDF document.
     *
     * @return string
     */
    public function getDownloadInvoiceUrl()
    {
        if (! $this->getTransactionInfo() || ! in_array($this->getTransactionInfo()->getState(), array(
            \PostFinanceCheckout\Sdk\Model\TransactionState::COMPLETED,
            \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL,
            \PostFinanceCheckout\Sdk\Model\TransactionState::DECLINE
        ))) {
            return false;
        }
        
        if (Mage::app()->getStore()->isAdmin()) {
            /* @var Mage_Adminhtml_Helper_Data $adminHelper */
            $adminHelper = Mage::helper('adminhtml');
            return $adminHelper->getUrl('adminhtml/postfinancecheckout_transaction/downloadInvoice', array(
                'transaction_id' => $this->getTransactionInfo()
                    ->getTransactionId(),
                'space_id' => $this->getTransactionInfo()
                    ->getSpaceId(),
                '_secure' => true
            ));
        } else {
            return $this->getUrl('postfinancecheckout/transaction/downloadInvoice', array(
                'order_id' => $this->getInfo()
                    ->getOrder()
                    ->getId()
            ));
        }
    }

    /**
     * Returns the URL to download the transaction's packing slip PDF document.
     *
     * @return string
     */
    public function getDownloadPackingSlipUrl()
    {
        if (! $this->getTransactionInfo() || $this->getTransactionInfo()->getState() != \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL) {
            return false;
        }
        
        if (Mage::app()->getStore()->isAdmin()) {
            /* @var Mage_Adminhtml_Helper_Data $adminHelper */
            $adminHelper = Mage::helper('adminhtml');
            return $adminHelper->getUrl('adminhtml/postfinancecheckout_transaction/downloadPackingSlip', array(
                'transaction_id' => $this->getTransactionInfo()
                    ->getTransactionId(),
                'space_id' => $this->getTransactionInfo()
                    ->getSpaceId(),
                '_secure' => true
            ));
        } else {
            return $this->getUrl('postfinancecheckout/transaction/downloadPackingSlip', array(
                'order_id' => $this->getInfo()
                    ->getOrder()
                    ->getId()
            ));
        }
    }

    /**
     * Returns the URL to download the refund PDF document.
     *
     * @return string
     */
    public function getDownloadRefundUrl()
    {
        /* @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = Mage::registry('current_creditmemo');
        if ($creditmemo == null || $creditmemo->getPostfinancecheckoutExternalId() == null) {
            return false;
        }
        
        /* @var Mage_Adminhtml_Helper_Data $adminHelper */
        $adminHelper = Mage::helper('adminhtml');
        return $adminHelper->getUrl('adminhtml/postfinancecheckout_transaction/downloadRefund', array(
            'external_id' => $creditmemo->getPostfinancecheckoutExternalId(),
            'space_id' => $this->getTransactionInfo()
                ->getSpaceId(),
            '_secure' => true
        ));
    }

    /**
     * Returns the transaction info.
     *
     * @return PostFinanceCheckout_Payment_Model_Entity_TransactionInfo
     */
    public function getTransactionInfo()
    {
        if ($this->transactionInfo === null) {
            if ($this->getInfo() instanceof Mage_Sales_Model_Order_Payment) {
                /* @var PostFinanceCheckout_Payment_Model_Entity_TransactionInfo $transactionInfo */
                $transactionInfo = Mage::getModel('postfinancecheckout_payment/entity_transactionInfo')->loadByOrder($this->getInfo()
                    ->getOrder());
                if ($transactionInfo->getId()) {
                    $this->transactionInfo = $transactionInfo;
                } else {
                    $this->transactionInfo = false;
                }
            } else {
                $this->transactionInfo = false;
            }
        }
        
        return $this->transactionInfo;
    }

    /**
     * Returns the URL to the payment method image.
     *
     * @return string
     */
    public function getImageUrl()
    {
        /* @var PostFinanceCheckout_Payment_Model_Payment_Method_Abstract $methodInstance */
        $methodInstance = $this->getMethod();
        $spaceId = $methodInstance->getPaymentMethodConfiguration()->getSpaceId();
        $spaceViewId = $this->getTransactionInfo() ? $this->getTransactionInfo()->getSpaceViewId() : null;
        $language = $this->getTransactionInfo() ? $this->getTransactionInfo()->getLanguage() : null;
        /* @var PostFinanceCheckout_Payment_Helper_Data $helper */
        $helper = $this->helper('postfinancecheckout_payment');
        return $helper->getResourceUrl($methodInstance->getPaymentMethodConfiguration()
            ->getImage(), $language, $spaceId, $spaceViewId);
    }

    /**
     * Returns the URL to the transaction detail view in PostFinance Checkout.
     *
     * @return string
     */
    public function getTransactionUrl()
    {
        return Mage::helper('postfinancecheckout_payment')->getBaseGatewayUrl() . '/s/' . $this->getTransactionInfo()->getSpaceId() . '/payment/transaction/view/' . $this->getTransactionInfo()->getTransactionId();
    }

    /**
     * Returns the translated name of the transaction's state.
     *
     * @return string
     */
    public function getTransactionState()
    {
        /* @var PostFinanceCheckout_Payment_Helper_Data $helper */
        $helper = $this->helper('postfinancecheckout_payment');
        switch ($this->getTransactionInfo()->getState()) {
            case \PostFinanceCheckout\Sdk\Model\TransactionState::AUTHORIZED:
                return $helper->__('Authorized');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::COMPLETED:
                return $helper->__('Completed');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::CONFIRMED:
                return $helper->__('Confirmed');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::DECLINE:
                return $helper->__('Decline');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::FAILED:
                return $helper->__('Failed');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::FULFILL:
                return $helper->__('Fulfill');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::PENDING:
                return $helper->__('Pending');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::PROCESSING:
                return $helper->__('Processing');
            case \PostFinanceCheckout\Sdk\Model\TransactionState::VOIDED:
                return $helper->__('Voided');
            default:
                return $helper->__('Unknown State');
        }
    }

    /**
     * Returns the transaction's currency.
     *
     * @return Mage_Directory_Model_Currency
     */
    public function getTransactionCurrency()
    {
        return Mage::getModel('directory/currency')->load($this->getTransactionInfo()
            ->getCurrency());
    }

    /**
     * Returns the charge attempt's labels by their groups.
     *
     * @return \PostFinanceCheckout\Sdk\Model\Label[]
     */
    public function getGroupedChargeAttemptLabels()
    {
        if ($this->getTransactionInfo()) {
            /* @var PostFinanceCheckout_Payment_Model_Provider_LabelDescriptor $labelDescriptorProvider */
            $labelDescriptorProvider = Mage::getSingleton('postfinancecheckout_payment/provider_labelDescriptor');
            
            /* @var PostFinanceCheckout_Payment_Model_Provider_LabelDescriptorGroup $labelDescriptorGroupProvider */
            $labelDescriptorGroupProvider = Mage::getSingleton('postfinancecheckout_payment/provider_labelDescriptorGroup');
            
            $labelsByGroupId = array();
            foreach ($this->getTransactionInfo()->getLabels() as $descriptorId => $value) {
                $descriptor = $labelDescriptorProvider->find($descriptorId);
                if ($descriptor) {
                    $labelsByGroupId[$descriptor->getGroup()][] = array(
                        'descriptor' => $descriptor,
                        'value' => $value
                    );
                }
            }
            
            $labelsByGroup = array();
            foreach ($labelsByGroupId as $groupId => $labels) {
                $group = $labelDescriptorGroupProvider->find($groupId);
                if ($group) {
                    usort($labels, function ($a, $b) {
                        return $a['descriptor']->getWeight() - $b['descriptor']->getWeight();
                    });
                    $labelsByGroup[] = array(
                        'group' => $group,
                        'labels' => $labels
                    );
                }
            }
            
            usort($labelsByGroup, function ($a, $b) {
                return $a['group']->getWeight() - $b['group']->getWeight();
            });
            return $labelsByGroup;
        } else {
            return array();
        }
    }
}