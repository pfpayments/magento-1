<?php

/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout/).
 *
 * @package PostFinanceCheckout_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

/**
 * Handles the dynamic payment method configs.
 */
class PostFinanceCheckout_Payment_Model_Core_Config extends Mage_Core_Model_Config
{
    
    public function reinit($options = array())
    {
        parent::reinit($options);
        
        Mage::getModel('postfinancecheckout_payment/system_config')->initConfigValues();
    }
    
}