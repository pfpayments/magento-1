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
 * This block displays a note that the invoice is in a pending capture state.
 */
class PostFinanceCheckout_Payment_Block_Adminhtml_Sales_Order_Invoice_View extends Mage_Adminhtml_Block_Abstract
{

    /**
     * Returns whether the invoice is in a pending capture state.
     *
     * @return boolean
     */
    public function isInvoicePending()
    {
        $invoice = Mage::registry('current_invoice');
        return $invoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_PAID &&
            $invoice->getPostfinancecheckoutCapturePending();
    }
}