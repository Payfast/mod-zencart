<?php

/**
 * payfast_itn_handler
 *
 * Callback handler for PayFast ITN
 *
 * Copyright (c) 2023 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own
 * website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any
 * reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 */

//// bof: Load ZenCart configuration
$show_all_errors   = false;
$current_page_base = 'payfastitn';
$loaderPrefix      = 'payfast_itn';

$show_all_errors = false;
require_once('includes/configure.php');
require_once('includes/modules/payment/payfast/payfast_common.inc');
require_once('includes/modules/payment/payfast/payfast_functions.php');
require_once('includes/application_top.php');
require_once(DIR_WS_CLASSES . 'payment.php');

$zcSessName = '';
$zcSessID   = '';
//// eof: Load ZenCart configuration

$show_all_errors    = true;
$logdir             = defined('DIR_FS_LOGS') ? DIR_FS_LOGS : 'includes/modules/payment/payfast';
$debug_logfile_path = $logdir . '/itn_debug_php_errors-' . time() . '.log';
@ini_set('log_errors', 1);
@ini_set('log_errors_max_len', 0);
@ini_set('display_errors', 0); // do not output errors to screen/browser/client (only to log file)
@ini_set('error_log', DIR_FS_CATALOG . $debug_logfile_path);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);


// Variable Initialization
const LINE_LITERAL                       = ' line ';
const REGEX_HI_LITERAL                   = "Hi,\n\n";
const LONG_LINE_LITERAL                  = "------------------------------------------------------------\n";
const SITE_LITERAL                       = "Site: ";
const ORDER_LITERAL                      = "Order ID: ";
const TRANSACTION_LITERAL                = "PayFast Transaction ID: ";
const PAYMENT_LITERAL                    = "PayFast Payment Status: ";
const MODULE_PAYMENT_PAYFAST_SERVER_LIVE = "payfast.co.za";
const MODULE_PAYMENT_PAYFAST_SERVER_TEST = "sandbox.payfast.co.za";

$pfError       = false;
$pfErrMsg      = '';
$pfData        = array();
$pfHost        = (strcasecmp(MODULE_PAYMENT_PAYFAST_SERVER, 'live') == 0) ?
    MODULE_PAYMENT_PAYFAST_SERVER_LIVE : MODULE_PAYMENT_PAYFAST_SERVER_TEST;
$pfOrderId     = '';
$pfParamString = '';
$pfPassphrase  = MODULE_PAYMENT_PAYFAST_PASSPHRASE;
$pfDebugEmail  = defined('MODULE_PAYMENT_PAYFAST_DEBUG_EMAIL_ADDRESS')
    ? MODULE_PAYMENT_PAYFAST_DEBUG_EMAIL_ADDRESS : STORE_OWNER_EMAIL_ADDRESS;

if (!defined('PF_DEBUG')) {
    // phpcs:disable
    define("PF_DEBUG", true);
    // phpcs:enable
}
pflog('PayFast ITN call received');

//// Notify PayFast that information has been received
if (!$pfError) {
    header('HTTP/1.0 200 OK');
    flush();
}

//// Get data sent by PayFast
if (!$pfError) {
    pflog('Get posted data');

    // Posted variables from ITN
    $pfData = pfGetData();

    pflog('PayFast Data: ' . print_r($pfData, true));

    if ($pfData === false) {
        $pfError  = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

//// Verify security signature
if (!$pfError) {
    pflog('Verify security signature');

    // If signature different, log for debugging
    if (!pfValidSignature($pfData, $pfParamString, $pfPassphrase)) {
        $pfError  = true;
        $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
    }
}

//// Verify data received
if (!$pfError) {
    pflog('Verify data received');

    $pfValid = pfValidData($pfHost, $pfData, $pfPassphrase, $pfParamString);

    if (!$pfValid) {
        $pfError  = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

//// Create ZenCart order
if (!$pfError) {
    // Variable initialization
    $ts        = time();
    $pfOrderId = null;
    $zcOrderId = null;
    $txnType   = null;

    // Determine the transaction type
    list($pfOrderId, $zcOrderId, $txnType) = pf_lookupTransaction($pfData);

    pflog(
        "Transaction details:" .
        "\n- pfOrderId = " . (empty($pfOrderId) ? 'null' : $pfOrderId) .
        "\n- zcOrderId = " . (empty($zcOrderId) ? 'null' : $zcOrderId) .
        "\n- txnType   = " . (empty($txnType) ? 'null' : $txnType)
    );

    switch ($txnType) {
        /**
         * New Transaction
         *
         * This is for when Zen Cart sees a transaction for the first time.
         * This doesn't necessarily mean that the transaction is in a
         * COMPLETE state, but rather than it is new to the system
         */
        case 'new':
            //// bof: Get Saved Session
            pflog('Retrieving saved session');

            // Get the Zen session name and ID from PayFast data
            list($zcSessName, $zcSessID) = explode('=', $pfData['custom_str2']);

            pflog('Session Name = ' . $zcSessName . ', Session ID = ' . $zcSessID);

            $sql           =
                "SELECT *
                FROM `" . TABLE_PAYFAST_SESSION . "`
                WHERE `session_id` = '" . $zcSessID . "'";
            $storedSession = $db->Execute($sql);

            if ($storedSession->recordCount() < 1) {
                $pfError  = true;
                $pfErrMsg = PF_ERR_NO_SESSION;
                break;
            } else {
                $_SESSION = unserialize(base64_decode($storedSession->fields['saved_session']));
            }
            //// eof: Get Saved Session

            //// bof: Get ZenCart order details
            pflog('Recreating Zen Cart order environment');
            if (defined(DIR_WS_CLASSES)) {
                pflog('Additional debug information: DIR_WS_CLASSES is ' . DIR_WS_CLASSES);
            } else {
                pflog(' ***ALERT*** DIR_WS_CLASSES IS NOT DEFINED');
            }
            if (isset($_SESSION)) {
                pflog('SESSION IS : ' . print_r($_SESSION, true));
            } else {
                pflog(' ***ALERT*** $_SESSION IS NOT DEFINED');
            }


            // Load ZenCart shipping class
            require_once(DIR_WS_CLASSES . 'shipping.php');
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            // Load ZenCart payment class
            require_once(DIR_WS_CLASSES . 'payment.php');
            $payment_modules = new payment($_SESSION['payment']);
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            $shipping_modules = new shipping($_SESSION['shipping']);
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            // Load ZenCart order class
            require(DIR_WS_CLASSES . 'order.php');
            $order = new order();
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            // Load ZenCart order_total class
            require(DIR_WS_CLASSES . 'order_total.php');
            $order_total_modules = new order_total();
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            $order_totals = $order_total_modules->process();
            //// eof: Get ZenCart order details
            pflog(__FILE__ . LINE_LITERAL . __LINE__);
            //// bof: Check data against ZenCart order
            pflog('Checking data against ZenCart order');

            // Check order amount
            pflog('Checking if amounts are the same');
            // patch for multi-currency - AGB 19/07/13 - see also includes/modules/payment/payfast.php
            // if( !pfAmountsEqual( $pfData['amount_gross'], $order->info['total'] ) )
            if (!pfAmountsEqual($pfData['amount_gross'], $_SESSION['payfast_amount'])) {
                pflog(
                    'Amount mismatch: PF amount = ' .
                    $pfData['amount_gross'] . ', ZC amount = ' . $_SESSION['payfast_amount']
                );

                $pfError  = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
                break;
            }
            //// eof: Check data against ZenCart order

            // Create ZenCart order
            pflog('Creating Zen Cart order');
            $zcOrderId = $order->create($order_totals);

            // Create PayFast order
            pflog('Creating PayFast order');
            $sqlArray = pf_createOrderArray($pfData, $zcOrderId, $ts);
            zen_db_perform(TABLE_PAYFAST, $sqlArray);

            // Create PayFast history record
            pflog('Creating PayFast payment status history record');
            $pfOrderId = $db->Insert_ID();

            $sqlArray = pf_createOrderHistoryArray($pfData, $pfOrderId, $ts);
            zen_db_perform(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $sqlArray);

            // Update order status (if required)
            $newStatus = MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID;

            if ($pfData['payment_status'] == 'PENDING') {
                pflog('Setting Zen Cart order status to PENDING');
                $newStatus = MODULE_PAYMENT_PAYFAST_PROCESSING_STATUS_ID;

                $sql =
                    "UPDATE " . TABLE_ORDERS . "
                    SET `orders_status` = " . MODULE_PAYMENT_PAYFAST_PROCESSING_STATUS_ID . "
                    WHERE `orders_id` = '" . $zcOrderId . "'";
                $db->Execute($sql);
            }

            // Update order status history
            pflog('Inserting Zen Cart order status history record');

            $sqlArray = array(
                'orders_id'         => $zcOrderId,
                'orders_status_id'  => $newStatus,
                'date_added'        => date(PF_FORMAT_DATETIME_DB, $ts),
                'customer_notified' => '0',
                'comments'          => 'PayFast status: ' . $pfData['payment_status'],
            );
            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sqlArray);

            // Add products to order
            pflog('Adding products to order');
            $order->create_add_products($zcOrderId, 2);

            // Email customer
            pflog('Emailing customer');
            $order->send_order_email($zcOrderId, 2);

            // Empty cart
            pflog('Emptying cart');
            $_SESSION['cart']->reset(true);

            // Deleting stored session information
            $sql =
                "DELETE FROM `" . TABLE_PAYFAST_SESSION . "`
                WHERE `session_id` = '" . $zcSessID . "'";
            $db->Execute($sql);

            // Sending email to admin
            if (PF_DEBUG) {
                $subject = "PayFast ITN on your site";
                $body    =
                    REGEX_HI_LITERAL .
                    "A PayFast transaction has been completed on your website\n" .
                    LONG_LINE_LITERAL .
                    SITE_LITERAL . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" .
                    ORDER_LITERAL . $zcOrderId . "\n" .
                    //"User ID: ". $db->f( 'user_id' ) ."\n".
                    TRANSACTION_LITERAL . $pfData['pf_payment_id'] . "\n" .
                    PAYMENT_LITERAL . $pfData['payment_status'] . "\n" .
                    "Order Status Code: " . $newStatus;
                zen_mail(
                    STORE_OWNER,
                    $pfDebugEmail,
                    $subject,
                    $body,
                    STORE_OWNER,
                    STORE_OWNER_EMAIL_ADDRESS,
                    null,
                    'debug'
                );
            }

            break;

        /**
         * Pending transaction must be cleared
         *
         * This is for when there is an existing order in the system which
         * is in a PENDING state which has now been updated to COMPLETE.
         */
        case 'cleared':
            $sqlArray = pf_createOrderHistoryArray($pfData, $pfOrderId, $ts);
            zen_db_perform(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $sqlArray);

            $newStatus = MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID;
            break;

        /**
         * Pending transaction must be updated
         *
         * This is when there is an existing order in the system in a PENDING
         * state which is being updated and is STILL in a pending state.
         *
         * NOTE: Currently, this should never happen
         */
        case 'update':
            $sqlArray = pf_createOrderHistoryArray($pfData, $pfOrderId, $ts);
            zen_db_perform(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $sqlArray);

            break;

        /**
         * Pending transaction has failed
         *
         * NOTE: Currently, this should never happen
         */
        case 'failed':
            $comments = 'Payment failed (PayFast id = ' . $pfData['pf_payment_id'] . ')';
            $sqlArray = pf_createOrderHistoryArray($pfData, $pfOrderId, $ts);
            zen_db_perform(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $sqlArray);

            $newStatus = MODULE_PAYMENT_PAYFAST_PREPARE_ORDER_STATUS_ID;

            // Sending email to admin
            $subject = "PayFast ITN Transaction on your site";
            $body    =
                REGEX_HI_LITERAL .
                "A failed PayFast transaction on your website requires attention\n" .
                LONG_LINE_LITERAL .
                SITE_LITERAL . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" .
                ORDER_LITERAL . $zcOrderId . "\n" .
                //"User ID: ". $db->f( 'user_id' ) ."\n".
                TRANSACTION_LITERAL . $pfData['pf_payment_id'] . "\n" .
                PAYMENT_LITERAL . $pfData['payment_status'] . "\n" .
                "Order Status Code: " . $newStatus;
            zen_mail(
                STORE_OWNER,
                $pfDebugEmail,
                $subject,
                $body,
                STORE_OWNER,
                STORE_OWNER_EMAIL_ADDRESS,
                null,
                'debug'
            );

            break;

        /**
         * Unknown t
         *
         * NOTE: Currently, this should never happen
         */
        default:
            pflog(
                "Can not process for txn type '" . $txn_type . ":\n" .
                print_r($pfData, true)
            );
            break;
    }
}

// Update Zen Cart order and history status tables
if (!$pfError && ($txnType != 'new' && !empty($newStatus))) {
    pf_updateOrderStatusAndHistory($pfData, $zcOrderId, $txnType, $ts, $newStatus);
}

//// Notify PayFast that information has been received
if (!$pfError) {
    header('HTTP/1.0 200 OK');
    flush();
} else {
    header("HTTP/1.1 500 Internal Server Error");
    flush();

    pflog('Error occurred: ' . $pfErrMsg);
    pflog('Sending email notification');

    $subject = "PayFast ITN error: " . $pfErrMsg;
    $body    =
        REGEX_HI_LITERAL .
        "An invalid PayFast transaction on your website requires attention\n" .
        LONG_LINE_LITERAL .
        SITE_LITERAL . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" .
        "Remote IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n" .
        "Remote host name: " . gethostbyaddr($_SERVER['REMOTE_ADDR']) . "\n" .
        ORDER_LITERAL . $zcOrderId . "\n";
    if (isset($pfData['pf_payment_id'])) {
        $body .= TRANSACTION_LITERAL . $pfData['pf_payment_id'] . "\n";
    }

    if (isset($pfData['payment_status'])) {
        $body .= PAYMENT_LITERAL . $pfData['payment_status'] . "\n";
    }

    $body .=
        "\nError: " . $pfErrMsg . "\n";

    if ($pfErrMsg === PF_ERR_AMOUNT_MISMATCH) {
        $body .=
            "Value received : " . $pfData['amount_gross'] . "\n" .
            "Value should be: " . $order->info['total'];
    }

    zen_mail(STORE_OWNER, $pfDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug');
}

// Close log
pflog('', true);
