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
    
    protected $_cacheSections = array(
        'admin'     => 0,
        'adminhtml' => 0,
        'crontab'   => 0,
        'install'   => 0,
        'stores'    => 1,
        'websites'  => 0,
        'wallee'    => 0
    );
    
    public function loadDb()
    {
        parent::loadDb();
        
        Mage::getModel('postfinancecheckout_payment/system_config')->initConfigValues();
    }
    
}