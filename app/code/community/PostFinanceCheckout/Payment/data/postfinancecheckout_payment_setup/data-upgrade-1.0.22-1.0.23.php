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

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$connection = $installer->getConnection();

$installer->startSetup();

/**
 * Encrypt sensitive configuration values.
 */
$rows = $connection->fetchAll(
    "select * from {$installer->getTable('core_config_data')} where
    path = 'postfinancecheckout_payment/general/api_user_secret'"
);

$helper = Mage::helper('core');
/* @var Mage_Core_Helper_Data $helper */
foreach ($rows as $row) {
    if (!empty($row['value'])) {
        $row['value'] = $helper->encrypt($row['value']);
        $connection->update($installer->getTable('core_config_data'), $row, 'config_id=' . $row['config_id']);
    }
}

$installer->endSetup();