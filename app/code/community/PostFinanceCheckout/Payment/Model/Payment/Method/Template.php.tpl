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

class PostFinanceCheckout_Payment_Model_PaymentMethod{id} extends PostFinanceCheckout_Payment_Model_Payment_Method_Abstract
{
    protected $_code = 'postfinancecheckout_payment_{id}';
    
    protected $_paymentMethodConfigurationId = {id};
}