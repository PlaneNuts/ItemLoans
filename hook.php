<?php

/**
 * -------------------------------------------------------------------------
 * itemloans plugin for GLPI
 * Copyright (C) 2025 by the itemloans Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */
use GlpiPlugin\Itemloans\Loans;
use GlpiPlugin\Itemloans\Profile as ItemLoans_Profile;
use GlpiPlugin\Itemloans\NotificationTargetLoans;
use GlpiPlugin\Itemloans\ConfirmationSummaryCronTask;
use GlpiPlugin\Itemloans\NewLoanSummaryCronTask;
use GlpiPlugin\Itemloans\OverdueReminderCronTask;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_itemloans_install()
{
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();

    // instantiate migration with version
    $migration = new Migration(PLUGIN_ITEMLOANS_VERSION);

    // create table only if it does not exist yet!
    $table = Loans::getTable();
    if (!$DB->tableExists($table)) {
        //table creation query
        $query = "CREATE TABLE `$table` ( 
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
                    `entities_id` INT(11) UNSIGNED NOT NULL ,
                    `glpi_item_type` VARCHAR(100) NOT NULL ,
                    `glpi_otherserial` VARCHAR(100) NOT NULL ,
                    `glpi_name` VARCHAR(100) NOT NULL ,
                    `glpi_item_id` INT(11) UNSIGNED NOT NULL ,
                    `date_loaned_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `return_by_date` TIMESTAMP NULL DEFAULT NULL ,
                    `send_reminder` BOOLEAN NOT NULL DEFAULT FALSE ,
                    `ask_to_confirm` BOOLEAN NOT NULL DEFAULT FALSE ,
                    `confirmation_sent` BOOLEAN NOT NULL DEFAULT FALSE ,
                    `confirmed_by_user` BOOLEAN NOT NULL DEFAULT FALSE ,
                    `loan_user_id` INT(11) UNSIGNED NOT NULL ,
                    `loan_submitted_by_id` INT(11) UNSIGNED NOT NULL ,
                    `loan_returned_by_id` INT(11) UNSIGNED ,
                    `date_loaned_returned` TIMESTAMP NULL DEFAULT NULL ,
                    `loan_returned` BOOLEAN NOT NULL DEFAULT FALSE ,
                PRIMARY KEY (`id`)) ENGINE = InnoDB
                DEFAULT CHARSET={$default_charset}
                COLLATE={$default_collation}";
        $DB->queryOrDie($query, $DB->error());
    }

    //execute the whole migration
    $migration->executeMigration();

    // Add confirmation_sent column if it does not exist
    $table = Loans::getTable();
    if ($DB->tableExists($table) && !$DB->fieldExists($table, 'confirmation_sent')) {
        $query_add_col = "ALTER TABLE `$table` ADD `confirmation_sent` BOOLEAN NOT NULL DEFAULT FALSE AFTER `confirmed_by_user`;";
        $DB->queryOrDie($query_add_col, $DB->error());
    }

    //add rights
    foreach (ItemLoans_Profile::getAllRights() as $right) {
        ProfileRight::deleteProfileRights([$right['field']]);
        ProfileRight::addProfileRights([$right['field']]);
    }

    $itemtype = 'Item Loan';
    $notification_definitions = [
        'overdue_loan' => [
            'name'      => 'Item Loan Overdue',
            'event'     => 'overdue_loan',
            'subject'   => 'Overdue Loan Reminder: ##loan.item_name##',
            'body'      => '<p>Hello ##loan.user_name##,</p><p>Your loan for the following device has expired. Please return it to the support department at your earliest convienence.</p><ul><li><strong>Item:</strong> ##loan.item_name##</li><li><strong>Inventory Number:</strong> ##loan.item_inventory_number##</li><li><strong>Return by Date:</strong> ##loan.return_by_date##</li></ul><p>Please return the item as soon as possible.</p><p>Thank you.</p>',
        ],
        'item_confirmation_summary' => [
            'name'      => 'Item Loan Confirmation Summary',
            'event'     => 'item_confirmation_summary',
            'subject'   => 'GLPI Loan Confirmation Summary',
            'body'      => '<p>Hello ##loan.user_name##,</p><p>The following devices have been checked out to you and are waiting for your confirmation of reception. Please follow the link below to confirm your items.</p>##loans.confirmation_table##<p>Please click the link below to confirm the loans:</p><p><a href="##loan.confirmation_link##">Confirm Loans</a></p><p>Thank you.</p>',
        ],
        'new_loan_summary' => [
            'name'      => 'Item Loan Confirm New Item',
            'event'     => 'new_loan_summary',
            'subject'   => 'New Items Loaned to You',
            'body'      => '<p>Hello ##loan.user_name##,</p><p>The following items have just been checked out to you:</p>##loans.tableOfDevices##<p>Please confirm your reception of these items from the link below:</p><p><a href="##loan.confirmation_link##">Confirm Loans</a></p><p>Thank you.</p>',
        ],
    ];

    foreach ($notification_definitions as $def) {
        $tpl_id = null;
        $query_tpl = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `name` = '{$def['name']}'";
        $result_tpl = $DB->query($query_tpl);
        if ($DB->numrows($result_tpl) > 0) {
            $tpl_id = $DB->result($result_tpl, 0, 'id');
        } else {
            $query_insert_tpl = "INSERT INTO `glpi_notificationtemplates` (`name`, `itemtype`, `date_mod`)
                                 VALUES ('{$def['name']}', '$itemtype', NOW())";
            $DB->query($query_insert_tpl);
            $result_tpl_new = $DB->query($query_tpl);
            $tpl_id = $DB->result($result_tpl_new, 0, 'id');

            if ($tpl_id) {
                $query_insert_trans = "INSERT INTO `glpi_notificationtemplatetranslations` 
                                        (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
                                        VALUES ($tpl_id, '{$DB->escape($def['subject'])}', '', '{$DB->escape($def['body'])}')";
                $DB->query($query_insert_trans);
            }
        }

        if ($tpl_id) {
            $notif_id = null;
            $query_notif = "SELECT `id` FROM `glpi_notifications` WHERE `name` = '{$def['name']}'";
            $result_notif = $DB->query($query_notif);
            if ($DB->numrows($result_notif) > 0) {
                $notif_id = $DB->result($result_notif, 0, 'id');
            } else {
                $query_insert_notif = "INSERT INTO `glpi_notifications` (`name`, `itemtype`, `event`, `is_active`)
                                    VALUES ('{$def['name']}', '$itemtype', '{$def['event']}', 1)";
                $DB->query($query_insert_notif);
                $result_notif_new = $DB->query($query_notif);
                $notif_id = $DB->result($result_notif_new, 0, 'id');

                if ($notif_id) {
                    $query_insert_link = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `notificationtemplates_id`, `mode`)
                                        VALUES ($notif_id, $tpl_id, 'mailing')";
                    $DB->query($query_insert_link);

                    $query_insert_target = "INSERT INTO `glpi_notificationtargets` (`notifications_id`, `type`, `items_id`)
                                            VALUES ($notif_id, " . \Notification::USER_TYPE . ", " . \GlpiPlugin\Itemloans\NotificationTargetLoans::LOAN_USER_RECIPIENT . ")";
                    $DB->query($query_insert_target);
                }
            }
        }
    }

    
    CronTask::register(ConfirmationSummaryCronTask::class, 'LoanConfirmSummary', DAY_TIMESTAMP);
    CronTask::register(NewLoanSummaryCronTask::class, 'LoanConfirmNew', DAY_TIMESTAMP);
    CronTask::register(OverdueReminderCronTask::class, 'LoanConfirmOverdue', DAY_TIMESTAMP);
    
    return true;
}


/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_itemloans_uninstall()
{
    global $DB;

    $tables = [
        Loans::getTable(),
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQueryOrDie(
                "DROP TABLE `$table`",
                $DB->error()
            );
        }
    }

    $itemtype = 'GlpiPlugin\Itemloans\Loans';

    $query_tpl = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype` = '$itemtype'";
    $result_tpl = $DB->query($query_tpl);
    if ($DB->numrows($result_tpl) > 0) {
        while ($row = $DB->fetchAssoc($result_tpl)) {
            $tpl_id = $row['id'];
            $DB->query("DELETE FROM `glpi_notificationtemplatetranslations` WHERE `notificationtemplates_id` = $tpl_id");
            $DB->query("DELETE FROM `glpi_notifications_notificationtemplates` WHERE `notificationtemplates_id` = $tpl_id");
        }
        $DB->query("DELETE FROM `glpi_notificationtemplates` WHERE `itemtype` = '$itemtype'");
    }

    $query_notif = "SELECT `id` FROM `glpi_notifications` WHERE `itemtype` = '$itemtype'";
    $result_notif = $DB->query($query_notif);
    if ($DB->numrows($result_notif) > 0) {
        while ($row = $DB->fetchAssoc($result_notif)) {
            $notif_id = $row['id'];
            $DB->query("DELETE FROM `glpi_notificationtargets` WHERE `notifications_id` = $notif_id");
            $DB->query("DELETE FROM `glpi_notifications_notificationtemplates` WHERE `notifications_id` = $notif_id");
        }
        $DB->query("DELETE FROM `glpi_notifications` WHERE `itemtype` = '$itemtype'");
    }

    //remove rights
    foreach (ItemLoans_Profile::getAllRights() as $right) {
        ProfileRight::deleteProfileRights([$right['field']]);
    }

    CronTask::unregister(ConfirmationSummaryCronTask::class, 'LoanConfirmSummary');
    CronTask::unregister(NewLoanSummaryCronTask::class, 'LoanConfirmNew');
    CronTask::unregister(OverdueReminderCronTask::class, 'LoanConfirmOverdue');

    return true;
}


function plugin_itemloans_addDefaultWhere($itemtype)
{
    if ($itemtype == 'GlpiPlugin\Itemloans\Loans') {
        return getEntitiesRestrictRequest('', \GlpiPlugin\Itemloans\Loans::getTable());
    }
    return '';
}

function plugin_itemloans_item_add($item)
{
    if ($item instanceof Loans && $item->fields['ask_to_confirm']) {
        $notification = new NotificationEvent();
        $notification->raiseEvent('item_confirmation', $item, ['loan_id' => $item->getID()]);
    }
}