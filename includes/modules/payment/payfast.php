<?php

/**
 * payfast.php
 *
 * Main module file which is responsible for installing, editing and deleting
 * module details from DB and sending data to PayFast.
 *
 * Copyright (c) 2023 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or
 * part thereof in any way.
 */

// Load dependency files
if (defined('MODULE_PAYMENT_PAYFAST_DEBUG') && !defined("PF_DEBUG")) {
    define('PF_DEBUG', MODULE_PAYMENT_PAYFAST_DEBUG == 'True');
}
// phpcs:disable
include_once((IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/payfast/payfast_common.inc');
include_once((IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/payfast/payfast_functions.php');
// phpcs:enable

/**
 * payfast
 *
 * Class for PayFast
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 */
class payfast extends base
{
    /**
     * payfast
     *
     * Constructor
     *
     * >> Standard ZenCart
     *
     * @param int $id
     *
     * @return payfast
     * @author PayFast (Pty) Ltd
     */

    private const DELETE_LITERAL = 'DELETE FROM ';
    private const CREATE_LITERAL = 'CREATE TABLE ';
    private const INSERT_LITERAL = 'INSERT INTO ';

    /**
     * $code string repesenting the payment method
     * @var string
     */
    public $code;

    /**
     * $title is the displayed name for this payment method
     * @var string
     */
    public $title;

    /**
     * $description is a soft name for this payment method
     * @var string
     */
    public $description;

    /**
     * $enabled determines whether this module shows or not... in catalog.
     * @var boolean
     */
    public $enabled;

    public function __construct($id = '')
    {
        // Variable initialization
        global $order, $messageStack;
        $this->code        = 'payfast';
        $this->codeVersion = '1.5.8';

        // Set payment module title in Admin
        if (IS_ADMIN_FLAG === true) {
            $this->title = 'PayFast';

            // Check if in test mode
            if (defined('MODULE_PAYMENT_PAYFAST_SERVER')) {
                if (IS_ADMIN_FLAG === true && MODULE_PAYMENT_PAYFAST_SERVER == 'Test') {
                    $this->title .= '<span class="alert"> (test mode active)</span>';
                }
            } else {
                $this->title .= '<span class="alert"> (test mode active)</span>';
            }
        } else {
            // Set payment module title in Catalog
            $this->title = MODULE_PAYMENT_PAYFAST_TEXT_CATALOG_TITLE;
        }

        // Set other payment module variables
        $this->description = MODULE_PAYMENT_PAYFAST_TEXT_DESCRIPTION;
        if (defined('MODULE_PAYMENT_PAYFAST_SORT_ORDER')) {
            $this->sort_order = MODULE_PAYMENT_PAYFAST_SORT_ORDER;
        }

        if (defined('MODULE_PAYMENT_PAYFAST_STATUS')) {
            $this->enabled = ((MODULE_PAYMENT_PAYFAST_STATUS == 'True') ? true : false);
        }

        if (defined('MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID;
        }


        if (is_object($order)) {
            $this->update_status();
        }


        if (defined('MODULE_PAYMENT_PAYFAST_SERVER')) {
            // Set posting destination destination
            if (MODULE_PAYMENT_PAYFAST_SERVER == 'Test') {
                $this->form_action_url = 'https://' . MODULE_PAYMENT_PAYFAST_SERVER_TEST;
            } else {
                $this->form_action_url = 'https://' . MODULE_PAYMENT_PAYFAST_SERVER_LIVE;
            }
        }

        $this->form_action_url .= '/eng/process';

        // Check for right version
        if (PROJECT_VERSION_MAJOR != '1' && substr(PROJECT_VERSION_MINOR, 0, 3) != '3.9') {
            $this->enabled = false;
        }
    }

    /**
     * update_status
     *
     * Calculate zone matches and flag settings to determine whether this
     * module should display to customers or not.
     *
     * @author PayFast (Pty) Ltd
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function update_status()
    {
        global $order, $db;

        if ($this->enabled && ((int)MODULE_PAYMENT_PAYFAST_ZONE > 0)) {
            $check_flag  = false;
            $check_query = $db->Execute(
                "SELECT `zone_id`
                FROM " . TABLE_ZONES_TO_GEO_ZONES . "
                WHERE `geo_zone_id` = '" . MODULE_PAYMENT_PAYFAST_ZONE . "'
                  AND `zone_country_id` = '" . $order->billing['country']['id'] . "'
                ORDER BY `zone_id`"
            );

            while (!$check_query->EOF) {
                if (
                    $check_query->fields['zone_id'] < 1 ||
                    $check_query->fields['zone_id'] == $order->billing['zone_id']
                ) {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }

            if (!$check_flag) {
                $this->enabled = false;
            }
        }
    }

    /**
     * javascript_validation
     *
     * JS validation which does error-checking of data-entry if this module is selected for use
     * (Number, Owner, and CVV Lengths)
     *
     * >> Standard ZenCart
     * @return string
     * @author PayFast (Pty) Ltd
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function javascript_validation()
    {
        return (false);
    }

    /**
     * selection
     *
     * Displays payment method name along with Credit Card Information
     * Submission Fields (if any) on the Checkout Payment Page.
     *
     * >> Standard ZenCart
     * @return array
     * @author PayFast (Pty) Ltd
     */
    public function selection()
    {
        return array(
            'id'     => $this->code,
            'module' => MODULE_PAYMENT_PAYFAST_TEXT_CATALOG_LOGO,
            'icon'   => MODULE_PAYMENT_PAYFAST_TEXT_CATALOG_LOGO
        );
    }

    /**
     * pre_confirmation_check
     *
     * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number &
     * Expiration Date
     * Since payfast module is not collecting info, it simply skips this step.
     *
     * >> Standard ZenCart
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function pre_confirmation_check()
    {
        return (false);
    }

    /**
     * confirmation
     *
     * Display Credit Card Information on the Checkout Confirmation Page
     * Since none is collected for payfast before forwarding to payfast site, this is skipped
     *
     * >> Standard ZenCart
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function confirmation()
    {
        return (false);
    }

    /**
     * process_button
     *
     * Build the data and actions to process when the "Submit" button is
     * pressed on the order-confirmation screen.
     *
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     *
     * >> Standard ZenCart
     * @return string
     * @author PayFast (Pty) Ltd
     */
    public function process_button()
    {
        // Variable initialization
        global $db, $order, $currencies, $currency;
        $data        = array();
        $buttonArray = array();

        $merchantId  = MODULE_PAYMENT_PAYFAST_MERCHANT_ID;
        $merchantKey = MODULE_PAYMENT_PAYFAST_MERCHANT_KEY;

        // Create URLs
        $returnUrl = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'referer=payfast', 'SSL');
        $cancelUrl = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
        $notifyUrl = zen_href_link('payfast_itn_handler.php', '', 'SSL', false, false, true);

        //// Set the currency and get the order amount
        $currency                   = 'ZAR';
        $currencyDecPlaces          = $currencies->get_decimal_places($currency);
        $this->totalsum             = $order->info['total'];
        $this->transaction_currency = $currency;
        $this->transaction_amount   = ($this->totalsum * $currencies->get_value($currency));

        //// Generate the order description
        $orderDescription = '';

        foreach ($order->products as $product) {
            $price    = round($product['final_price'] * (100 + $product['tax']) / 100, 2);
            $priceStr = number_format($price, $currencyDecPlaces);

            $orderDescription .= $product['qty'] . ' x ' . $product['name'];

            if ($product['qty'] > 1) {
                $linePrice    = $price * $product['qty'];
                $linePriceStr = number_format($linePrice, $currencyDecPlaces);

                $orderDescription .= ' @ ' . $priceStr . 'ea = ' . $linePriceStr;
            } else {
                $orderDescription .= ' = ' . $priceStr;
            }

            $orderDescription .= '; ';
        }

        $orderDescription .= 'Shipping = ' . number_format($order->info['shipping_cost'], $currencyDecPlaces) . '; ';
        $orderDescription .= 'Total= ' . number_format($this->transaction_amount, $currencyDecPlaces) . '; ';


        //// Save the session (and remove expired sessions)
        pf_removeExpiredSessions();
        $tsExpire = strtotime('+' . PF_SESSION_LIFE . ' days');


        // Delete existing record (if it exists)
        $sql =
            self::DELETE_LITERAL . TABLE_PAYFAST_SESSION . "
            WHERE `session_id` = '" . zen_db_input(zen_session_id()) . "'";
        $db->Execute($sql);

        // patch for multi-currency - AGB 19/07/13 - see also the ITN handler
        $_SESSION['payfast_amount'] = number_format($this->transaction_amount, $currencyDecPlaces, '.', '');

        // remove amp; before POSTing to PayFast
        $cancelUrl = str_replace("amp;", "", $cancelUrl);
        $returnUrl = str_replace("amp;", "", $returnUrl);

        //// Set the data
        $mPaymentId = pf_createUUID();
        $data       = array(
            // Merchant fields
            'merchant_id'   => $merchantId,
            'merchant_key'  => $merchantKey,
            'return_url'    => $returnUrl,
            'cancel_url'    => $cancelUrl,
            'notify_url'    => $notifyUrl,

            // Customer details
            'name_first'    => replace_accents($order->customer['firstname']),
            'name_last'     => replace_accents($order->customer['lastname']),
            'email_address' => $order->customer['email_address'],

            'm_payment_id'     => $mPaymentId,
            'amount'           => number_format($this->transaction_amount, $currencyDecPlaces, '.', ''),

            // Item Details
            'item_name'        => MODULE_PAYMENT_PAYFAST_PURCHASE_DESCRIPTION_TITLE . $mPaymentId,
            'item_description' => substr($orderDescription, 0, 254),
            'custom_str1'      => PF_MODULE_NAME . '_' . PF_MODULE_VER,
            'custom_str2'      => zen_session_name() . '=' . zen_session_id(),
        );

        $_SESSION['guest_detail'] = json_encode($_POST);

        $sql =
            self::INSERT_LITERAL . TABLE_PAYFAST_SESSION . "
                ( session_id, saved_session, expiry )
            VALUES (
                '" . zen_db_input(zen_session_id()) . "',
                '" . base64_encode(serialize($_SESSION)) . "',
                '" . date(PF_FORMAT_DATETIME_DB, $tsExpire) . "' )";
        $db->Execute($sql);

        $pfOutput = '';
        // Create output string
        foreach ($data as $name => $value) {
            $pfOutput .= $name . '=' . urlencode(trim($value)) . '&';
        }

        $passPhrase = MODULE_PAYMENT_PAYFAST_PASSPHRASE;

        $pfOutput = substr($pfOutput, 0, -1);

        if (!empty($passPhrase)) {
            $pfOutput = $pfOutput . "&passphrase=" . urlencode($passPhrase);
        }

        $data['signature'] = md5($pfOutput);
        pflog("Data to send:\n" . print_r($data, true));


        //// Check the data and create the process button array

        foreach ($data as $name => $value) {
            // Remove quotation marks
            $value = str_replace('"', '', $value);

            $buttonArray[] = zen_draw_hidden_field($name, $value);
        }

        $processButtonString = implode("\n", $buttonArray) . "\n";

        return ($processButtonString);
    }

    /**
     * before_process
     *
     * Store transaction info to the order and process any results that come
     * back from the payment gateway
     *
     * >> Standard ZenCart
     * >> Called when the user is returned from the payment gateway
     * @author PayFast (Pty) Ltd
     */
    public function before_process()
    {
        $pre = __METHOD__ . ' : ';
        pflog($pre . 'bof');

        // Variable initialization
        global $db, $order_total_modules, $insert_id;

        // If page was called correctly with "referer" tag
        if (isset($_GET['referer']) && strcasecmp($_GET['referer'], 'payfast') == 0) {
            $this->notify('NOTIFY_PAYMENT_PAYFAST_RETURN_TO_STORE');

            $this->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET', $insert_id);

            // Reset all session variables
            $_SESSION['cart']->reset(true);
            unset($_SESSION['sendto']);
            unset($_SESSION['billto']);
            unset($_SESSION['shipping']);
            unset($_SESSION['payment']);
            unset($_SESSION['comments']);
            unset($_SESSION['cot_gv']);
            $order_total_modules->clear_posts();

            $this->notify('NOTIFY_HEADER_END_CHECKOUT_PROCESS');

            // Redirect to the checkout success page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
        } else {
            $this->notify('NOTIFY_PAYMENT_PAYFAST_CANCELLED_DURING_CHECKOUT');

            // Remove the pending PayFast transaction from the table
            if (isset($_SESSION['pf_m_payment_id'])) {
                $sql =
                    self::DELETE_LITERAL . pf_getActiveTable() . "
                    WHERE `m_payment_id` = " . $_SESSION['pf_m_payment_id'] . "
                    LIMIT 1";
                $db->Execute($sql);

                unset($_SESSION['pf_m_payment_id']);
            }

            // Redirect to the payment page
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
        }
    }

    /**
     * check_referrer
     *
     * Checks referrer
     *
     * >> Standard ZenCart
     *
     * @param string $zf_domain
     *
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function check_referrer($zf_domain)
    {
        return (true);
    }

    /**
     * after_process
     *
     * Post-processing activities
     *
     * >> Standard ZenCart
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function after_process()
    {
        $pre = __METHOD__ . ' : ';
        pflog($pre . 'bof');

        $this->notify('NOTIFY_HEADER_START_CHECKOUT_PROCESS');

        // Set 'order not created' flag
        $_SESSION['order_created'] = '';

        return (false);
    }

    /**
     * Used to display error message details
     *
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function output_error()
    {
        return (false);
    }

    /**
     * Check to see whether module is installed
     *
     * >> Standard ZenCart
     * @return boolean
     * @author PayFast (Pty) Ltd
     */
    public function check()
    {
        // Variable initialization
        global $db;

        if (!isset($this->_check)) {
            $check_query  = $db->Execute(
                "SELECT `configuration_value`
                FROM " . TABLE_CONFIGURATION . "
                WHERE `configuration_key` = 'MODULE_PAYMENT_PAYFAST_STATUS'"
            );
            $this->_check = $check_query->RecordCount();
        }

        return ($this->_check);
    }

    /**
     * install
     *
     * Installs PayFast payment module in osCommerce and creates necessary
     * configuration fields which need to be supplied by store owner.
     *
     * >> Standard ZenCart
     * @author PayFast (Pty) Ltd
     */
    public function install()
    {
        // Variable Initialization
        global $db;

        //// Insert configuration values
        // MODULE_PAYMENT_PAYFAST_STATUS (Default = False)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION .
            "( configuration_title, configuration_key, configuration_value, configuration_description,
             configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Enable Payfast?', 'MODULE_PAYMENT_PAYFAST_STATUS', 'False',
             'Do you want to enable Payfast?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_MERCHANT_ID (Default = Generic sandbox credentials)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION .
            "( configuration_title, configuration_key, configuration_value, configuration_description,
 configuration_group_id, sort_order, date_added )
            VALUES( 'Merchant ID', 'MODULE_PAYMENT_PAYFAST_MERCHANT_ID', '10000100', 'Your Merchant ID from PayFast
            <br><span style=\"font-size: 0.9em; color: green;\">(Click <a href=\"https://my.payfast.co.za/login\"
             target=\"_blank\">here</a> to get yours. This is initially set to a test value for testing purposes.)
             </span>', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_MERCHANT_KEY (Default = Generic sandbox credentials)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
 configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Merchant Key', 'MODULE_PAYMENT_PAYFAST_MERCHANT_KEY', '46f0cd694581a',
             'Your Merchant Key from PayFast<br><span style=\"font-size: 0.9em; color: green;\">
             (Click <a href=\"https://my.payfast.co.za/login\" target=\"_blank\">here</a>
              to get yours. This is initially set to a test value for testing purposes.)</span>', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_PASSPHRASE
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Passphrase', 'MODULE_PAYMENT_PAYFAST_PASSPHRASE', '',
             'Only enter a Passphrase if you have one set on your PayFast account', '6', '0', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_SERVER (Default = Test)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Transaction Server', 'MODULE_PAYMENT_PAYFAST_SERVER', 'Test', 'Select the PayFast server to use',
             '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Test\'), ', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_SORT_ORDER (Default = 0)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Sort Display Order', 'MODULE_PAYMENT_PAYFAST_SORT_ORDER', '0', 'Sort order of display.
             Lowest is displayed first.', '6', '0', now())"
        );
        // MODULE_PAYMENT_PAYFAST_ZONE (Default = "-none-")
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added )
            VALUES( 'Payment Zone', 'MODULE_PAYMENT_PAYFAST_ZONE', '0', 'If a zone is selected, only enable this payment
             method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())"
        );
        // MODULE_PAYMENT_PAYFAST_PREPARE_ORDER_STATUS_ID
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added )
            VALUES( 'Set Preparing Order Status', 'MODULE_PAYMENT_PAYFAST_PREPARE_ORDER_STATUS_ID', '1', 'Set the status
             of prepared orders made with PayFast to this value', '6', '0',
              'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())"
        );
        // MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added )
            VALUES( 'Set Acknowledged Order Status', 'MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID', '2',
             'Set the status of orders made with PayFast to this value', '6', '0',
             'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())"
        );
        // MODULE_PAYMENT_PAYFAST_DEBUG (Default = False)
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, set_function, date_added )
            VALUES( 'Enable debugging?', 'MODULE_PAYMENT_PAYFAST_DEBUG', 'False', 'Do you want to enable debugging?',
             '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now() )"
        );
        // MODULE_PAYMENT_PAYFAST_DEBUG_EMAIL
        $db->Execute(
            self::INSERT_LITERAL . TABLE_CONFIGURATION . "( configuration_title, configuration_key, configuration_value,
             configuration_description, configuration_group_id, sort_order, date_added )
            VALUES( 'Debug email address', 'MODULE_PAYMENT_PAYFAST_DEBUG_EMAIL', '',
             'Where would you like debugging information emailed?', '6', '0', now() )"
        );

        //// Create tables
        $tables    = array();
        $result    = $db->Execute("SHOW TABLES LIKE 'payfast%'");
        $fieldName = 'Tables_in_' . DB_DATABASE . ' (payfast%)';

        while (!$result->EOF) {
            $tables[] = $result->fields[$fieldName];
            $result->MoveNext();
        }

        // Main payfast table
        if (!in_array(TABLE_PAYFAST, $tables)) {
            $db->Execute(
                self::CREATE_LITERAL . TABLE_PAYFAST . "` (
                  `id` INTEGER UNSIGNED NOT NULL auto_increment,
                  `m_payment_id` VARCHAR(36) NOT NULL,
                  `pf_payment_id` VARCHAR(36) NOT NULL,
                  `zc_order_id` INTEGER UNSIGNED DEFAULT NULL,
                  `amount_gross` DECIMAL(14,2) DEFAULT NULL,
                  `amount_fee` DECIMAL(14,2) DEFAULT NULL,
                  `amount_net` DECIMAL(14,2) DEFAULT NULL,
                  `payfast_data` TEXT DEFAULT NULL,
                  `timestamp` DATETIME DEFAULT NULL,
                  `status` VARCHAR(50) DEFAULT NULL,
                  `status_date` DATETIME DEFAULT NULL,
                  `status_reason` VARCHAR(255) DEFAULT NULL,
                  PRIMARY KEY( `id` ),
                  KEY `idx_m_payment_id` (`m_payment_id`),
                  KEY `idx_pf_payment_id` (`pf_payment_id`),
                  KEY `idx_zc_order_id` (`zc_order_id`),
                  KEY `idx_timestamp` (`timestamp`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
            );
        }

        // Payment status table
        if (!in_array(TABLE_PAYFAST_PAYMENT_STATUS, $tables)) {
            $db->Execute(
                self::CREATE_LITERAL . TABLE_PAYFAST_PAYMENT_STATUS . "` (
                  `id` INTEGER UNSIGNED NOT NULL,
                  `name` VARCHAR(50) NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
            );

            $db->Execute(
                self::INSERT_LITERAL . TABLE_PAYFAST_PAYMENT_STATUS . "`
                    ( `id`,`name` )
                VALUES
                    ( 1, 'COMPLETE' ),
                    ( 2, 'PENDING' ),
                    ( 3, 'FAILED' )"
            );
        }

        // Payment status history table
        if (!in_array(TABLE_PAYFAST_PAYMENT_STATUS_HISTORY, $tables)) {
            $db->Execute(
                self::CREATE_LITERAL . TABLE_PAYFAST_PAYMENT_STATUS_HISTORY . "`(
                  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                  `pf_order_id` INTEGER UNSIGNED NOT NULL,
                  `timestamp` DATETIME DEFAULT NULL,
                  `status` VARCHAR(50) DEFAULT NULL,
                  `status_reason` VARCHAR(255) DEFAULT NULL,
                  PRIMARY KEY( `id` ),
                  KEY `idx_pf_order_id` (`pf_order_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
            );
        }

        // Session table
        if (!in_array(TABLE_PAYFAST_SESSION, $tables)) {
            $db->Execute(
                self::CREATE_LITERAL . TABLE_PAYFAST_SESSION . "` (
                  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
                  `session_id` VARCHAR(100) NOT NULL,
                  `saved_session` MEDIUMBLOB NOT NULL,
                  `expiry` DATETIME NOT NULL,
                  PRIMARY KEY( `id` ),
                  KEY `idx_session_id` (`session_id`(36))
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
            );
        }

        // Testing table
        if (!in_array(TABLE_PAYFAST_TESTING, $tables)) {
            $db->Execute(
                self::CREATE_LITERAL . TABLE_PAYFAST_TESTING . "` (
                  `id` INTEGER UNSIGNED NOT NULL auto_increment,
                  `m_payment_id` VARCHAR(36) NOT NULL,
                  `pf_payment_id` VARCHAR(36) NOT NULL,
                  `zc_order_id` INTEGER UNSIGNED DEFAULT NULL,
                  `amount_gross` DECIMAL(14,2) DEFAULT NULL,
                  `amount_fee` DECIMAL(14,2) DEFAULT NULL,
                  `amount_net` DECIMAL(14,2) DEFAULT NULL,
                  `payfast_data` TEXT DEFAULT NULL,
                  `timestamp` DATETIME DEFAULT NULL,
                  `status` VARCHAR(50) DEFAULT NULL,
                  `status_date` DATETIME DEFAULT NULL,
                  `status_reason` VARCHAR(255) DEFAULT NULL,
                  PRIMARY KEY( `id` ),
                  KEY `idx_m_payment_id` (`m_payment_id`),
                  KEY `idx_pf_payment_id` (`pf_payment_id`),
                  KEY `idx_zc_order_id` (`zc_order_id`),
                  KEY `idx_timestamp` (`timestamp`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=latin1"
            );
        }

        $this->notify('NOTIFY_PAYMENT_PAYFAST_INSTALLED');
    }

    /**
     * remove
     *
     * Remove the module and all its settings. Leaves the tables which were
     * created as they will have information from past orders which is still
     * relevant and required.
     *
     * >> Standard ZenCart
     * @author PayFast (Pty) Ltd
     */
    public function remove()
    {
        // Variable Initialization
        global $db;

        // Remove all configuration variables
        $db->Execute(
            self::DELETE_LITERAL . TABLE_CONFIGURATION . "
            WHERE `configuration_key` LIKE 'MODULE\_PAYMENT\_PAYFAST\_%'"
        );

        $this->notify('NOTIFY_PAYMENT_PAYFAST_UNINSTALLED');
    }

    /**
     * keys
     *
     * Returns an array of the configuration keys for the module
     *
     * >> Standard osCommerce
     * @return array
     * @author PayFast (Pty) Ltd
     */
    public function keys()
    {
        // Variable initialization
        $keys = array(
            'MODULE_PAYMENT_PAYFAST_STATUS',
            'MODULE_PAYMENT_PAYFAST_MERCHANT_ID',
            'MODULE_PAYMENT_PAYFAST_MERCHANT_KEY',
            'MODULE_PAYMENT_PAYFAST_PASSPHRASE',
            'MODULE_PAYMENT_PAYFAST_SERVER',
            'MODULE_PAYMENT_PAYFAST_SORT_ORDER',
            'MODULE_PAYMENT_PAYFAST_ZONE',
            'MODULE_PAYMENT_PAYFAST_PREPARE_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYFAST_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYFAST_DEBUG',
            'MODULE_PAYMENT_PAYFAST_DEBUG_EMAIL',
        );

        return ($keys);
    }

    /**
     * after_order_create
     *
     * >> Standard osCommerce
     * @author PayFast (Pty) Ltd
     */
    public function after_order_create($insert_id)
    {
        $pre = __METHOD__ . ' : ';
        pflog($pre . 'bof');

        return (false);
    }
}
