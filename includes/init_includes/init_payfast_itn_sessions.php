<?php
/**
 * PayFast ITN specific session stuff
 *
 * @package initSystem
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 * @version $Id: init_paypal_ipn_sessions.php 6598 2007-07-15 00:34:08Z drbyte $
 */
if( !defined( 'IS_ADMIN_FLAG' ) ) {
  die('Illegal Access');
}

/**
 * Begin processing. Add notice to log if logging enabled.
 */
pflog(
    'ITN processing initiated. ' ."\n".
    '- Originating IP: '. $_SERVER['REMOTE_ADDR'] .' '.
    ( SESSION_IP_TO_HOST_ADDRESS == 'true' ? @gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) : '' ) .
    ( $_SERVER['HTTP_USER_AGENT'] == '' ? '' : "\n" .
    '- Browser/User Agent: ' . $_SERVER['HTTP_USER_AGENT'] ) );

if( !$_POST )
{
    pflog( 'ITN Fatal Error :: No POST data available -- '.
        'Most likely initiated by browser and not PayFast.' );
}

$session_post = isset( $_POST['custom_str1']) ? $_POST['custom_str1'] : '=' ;
$session_stuff = explode( '=', $session_post );
$itnFoundSession = true;
?>