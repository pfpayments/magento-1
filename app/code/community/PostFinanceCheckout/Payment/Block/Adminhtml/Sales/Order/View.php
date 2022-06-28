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
 * This block displays a note that there is a pending refund for the order.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Abstract
{

    /**
     * Returns whether there is a pending refund for the order.
     *
     * @return boolean
     */
    public function hasPendingRefund()
    {
        /* @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('sales_order');

        /* @var PostFinanceCheckout_Payment_Model_Entity_RefundJob $existingRefundJob */
        $existingRefundJob = Mage::getModel('postfinancecheckout_payment/entity_refundJob');
        $existingRefundJob->loadByOrder($order);
        return $existingRefundJob->getId() > 0;
    }

    /**
     * Returns the URL to send the refund request to the gateway.
     *
     * @return string
     */
    public function getRefundUrl()
    {
        /* @var Mage_Sales_Model_Order $order */
        $order = Mage::registry('sales_order');
        return $this->getUrl('adminhtml/postfinancecheckout_transaction/refund',
            array(
                'order_id' => $order->getId()
            ));
    }
}