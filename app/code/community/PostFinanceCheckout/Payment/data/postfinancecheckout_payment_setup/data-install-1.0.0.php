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
 * Insert order status 'Hold Delivery'.
 */
$data = array(
    array(
        'processing_postfinancecheckout',
        'Hold Delivery'
    )
);
$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status'), array(
    'status',
    'label'
    ), $data
);

/**
 * Assign order status 'Hold Delivery' to state 'processing'.
 */
$data = array(
    array(
        'processing_postfinancecheckout',
        'processing',
        0
    )
);
$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status_state'), array(
    'status',
    'state',
    'is_default'
    ), $data
);

$installer->endSetup();