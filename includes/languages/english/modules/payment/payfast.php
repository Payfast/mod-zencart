<?php
/**
 * payfast.php
 *
 * Lanugage defines for PayFast payment module
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

define( 'MODULE_PAYMENT_PAYFAST_TEXT_ADMIN_TITLE', 'PayFast' );
define( 'MODULE_PAYMENT_PAYFAST_TEXT_CATALOG_TITLE', 'PayFast' );
define( 'MODULE_PAYMENT_PAYFAST_BUTTON_IMG', DIR_WS_IMAGES .'payfast/PayFast Logo Colour.png' );

if( IS_ADMIN_FLAG === true )
    define( 'MODULE_PAYMENT_PAYFAST_TEXT_DESCRIPTION',
        '<img src="../'. MODULE_PAYMENT_PAYFAST_BUTTON_IMG .'" style="height:5rem;"> <br>'.
        '<br />'.
        'Manage your '.
        '<a href="https://www.payfast.co.za/acc/account" target="_blank">PayFast account</a> or '.
        '<a href="http://www.payfast.co.za/user/register" target="_blank"> register</a> for a PayFast account');
else
    define( 'MODULE_PAYMENT_PAYFAST_TEXT_DESCRIPTION', '<strong>PayFast</strong>');

define( 'MODULE_PAYMENT_PAYFAST_BUTTON_ALT', 'Checkout with PayFast' );
define( 'MODULE_PAYMENT_PAYFAST_ACCEPTANCE_MARK_TEXT', '' );

define( 'MODULE_PAYMENT_PAYFAST_TEXT_CATALOG_LOGO',
    '<a href="http://www.payfast.co.za" style="border: 0;" target="_blank">'.
    '<img src="'. MODULE_PAYMENT_PAYFAST_BUTTON_IMG .'"'.
    ' alt="'. MODULE_PAYMENT_PAYFAST_BUTTON_ALT .'"'.
    ' title="' . MODULE_PAYMENT_PAYFAST_BUTTON_ALT .'"'.
    ' style="vertical-align: text-bottom; border: 0px;" border="0"/></a>&nbsp;'.
    '<span class="smallText">' . MODULE_PAYMENT_PAYFAST_ACCEPTANCE_MARK_TEXT . '</span>' );

define( 'MODULE_PAYMENT_PAYFAST_ENTRY_FIRST_NAME', 'First Name:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_LAST_NAME', 'Last Name:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_BUSINESS_NAME', 'Business Name:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_NAME', 'Address Name:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_STREET', 'Address Street:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_CITY', 'Address City:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_STATE', 'Address State:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_ZIP', 'Address Zip:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_COUNTRY', 'Address Country:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_EMAIL_ADDRESS', 'Payer Email:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_EBAY_ID', 'Ebay ID:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYER_ID', 'Payer ID:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYER_STATUS', 'Payer Status:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_ADDRESS_STATUS', 'Address Status:' );

define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYMENT_TYPE', 'Payment Type:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYMENT_STATUS', 'Payment Status:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PENDING_REASON', 'Pending Reason:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_INVOICE', 'Invoice:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYMENT_DATE', 'Payment Date:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_CURRENCY', 'Currency:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_GROSS_AMOUNT', 'Gross Amount:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PAYMENT_FEE', 'Payment Fee:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_CART_ITEMS', 'Cart items:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_TXN_TYPE', 'Trans. Type:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_TXN_ID', 'Trans. ID:' );
define( 'MODULE_PAYMENT_PAYFAST_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:' );

define( 'MODULE_PAYMENT_PAYFAST_PURCHASE_DESCRIPTION_TITLE', STORE_NAME .' purchase, Order #' );
?>