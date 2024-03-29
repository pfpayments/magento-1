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

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

/**
 * Add columns to store transaction information on the quote.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote'), 'postfinancecheckout_space_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BIGINT,
    'unsigned' => true,
    'comment' => 'PostFinance Checkout Space Id'
    )
);
$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote'), 'postfinancecheckout_transaction_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BIGINT,
    'unsigned' => true,
    'comment' => 'PostFinance Checkout Transaction Id'
    )
);
$installer->getConnection()->addIndex(
    $installer->getTable('sales/quote'), $installer->getIdxName(
        'sales/quote', array(
        'postfinancecheckout_space_id',
        'postfinancecheckout_transaction_id'
        )
    ), array(
    'postfinancecheckout_space_id',
    'postfinancecheckout_transaction_id'
    )
);

/**
 * Add columns to store transaction information on the order.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'), 'postfinancecheckout_space_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BIGINT,
    'unsigned' => true,
    'comment' => 'PostFinance Checkout Space Id'
    )
);
$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'), 'postfinancecheckout_transaction_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BIGINT,
    'unsigned' => true,
    'comment' => 'PostFinance Checkout Transaction Id'
    )
);
$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'), 'postfinancecheckout_authorized', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'default' => '0',
    'comment' => 'PostFinance Checkout Authorized'
    )
);
$installer->getConnection()->addIndex(
    $installer->getTable('sales/order'), $installer->getIdxName(
        'sales/order', array(
        'postfinancecheckout_space_id',
        'postfinancecheckout_transaction_id'
        )
    ), array(
    'postfinancecheckout_space_id',
    'postfinancecheckout_transaction_id'
    )
);

/**
 * Add a new column to the sales/quote_payment table that stores the selected token.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote_payment'), 'postfinancecheckout_token', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'length' => 10,
    'unsigned' => true,
    'comment' => 'PostFinance Checkout Token'
    )
);

/**
 * Add a new column to the sales/invoice table that stores whether the invoice is in pending capture state.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/invoice'), 'postfinancecheckout_capture_pending', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'default' => '0',
    'comment' => 'PostFinance Checkout Capture Pending'
    )
);

/**
 * Add a new column to the sales/creditmemo table that stores the external id of the refund in PostFinance Checkout
 * representing this creditmemo.
 */
$installer->getConnection()->addColumn(
    $installer->getTable('sales/creditmemo'), 'postfinancecheckout_external_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 100,
    'nullable' => true,
    'comment' => 'PostFinance Checkout External Id'
    )
);
$installer->getConnection()->addIndex(
    $installer->getTable('sales/creditmemo'), $installer->getIdxName(
        'sales/creditmemo', array(
        'postfinancecheckout_external_id'
        ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ), array(
    'postfinancecheckout_external_id'
    ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

/**
 * Create table 'postfinancecheckout_payment/transaction_info'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('postfinancecheckout_payment/transaction_info'))
    ->addColumn(
        'entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
        ), 'Entity Id'
    )
    ->addColumn(
        'transaction_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Transaction Id'
    )
    ->addColumn(
        'state', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
        ), 'State'
    )
    ->addColumn(
        'space_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Space Id'
    )
    ->addColumn(
        'space_view_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => true
        ), 'Space View Id'
    )
    ->addColumn(
        'language', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
        ), 'Language'
    )
    ->addColumn(
        'currency', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
        ), 'Currency'
    )
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
    ->addColumn(
        'authorization_amount', Varien_Db_Ddl_Table::TYPE_NUMERIC, '19,8', array(
        'nullable' => false
        ), 'Authorization Amount'
    )
    ->addColumn(
        'image', Varien_Db_Ddl_Table::TYPE_TEXT, '512', array(
        'nullable' => true
        ), 'Image'
    )
    ->addColumn('labels', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(), 'Labels')
    ->addColumn(
        'payment_method_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => true
        ), 'Payment Method Id'
    )
    ->addColumn(
        'connector_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => true
        ), 'Connector Id'
    )
    ->addColumn(
        'order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Order Id'
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/transaction_info', array(
            'space_id',
            'transaction_id'
            ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array(
        'space_id',
        'transaction_id'
        ), array(
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/transaction_info', array(
            'order_id'
            ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array(
        'order_id'
        ), array(
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    );
$installer->getConnection()->createTable($table);

/**
 * Create table 'postfinancecheckout_payment/token_info'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('postfinancecheckout_payment/token_info'))
    ->addColumn(
        'entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
        ), 'Entity Id'
    )
    ->addColumn(
        'token_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Token Id'
    )
    ->addColumn(
        'state', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
        ), 'State'
    )
    ->addColumn(
        'space_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Space Id'
    )
    ->addColumn(
        'name', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
        'nullable' => false
        ), 'Name'
    )
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
    ->addColumn(
        'customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Customer Id'
    )
    ->addColumn(
        'payment_method_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Payment Method Id'
    )
    ->addColumn(
        'connector_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Connector Id'
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/token_info', array(
            'customer_id'
            )
        ), array(
        'customer_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/token_info', array(
            'payment_method_id'
            )
        ), array(
        'payment_method_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/token_info', array(
            'connector_id'
            )
        ), array(
        'connector_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/token_info', array(
            'space_id',
            'token_id'
            ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array(
        'space_id',
        'token_id'
        ), array(
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    );
$installer->getConnection()->createTable($table);

/**
 * Create table 'postfinancecheckout_payment/payment_method_configuration'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('postfinancecheckout_payment/payment_method_configuration'))
    ->addColumn(
        'entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
        ), 'Entity Id'
    )
    ->addColumn(
        'state', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => 1
        ), 'State'
    )
    ->addColumn(
        'space_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Space Id'
    )
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Updated At')
    ->addColumn(
        'configuration_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Configuration Id'
    )
    ->addColumn(
        'configuration_name', Varien_Db_Ddl_Table::TYPE_TEXT, 150, array(
        'nullable' => false
        ), 'Configuration Name'
    )
    ->addColumn(
        'title', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        'nullable' => true
        ), 'Title'
    )
    ->addColumn(
        'description', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        'nullable' => true
        ), 'Description'
    )
    ->addColumn(
        'image', Varien_Db_Ddl_Table::TYPE_TEXT, '512', array(
        'nullable' => true
        ), 'Image'
    )
    ->addColumn(
        'sort_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false
        ), 'Sort Order'
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/payment_method_configuration', array(
            'space_id'
            )
        ), array(
        'space_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/payment_method_configuration', array(
            'configuration_id'
            )
        ), array(
        'configuration_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/payment_method_configuration', array(
            'space_id',
            'configuration_id'
            ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array(
        'space_id',
        'configuration_id'
        ), array(
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    )
    ->setComment('PostFinance Checkout Payment Method Configuration');
$installer->getConnection()->createTable($table);

/**
 * Create table 'postfinancecheckout_payment/refund_job'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('postfinancecheckout_payment/refund_job'))
    ->addColumn(
        'entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true
        ), 'Entity Id'
    )
    ->addColumn(
        'order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Order Id'
    )
    ->addColumn(
        'space_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, array(
        'unsigned' => true,
        'nullable' => false
        ), 'Space Id'
    )
    ->addColumn(
        'external_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, array(
        'nullable' => false
        ), 'External Id'
    )
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(), 'Created At')
    ->addColumn(
        'refund', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(
        'nullable' => false
        ), 'Refund'
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/refund_job', array(
            'space_id'
            )
        ), array(
        'space_id'
        )
    )
    ->addIndex(
        $installer->getIdxName(
            'postfinancecheckout_payment/refund_job', array(
            'order_id'
            ), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ), array(
        'order_id'
        ), array(
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        )
    )
    ->setComment('PostFinance Checkout Payment Refund Job');
$installer->getConnection()->createTable($table);

$installer->endSetup();