<?php
/**
 * payfast_functions.php
 *
 * Functions used by payment module class for PayFast ITN payment method
 *
 * @copyright Copyright 2009 PayFast (Pty) Ltd
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

// Posting URLs
define( 'MODULE_PAYMENT_PAYFAST_SERVER_LIVE', 'www.payfast.co.za' );
define( 'MODULE_PAYMENT_PAYFAST_SERVER_TEST', 'sandbox.payfast.co.za' );

// Database tables
define( 'TABLE_PAYFAST', DB_PREFIX . 'payfast' );
define( 'TABLE_PAYFAST_SESSION', DB_PREFIX . 'payfast_session' );
define( 'TABLE_PAYFAST_PAYMENT_STATUS', DB_PREFIX . 'payfast_payment_status' );
define( 'TABLE_PAYFAST_PAYMENT_STATUS_HISTORY', DB_PREFIX . 'payfast_payment_status_history' );
define( 'TABLE_PAYFAST_TESTING', DB_PREFIX . 'payfast_testing' );

// Formatting
define( 'PF_FORMAT_DATETIME', 'Y-m-d H:i:s' );
define( 'PF_FORMAT_DATETIME_DB', 'Y-m-d H:i:s' );
define( 'PF_FORMAT_DATE', 'Y-m-d' );
define( 'PF_FORMAT_TIME', 'H:i' );
define( 'PF_FORMAT_TIMESTAMP', 'YmdHis' );

// General
define( 'PF_SESSION_LIFE', 7 );         // # of days session is saved for
define( 'PF_SESSION_EXPIRE_PROB', 5 );  // Probability (%) of deleting expired sessions

// {{{ pf_createUUID()
/**
 * pf_createUUID
 *
 * This function creates a pseudo-random UUID according to RFC 4122
 *
 * @see http://www.php.net/manual/en/function.uniqid.php#69164
 */
function pf_createUUID()
{
    $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );

    return( $uuid );
}
// }}}
// {{{ pf_getActiveTable()
/**
 * pf_getActiveTable
 *
 * This function gets the currently active table. If in testing mode, it
 * returns the test table, if in live, it returns the live table
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $msg String Message to log
 */
function pf_getActiveTable()
{
    if( strcasecmp( MODULE_PAYMENT_PAYFAST_SERVER, 'Live' ) == 0 )
        $table = TABLE_PAYFAST;
    else
        $table = TABLE_PAYFAST_TESTING;

    return( $table );
}
// }}}
// {{{ pf_createOrderArray()
/**
 * pf_createOrderArray
 *
 * Creates the array used to create a PayFast order
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $pfData Array Array of posted PayFast data
 * @param $zcOrderId Integer Order ID for Zen Cart order
 * @param $timestamp Integer Unix timestamp to use for transaction
 */
function pf_createOrderArray( $pfData = null, $zcOrderId = null, $timestamp = null )
{
    // Variable initialization
    $ts = empty( $timestamp ) ? time() : $timestamp;

    $sqlArray = array(
        'm_payment_id' => $pfData['m_payment_id'],
        'pf_payment_id' => $pfData['pf_payment_id'],
        'zc_order_id' => $zcOrderId,
        'amount_gross' => $pfData['amount_gross'],
        'amount_fee' => $pfData['amount_fee'],
        'amount_net' => $pfData['amount_net'],
        'payfast_data' => serialize( $pfData ),
        'timestamp' => date( PF_FORMAT_DATETIME_DB, $ts ),
        'status' => $pfData['payment_status'],
        'status_date' => date( PF_FORMAT_DATETIME_DB, $ts ),
        'status_reason' => '',
        );

    return( $sqlArray );
}
// }}}
// {{{ pf_determineTxnType()
/**
 * pf_lookupTransaction
 *
 * Determines the type of transaction which is occuring
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $pfData Array Array of posted PayFast data
 */
function pf_lookupTransaction( $pfData = null )
{
    // Variable initialization
    global $db;
    $data = array();

    $data = array(
        'pf_order_id' => '',
        'zc_order_id' => '',
        'txn_type' => '',
        );

    // Check if there is an existing order
    $sql =
        "SELECT `id` AS `pf_order_id`, `zc_order_id`, `status`
        FROM `". pf_getActiveTable() ."`
        WHERE `m_payment_id` = '". $pfData['m_payment_id'] ."'
        LIMIT 1";
    $orderData = $db->Execute( $sql );

    $exists = ( $orderData->RecordCount() > 0 );

    pflog( "Record count = ". $orderData->RecordCount() );

    // If record found, extract the useful information
    if( $exists )
        $data = array_merge( $data, $orderData->fields );

    pflog( "Data:\n". print_r( $data, true ) );

    // New transaction (COMPLETE or PENDING)
    if( !$exists )
        $data['txn_type'] = 'new';
    // Current transaction is PENDING and has now cleared
    elseif( $exists && $pfData['payment_status'] == 'COMPLETE' )
        $data['txn_type'] = 'cleared';
    // Current transaction is PENDING and is still PENDING
    elseif( $exists && $pfData['payment_status'] == 'PENDING' )
        $data['txn_type'] = 'update';
    // Current trasnaction is PENDING and has now failed
    elseif( $exists && $pfData['payment_status'] == 'FAILED' )
        $data['txn_type'] = 'failed';
    else
        $data['txn_type'] = 'unknown';

    pflog( "Data to be returned:\n". print_r( array_values( $data ), true ) );

    return( array_values( $data ) );
}
// }}}
// {{{ pf_createOrderHistoryArray()
/**
 * pf_createOrderHistoryArray
 *
 * Creats the array required for an order history update
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $pfData Array Array of posted PayFast data
 * @param $pfOrderId Integer Order ID for PayFast order
 * @param $timestamp Integer Unix timestamp to use for transaction
 */
function pf_createOrderHistoryArray( $pfData = null, $pfOrderId = null, $timestamp = null )
{
    $sqlArray = array (
        'pf_order_id' => $pfOrderId,
        'timestamp' => date( PF_FORMAT_DATETIME_DB, $timestamp ),
        'status' => $pfData['payment_status'],
        'status_reason' => '',
        );

    return( $sqlArray );
}
// }}}
// {{{ pf_updateOrderStatusAndHistory()
/**
 * pf_updateOrderStatusAndHistory
 *
 * Update the Zen Cart order status and history with new information supplied
 * from PayFast.
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $pfData Array Array of posted PayFast data
 * @param $zcOrderId Integer Order ID for ZenCart order
 */
function pf_updateOrderStatusAndHistory( $pfData, $zcOrderId, $newStatus = 1, $txnType, $ts )
{
    // Variable initialization
    global $db;

    // Update ZenCart order table with new status
    $sql =
        "UPDATE `". TABLE_ORDERS ."`
        SET `orders_status` = '". (int) $newStatus ."'
        WHERE `orders_id` = '". (int) $zcOrderId ."'";
    $db->Execute( $sql );

    // Update PayFast order with new status
    $sqlArray = array(
        'status' => $pfData['payment_status'],
        'status_date' => date( PF_FORMAT_DATETIME_DB, $ts ),
        );
    zen_db_perform(
        pf_getActiveTable(), $sqlArray, 'update', "zc_order_id='". $zcOrderId ."'" );

    // Create new PayFast order status history record
    $sqlArray = array(
        'orders_id' => (int)$zcOrderId,
        'orders_status_id' => (int)$newStatus,
        'date_added' => date( PF_FORMAT_DATETIME_DB, $ts ),
        'customer_notified' => '0',
        'comments' => 'PayFast status: '. $pfData['payment_status'],
       );
    zen_db_perform( TABLE_ORDERS_STATUS_HISTORY, $sqlArray );

    //// Activate any downloads for an order which has now cleared
    if( $txnType == 'cleared' )
    {
        $sql =
            "SELECT `date_purchased`
            FROM `". TABLE_ORDERS ."`
            WHERE `orders_id` = ". (int)$zcOrderId;
        $checkStatus = $db->Execute( $sql );

        $zcMaxDays = date_diff( $checkStatus->fields['date_purchased'],
            date( PF_FORMAT_DATETIME ) ) + (int)DOWNLOAD_MAX_DAYS;

        pflog( 'Updating order #'. (int)$zcOrderId . ' downloads. New max days: '.
            (int)$zcMaxDays .', New count: '. (int)DOWNLOAD_MAX_COUNT );

        $sql =
            "UPDATE `". TABLE_ORDERS_PRODUCTS_DOWNLOAD ."`
            SET `download_maxdays` = ". (int)$zcMaxDays .",
                `download_count` = ". (int)DOWNLOAD_MAX_COUNT ."
            WHERE `orders_id` = ". (int)$zcOrderId;
        $db->Execute( $sql );
    }
}
// }}}
// {{{ pf_removeExpiredSessions()
/**
 * pf_removeExpiredSessions
 *
 * Removes sessions from the PayFast session table which are passed their
 * expiry date. Sessions will be left like this due to shopping cart
 * abandonment (ie. someone get's all the way to the order confirmation
 * page but fails to click "Confirm Order"). This will also happen when orders
 * are cancelled.
 *
 * Won't be run every time it is called, but according to a probability
 * setting to ensure a non-excessive use of resources
 *
 * @author Jonathan Smit, PayFast (Pty) Ltd
 * @param $pfData Array Array of posted PayFast data
 * @param $zcOrderId Integer Order ID for ZenCart order
 */
function pf_removeExpiredSessions()
{
    // Variable initialization
    global $db;
    $prob = mt_rand( 1, 100 );

    pflog( 'Generated probability = '. $prob
        .' (Expires for <= '. PF_SESSION_EXPIRE_PROB .')' );

    if( $prob <= PF_SESSION_EXPIRE_PROB )
    {
        // Removed sessions passed their expiry date
        $sql =
            "DELETE FROM `". TABLE_PAYFAST_SESSION ."`
            WHERE `expiry` < '". date( PF_FORMAT_DATETIME_DB ) ."'";
        $db->Execute( $sql );
    }
}
// }}}
?>