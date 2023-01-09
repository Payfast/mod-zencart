<?php

/**
 * PayFast ITN specific session stuff
 *
 * @package initSystem
 * Copyright (c) 2023 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code
 * or part thereof in any way.
 * @version $Id: init_PayFast_sessions.php
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

/**
 * Begin processing. Add notice to log if logging enabled.
 */
pflog(
    'ITN processing initiated. ' . "\n" .
    '- Originating IP: ' . $_SERVER['REMOTE_ADDR'] . ' ' .
    (SESSION_IP_TO_HOST_ADDRESS == 'true' ? @gethostbyaddr($_SERVER['REMOTE_ADDR']) : '')
);

if (!$_POST) {
    pflog(
        'ITN Fatal Error :: No POST data available -- ' .
        'Most likely initiated by browser and not PayFast.'
    );
}

$session_post    = isset($_POST['custom_str2']) ? $_POST['custom_str2'] : '=';
$session_stuff   = explode('=', $session_post);
$itnFoundSession = true;
