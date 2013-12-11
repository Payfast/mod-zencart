<?php
/**
 * payfast.php
 *
 * Lanugage defines for the PayFast payment module within the admin console
 *
 * @copyright Copyright 2009 PayFast (Pty) Ltd
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

// General
define( 'TEXT_ALL', 'All' );

// Sort orders
define( 'TEXT_PAYFAST_SORT_ORDER_INFO', 'Display Order: ' );
define( 'TEXT_SORT_PAYFAST_ID_DESC', 'PayFast Order Received (new - old)' );
define( 'TEXT_SORT_PAYFAST_ID', 'PayFast Order Received (old - new)' );
define( 'TEXT_SORT_ZEN_ORDER_ID_DESC', 'Order ID (high - low), PayFast Order Received' );
define( 'TEXT_SORT_ZEN_ORDER_ID', 'Order ID (low - high), PayFast Order Received' );
define( 'TEXT_PAYMENT_AMOUNT_DESC', 'Order Amount (high - low)' );
define( 'TEXT_PAYMENT_AMOUNT', 'Order Amount (low - high)' );

// Page headings
define( 'HEADING_ADMIN_TITLE', 'PayFast' );
define( 'HEADING_PAYMENT_STATUS', 'Payment Status' );

// Table headings
define( 'TABLE_HEADING_ORDER_NUMBER', 'Order #' );
define( 'TABLE_HEADING_MERCHANT_REF', 'Merchant ref on PayFast' );
define( 'TABLE_HEADING_AMOUNT_GROSS', 'Gross' );
define( 'TABLE_HEADING_AMOUNT_FEE', 'Fee' );
define( 'TABLE_HEADING_AMOUNT_NET', 'Net' );
define( 'TABLE_HEADING_STATUS', 'Status' );
define( 'TABLE_HEADING_ACTION', 'Action' );

// Right pane headings
define( 'TEXT_INFO_PAYFAST_HEADING', 'PayFast' );
define( 'TABLE_HEADING_NUM_HISTORY_ENTRIES', 'Number of entries in Status History' );
define( 'TABLE_HEADING_ENTRY_NUM', 'Entry #' );
define( 'TABLE_HEADING_DATE_ADDED', 'Timestamp' );
define( 'TABLE_HEADING_STATUS_REASON', 'Status Reason' );

define( 'TEXT_DISPLAY_NUMBER_OF_TRANSACTIONS',
    'Displaying <strong>%d</strong> to <strong>%d</strong> (of <strong>%d</strong> entries)' );
?>