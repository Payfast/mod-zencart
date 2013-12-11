<?php
if (!defined('IS_ADMIN_FLAG'))
    die('Illegal Access');

if( MODULE_PAYMENT_PAYFAST_STATUS == 'True' )
{
    $za_contents[] = array(
        'text' => 'PayFast Orders',
        'link' => zen_href_link( 'payfast.php', '', 'NONSSL' )
        );
}
?>