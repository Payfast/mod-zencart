<?php

/**
 * payfast.php
 *
 * Admin module for querying payments (and associated orders) made using the
 * PayFast payment module.
 *
 * Copyright (c) 2023 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in
 * conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason,
 * you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or
 * part thereof in any way.
 *
 */

// Max results to show per page
// phpcs:disable
define('MAX_DISPLAY_SEARCH_RESULTS_PAYFAST', 10);
define('FILENAME_PAYFAST', 'payfast.php');
// phpcs:enable
const PAGE_LITERAL       = 'page=';
const ORDER_ID_LITERAL   = '&pf_order_id=';
const STATUS_LITERAL     = '&pf_status=';
const SORT_ORDER_LITERAL = '&pf_sort_order=';
const TABLE_CONTENT_HTML = '<td class="dataTableContent">';
const TD_END_HTML        = '</td>';

// Include ZenCart header
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
require('includes/application_top.php');

// Create sort order array
$payfastSortOrderArray = array(
    array('id' => '0', 'text' => TEXT_SORT_PAYFAST_ID_DESC),
    array('id' => '1', 'text' => TEXT_SORT_PAYFAST_ID),
    array('id' => '2', 'text' => TEXT_SORT_ZEN_ORDER_ID_DESC),
    array('id' => '3', 'text' => TEXT_SORT_ZEN_ORDER_ID),
    array('id' => '4', 'text' => TEXT_PAYMENT_AMOUNT_DESC),
    array('id' => '5', 'text' => TEXT_PAYMENT_AMOUNT)
);

// Set sort order
$selectedSortOrder =
    isset($_GET['pf_sort_order']) ? $_GET['pf_sort_order'] : 0;

// Create 'order by' statement based on sort order
switch ($selectedSortOrder) {
    case 0:
        $sqlOrderBy = " ORDER BY p.`id` DESC";
        break;
    case 1:
        $sqlOrderBy = " ORDER BY p.`id`";
        break;
    case 2:
        $sqlOrderBy = " ORDER BY p.`zc_order_id` DESC, p.id";
        break;
    case 3:
        $sqlOrderBy = " ORDER BY p.`zc_order_id`, p.id";
        break;
    case 4:
        $sqlOrderBy = " ORDER BY p.`amount_gross` DESC";
        break;
    case 5:
        $sqlOrderBy = " ORDER BY p.`amount_gross`";
        break;
    default:
        $sqlOrderBy = " ORDER BY p.`id` DESC";
        break;
}

$action         = isset($_GET['action']) ? $_GET['action'] : '';
$selectedStatus = isset($_GET['pf_status']) ? $_GET['pf_status'] : '';

require(DIR_FS_CATALOG_MODULES . 'payment/payfast.php');

// Create payment statuses array
$sql    =
    "SELECT `name` FROM " . TABLE_PAYFAST_PAYMENT_STATUS;
$result = $db->Execute($sql);

$paymentStatuses = array();
while (!$result->EOF) {
    $paymentStatuses[] = array(
        'id'   => $result->fields['name'],
        'text' => $result->fields['name']
    );
    $result->MoveNext();
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php
echo HTML_PARAMS; ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php
    echo CHARSET; ?>">
    <title><?php
        echo TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
    <script language="javascript" src="includes/menu.js"></script>
    <script language="javascript" src="includes/general.js"></script>
    <script type="text/javascript">
      function init() {
        cssjsmenu('navbar')
        if (document.getElementById) {
          var kill = document.getElementById('hoverJS')
          kill.disabled = true
        }
      }
    </script>
</head>
<body style="margin: 0;" onLoad="SetFocus(), init();">

<!-- header //-->
<?php
require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table style="width: 100%; border: 0;">
    <tr>
        <!-- body_text //-->
        <td style="vertical-align: top; width: 100%;">

            <table style="width: 100%; border: 0;">
                <tr>
                    <td>

                        <table>
                            <tr>
                                <td class="pageHeading"><?php
                                    echo HEADING_ADMIN_TITLE; ?></td>
                                <td class="pageHeading"><?php
                                    echo zen_draw_separator(
                                        'pixel_trans.gif',
                                        HEADING_IMAGE_WIDTH,
                                        HEADING_IMAGE_HEIGHT
                                    ); ?></td>
                                <td class="smallText">
                                    <?php
                                    echo
                                        zen_draw_form('pf_status', FILENAME_PAYFAST, '', 'get') .
                                        HEADING_PAYMENT_STATUS . ' ' .
                                        zen_draw_pull_down_menu(
                                            'pf_status',
                                            array_merge(array(array('id' => '', 'text' => TEXT_ALL)), $paymentStatuses),
                                            $selectedStatus,
                                            'onChange="this.form.submit();"'
                                        ) .
                                        zen_hide_session_id() .
                                        zen_draw_hidden_field('pf_sort_order', $_GET['pf_sort_order']) .
                                        '</form>';

                                    echo
                                        '&nbsp;&nbsp;&nbsp;' . TEXT_PAYFAST_SORT_ORDER_INFO .
                                        zen_draw_form('pf_sort_order', FILENAME_PAYFAST, '', 'get') . '&nbsp;&nbsp;' .
                                        zen_draw_pull_down_menu(
                                            'pf_sort_order',
                                            $payfastSortOrderArray,
                                            $resetPayfastSortOrder,
                                            'onChange="this.form.submit();"'
                                        ) .
                                        zen_hide_session_id() .
                                        zen_draw_hidden_field('pf_status', $_GET['pf_status']) .
                                        '</form>';
                                    ?>
                                </td>
                                <td class="pageHeading">
                                    <?php
                                    echo zen_draw_separator(
                                        'pixel_trans.gif',
                                        HEADING_IMAGE_WIDTH,
                                        HEADING_IMAGE_HEIGHT
                                    ); ?></td>
                            </tr>
                        </table>

                    </td>
                </tr>
                <tr>
                    <td>

                        <table>
                            <tr>
                                <td>

                                    <table>
                                        <tr class="dataTableHeadingRow">
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_ORDER_NUMBER; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_MERCHANT_REF; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_STATUS; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_AMOUNT_GROSS; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_AMOUNT_FEE; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_AMOUNT_NET; ?></td>
                                            <td class="dataTableHeadingContent">
                                                <?php
                                                echo TABLE_HEADING_ACTION; ?>&nbsp;
                                            </td>
                                        </tr>
                                        <?php
                                        if (zen_not_null($selectedStatus)) {
                                            $sqlSearch = " AND p.status = '" . zen_db_prepare_input(
                                                $selectedStatus
                                            ) . "'";

                                            if ($selectedStatus !== 'pending' && $selectedStatus !== 'completed') {
                                                $sql =
                                                    "SELECT p.*
                    FROM " . TABLE_PAYFAST . " AS p, " . TABLE_ORDERS . " AS o
                    WHERE o.`orders_id` = p.`zc_order_id`" .
                                                    $sqlSearch .
                                                    $sqlOrderBy;
                                            }
                                        } else {
                                            $sql =
                                                "SELECT p.*
        FROM `" . TABLE_PAYFAST . "` AS p
          LEFT JOIN `" . TABLE_ORDERS . "` AS o ON o.`orders_id` = p.`zc_order_id`" .
                                                $sqlOrderBy;
                                        }

                                        $split = new splitPageResults(
                                            $_GET['page'],
                                            MAX_DISPLAY_SEARCH_RESULTS_PAYFAST,
                                            $sql,
                                            $qryNumRows
                                        );
                                        $trans = $db->Execute($sql);

                                        while (!$trans->EOF) {
                                            $out = '';

                                            if (
                                                (!isset($_GET['pf_order_id']) ||
                                                 (isset($_GET['pf_order_id']) &&
                                                  ($_GET['pf_order_id'] == $trans->fields['id']))) &&
                                                !isset($info)
                                            ) {
                                                $info = new objectInfo($trans->fields);
                                            }

                                            //
                                            if (
                                                isset($info) && is_object(
                                                    $info
                                                ) && ($trans->fields['id'] == $info->id)
                                            ) {
                                                $out .=
                                                    '              ' .
                                                    '<tr id="defaultSelected" class="dataTableRowSelected"' .
                                                    ' onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)"'
                                                    . ' onclick="document.location.href=\'' .
                                                    zen_href_link(
                                                        FILENAME_ORDERS,
                                                        PAGE_LITERAL . $_GET['page'] .
                                                        ORDER_ID_LITERAL . $info->id .
                                                        '&oID=' . $info->zc_order_id .
                                                        '&action=edit' .
                                                        (zen_not_null(
                                                            $selectedStatus
                                                        ) ? STATUS_LITERAL . $selectedStatus : '') .
                                                        (zen_not_null(
                                                            $selectedSortOrder
                                                        ) ? SORT_ORDER_LITERAL . $selectedSortOrder : '')
                                                    ) .
                                                    '\'">' . "\n";
                                            } else {
                                                $out .=
                                                    '              ' .
                                                    '<tr class="dataTableRow" onmouseover="rowOverEffect(this)"' .
                                                    ' onmouseout="rowOutEffect(this)"' .
                                                    ' onclick="document.location.href=\'' .
                                                    zen_href_link(
                                                        FILENAME_PAYFAST,
                                                        PAGE_LITERAL . $_GET['page'] .
                                                        ORDER_ID_LITERAL . $trans->fields['id'] .
                                                        (zen_not_null(
                                                            $selectedStatus
                                                        ) ? STATUS_LITERAL . $selectedStatus : '') .
                                                        (zen_not_null(
                                                            $selectedSortOrder
                                                        ) ? SORT_ORDER_LITERAL . $selectedSortOrder : '')
                                                    ) .
                                                    '\'">' . "\n";
                                            }

                                            $out .=
                                                // ZenCart order id
                                                TABLE_CONTENT_HTML . $trans->fields['zc_order_id'] . TD_END_HTML .

                                                // PayFast m_payment_id
                                                TABLE_CONTENT_HTML . $trans->fields['m_payment_id'] . TD_END_HTML .

                                                TABLE_CONTENT_HTML .
                                                $trans->fields['status'] . TD_END_HTML .

                                                // Amount Gross
                                                TABLE_CONTENT_HTML .
                                                number_format($trans->fields['amount_gross'], 2) . TD_END_HTML .

                                                // Amount Fee
                                                TABLE_CONTENT_HTML .
                                                number_format($trans->fields['amount_fee'], 2) . TD_END_HTML .

                                                // Amount Net
                                                TABLE_CONTENT_HTML .
                                                number_format($trans->fields['amount_net'], 2) . TD_END_HTML .

                                                TABLE_CONTENT_HTML;

                                            if (
                                                isset($info) && is_object(
                                                    $info
                                                ) && ($trans->fields['id'] == $info->id)
                                            ) {
                                                $out .= zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif');
                                            } else {
                                                $out .=
                                                    '<a href="' .
                                                    zen_href_link(
                                                        FILENAME_PAYFAST,
                                                        PAGE_LITERAL . $_GET['page'] .
                                                        '&ipnID=' . $trans->fields['id']
                                                    ) .
                                                    (zen_not_null(
                                                        $selectedStatus
                                                    ) ? STATUS_LITERAL . $selectedStatus : '') .
                                                    (zen_not_null(
                                                        $selectedSortOrder
                                                    ) ? SORT_ORDER_LITERAL . $selectedSortOrder : '') .
                                                    '">' .
                                                    zen_image(
                                                        DIR_WS_IMAGES . 'icon_info.gif',
                                                        IMAGE_ICON_INFO
                                                    ) . '</a>';
                                            }

                                            $out .= '</td></tr>';

                                            echo $out;

                                            $trans->MoveNext();
                                        }
                                        ?>
                                        <tr>
                                            <td colspan="5">
                                                <table>
                                                    <tr>
                                                        <td class="smallText">
                                                            <?php
                                                            echo $split->display_count(
                                                                $qryNumRows,
                                                                MAX_DISPLAY_SEARCH_RESULTS_PAYFAST,
                                                                $_GET['page'],
                                                                TEXT_DISPLAY_NUMBER_OF_TRANSACTIONS
                                                            ); ?></td>
                                                        <td class="smallText">
                                                            <?php
                                                            echo $split->display_links(
                                                                $qryNumRows,
                                                                MAX_DISPLAY_SEARCH_RESULTS_PAYFAST,
                                                                MAX_DISPLAY_PAGE_LINKS,
                                                                $_GET['page'],
                                                                (zen_not_null(
                                                                    $selectedStatus
                                                                ) ? STATUS_LITERAL . $selectedStatus : '') .
                                                                (zen_not_null(
                                                                    $selectedSortOrder
                                                                ) ? SORT_ORDER_LITERAL . $selectedSortOrder : '')
                                                            ); ?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <?php
                                $heading  = array();
                                $contents = array();

                                switch ($action) {
                                    case 'edit':
                                    case 'delete':
                                    case 'new':
                                        break;
                                    default:
                                        if (is_object($info)) {
                                            $heading[] = array(
                                                'text' =>
                                                    '<strong>' . TEXT_INFO_PAYFAST_HEADING . ' #' .
                                                    $info->id . '</strong>'
                                            );

                                            $sql         =
                                                "SELECT *
                FROM `" . TABLE_PAYFAST_PAYMENT_STATUS_HISTORY . "`
                WHERE `pf_order_id` = '" . $info->id . "'";
                                            $statHist    = $db->Execute($sql);
                                            $noOfRecords = $statHist->RecordCount();

                                            $contents[] = array(
                                                'align' => 'center',
                                                'text'  => '<a href="' .
                                                           zen_href_link(
                                                               FILENAME_ORDERS,
                                                               zen_get_all_get_params(array('ipnID', 'action')) .
                                                               'oID=' . $info->zc_order_id .
                                                               ORDER_ID_LITERAL . $info->id .
                                                               '&action=edit' . '&referer=ipn'
                                                           ) .
                                                           '">' .
                                                           zen_image_button('button_orders.gif', IMAGE_ORDERS) . '</a>'
                                            );
                                            $contents[] = array(
                                                'text' => '<br>' .
                                                          TABLE_HEADING_NUM_HISTORY_ENTRIES . ': ' . $noOfRecords
                                            );
                                            $i          = 1;

                                            while (!$statHist->EOF) {
                                                $data = new objectInfo($statHist->fields);

                                                $contents[] = array(
                                                    'text' => '<br>' . TABLE_HEADING_ENTRY_NUM . ': ' . $i
                                                );
                                                $contents[] = array(
                                                    'text' => TABLE_HEADING_DATE_ADDED . ': ' . zen_datetime_short(
                                                        $data->timestamp
                                                    )
                                                );
                                                $contents[] = array(
                                                    'text' => TABLE_HEADING_STATUS . ': ' . $data->status
                                                );
                                                $contents[] = array(
                                                    'text' => TABLE_HEADING_STATUS_REASON . ': ' . $data->status_reason
                                                );
                                                $i++;

                                                $statHist->MoveNext();
                                            }
                                        }
                                        break;
                                }

                                if ((zen_not_null($heading)) && (zen_not_null($contents))) {
                                    echo '            <td width="25%" valign="top">' . "\n";

                                    $box = new box();
                                    echo $box->infoBox($heading, $contents);

                                    echo '            </td>' . "\n";
                                }
                                ?>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

        </td>
        <!-- body_text_eof //-->
    </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php
require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>

</body>
</html>
<?php
require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
