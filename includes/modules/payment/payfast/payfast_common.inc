<?php

/**
 * common.php
 *
 * Copyright (c) 2023 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or
 * part thereof in any way.
 * @author PayFast (Pty) Ltd
 */

//// Create user agent string
// phpcs:disable
define('PF_SOFTWARE_NAME', 'ZenCart');
if (defined('PROJECT_VERSION_MAJOR')) {
    define('PF_SOFTWARE_VER', PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR);
}
define('PF_MODULE_NAME', 'PayFast_ZenCart');
define('PF_MODULE_VER', '1.1.4');

// Features
// - PHP
$pfFeatures = 'PHP ' . phpversion() . ';';
// phpcs:enable

// - cURL
if (in_array('curl', get_loaded_extensions())) {
    // phpcs:disable
    define('PF_CURL', '');
    $pfVersion  = curl_version();
    $pfFeatures .= ' curl ' . $pfVersion['version'] . ';';
    // phpcs:enable
} else {
    // phpcs:disable
    $pfFeatures .= ' nocurl;';
    // phpcs:enable
}
// General Defines
// phpcs:disable
define('PF_TIMEOUT', 15);
define('PF_EPSILON', 0.01);

// Messages
// Error
define('PF_ERR_AMOUNT_MISMATCH', 'Amount mismatch');
define('PF_ERR_BAD_ACCESS', 'Bad access of page');
define('PF_ERR_BAD_SOURCE_IP', 'Bad source IP address');
define('PF_ERR_CONNECT_FAILED', 'Failed to connect to PayFast');
define('PF_ERR_INVALID_SIGNATURE', 'Security signature mismatch');
define('PF_ERR_MERCHANT_ID_MISMATCH', 'Merchant ID mismatch');
define('PF_ERR_NO_SESSION', 'No saved session found for ITN transaction');
define('PF_ERR_ORDER_ID_MISSING_URL', 'Order ID not present in URL');
define('PF_ERR_ORDER_ID_MISMATCH', 'Order ID mismatch');
define('PF_ERR_ORDER_INVALID', 'This order ID is invalid');
define('PF_ERR_ORDER_NUMBER_MISMATCH', 'Order Number mismatch');
define('PF_ERR_ORDER_PROCESSED', 'This order has already been processed');
define('PF_ERR_PDT_FAIL', 'PDT query failed');
define('PF_ERR_PDT_TOKEN_MISSING', 'PDT token not present in URL');
define('PF_ERR_SESSIONID_MISMATCH', 'Session ID mismatch');
define('PF_ERR_UNKNOWN', 'Unkown error occurred');

// General
define('PF_MSG_OK', 'Payment was successful');
define('PF_MSG_FAILED', 'Payment has failed');
define(
    'PF_MSG_PENDING',
    'The payment is pending. Please note, you will receive another Instant' .
    ' Transaction Notification when the payment status changes to' .
    ' "Completed", or "Failed"'
);
// phpcs:enable

/**
 * pflog
 *
 * Log function for logging output.
 *
 * @param $msg String Message to log
 * @param $close Boolean Whether to close the log file or not
 *
 * @author PayFast (Pty) Ltd
 */
function pflog($msg = '', $close = false)
{
    if (defined('PF_DEBUG')) {
        static $fh = 0;
        global $module;

        // Only log if debugging is enabled
        if (PF_DEBUG) {
            if ($close) {
                fclose($fh);
            } else {
                // If file doesn't exist, create it
                if (!$fh) {
                    $pathinfo = pathinfo(__FILE__);
                    $fh       = fopen($pathinfo['dirname'] . '/payfast.log', 'a+');
                }

                // If file was successfully created
                if ($fh) {
                    $line = date('Y-m-d H:i:s') . ' : ' . $msg . "\n";

                    fwrite($fh, $line);
                }
            }
        }
    }
}

/**
 * pfGetData
 *
 * @author PayFast (Pty) Ltd
 */
function pfGetData(): bool|array
{
    // Posted variables from ITN
    $pfData = $_POST;
    if (empty($_POST)) {
        $pfData = json_decode(file_get_contents('php://input'), true);
    }

    // Strip any slashes in data
    foreach ($pfData as $key => $val) {
        $pfData[$key] = stripslashes($val);
    }


    // Return "false" if no data was received
    if (empty($pfData)) {
        return (false);
    } else {
        return ($pfData);
    }
}

function pfGenerateString($pfData, $pfPassphrase = null): string
{
    // Dump the submitted variables and calculate security signature
    $pfParamString = '';

    foreach ($pfData as $key => $val) {
        if ($key != 'signature') {
            $pfParamString .= $key . '=' . urlencode($val) . '&';
        } else {
            break;
        }
    }

    // Remove the last '&' from the parameter string
    $pfParamString = substr($pfParamString, 0, -1);

    // Calculate security signature
    if ($pfPassphrase === null) {
        $tempParamString = $pfParamString;
    } else {
        $tempParamString = $pfParamString . '&passphrase=' . urlencode($pfPassphrase);
    }

    return $tempParamString;
}

/**
 * pfValidSignature
 *
 * @author PayFast (Pty) Ltd
 */
function pfValidSignature($pfData = null, $pfParamString = null, $pfPassphrase = null): bool
{
    $tempParamString = pfGenerateString($pfData, $pfPassphrase);

    $signature = md5($tempParamString);

    $result = ($pfData['signature'] == $signature);

    pflog('Signature = ' . ($result ? 'valid' : 'invalid'));

    return $result;
}

/**
 * pfValidData
 *
 * @param $pfHost String Hostname to use
 * @param $pfParamString String Parameter string to send
 * @param $proxy String Address of proxy to use or NULL if no proxy
 *
 * @author PayFast (Pty) Ltd
 */
function pfValidData(
    $pfHost = 'www.payfast.co.za',
    $pfData = null,
    $pfPassphrase = null,
    $pfParamString = '',
    $pfProxy = null
) {
    pflog('Host = ' . $pfHost);
    pflog('Params = ' . $pfParamString);

    $tempParamString = pfGenerateString($pfData, $pfPassphrase);

    // Use cURL (if available)
    if (defined('PF_CURL')) {
        // Variable initialization
        $url = 'https://' . $pfHost . '/eng/query/validate';

        // Create default cURL object
        $ch = curl_init();

        // Set cURL options - Use curl_setopt for greater PHP compatibility
        // Base settings
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // Return output as string rather than outputting it
        curl_setopt($ch, CURLOPT_HEADER, false);             // Don't include header in output
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Standard settings
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $tempParamString);
        curl_setopt($ch, CURLOPT_TIMEOUT, PF_TIMEOUT);
        if (!empty($pfProxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        // Execute CURL
        $response = curl_exec($ch);
        curl_close($ch);
    } else { // Use fsockopen
        // Variable initialization
        $header     = '';
        $response   = '';
        $headerDone = false;

        // Construct Header
        $header = "POST /eng/query/validate HTTP/1.0\r\n";
        $header .= "Host: " . $pfHost . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($pfParamString) . "\r\n\r\n";

        // Connect to server
        $socket = fsockopen('ssl://' . $pfHost, 443, $errno, $errstr, PF_TIMEOUT);

        // Send command to server
        fputs($socket, $header . $pfParamString);

        // Read the response from the server
        while (!feof($socket)) {
            $line = fgets($socket, 1024);

            // Check if we are finished reading the header yet
            if (strcmp($line, "\r\n") == 0) {
                // read the header
                $headerDone = true;
            } elseif ($headerDone) { // If header has been processed
                // Read the main response
                $response .= $line;
            }
        }
    }

    pflog("Response:\n" . print_r($response, true));

    // Interpret Response
    $lines        = explode("\r\n", $response);
    $verifyResult = trim($lines[0]);

    if (strcasecmp($verifyResult, 'VALID') == 0) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * pfAmountsEqual
 *
 * Checks to see whether the given amounts are equal using a proper floating
 * point comparison with an Epsilon which ensures that insignificant decimal
 * places are ignored in the comparison.
 *
 * eg. 100.00 is equal to 100.0001
 *
 * @param $amount1 Float 1st amount for comparison
 * @param $amount2 Float 2nd amount for comparison
 *
 * @author PayFast (Pty) Ltd
 */
function pfAmountsEqual($amount1, $amount2)
{
    if (abs(floatval($amount1) - floatval($amount2)) > PF_EPSILON) {
        return (false);
    } else {
        return (true);
    }
}
