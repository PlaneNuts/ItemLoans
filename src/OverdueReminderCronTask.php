<?php
namespace GlpiPlugin\Itemloans;

use CronTask as GlpiCronTask;
use GlpiPlugin\Itemloans\Loans;
use NotificationEvent;

class OverdueReminderCronTask extends GlpiCronTask
{
    public static function getTypeName($nb = 0)
    {
        return __('Item Loan', 'itemloans');
    }

    static function cronInfo($name) {

      switch ($name) {
         case 'LoanConfirmOverdue':
            return [
               'description' => __('Overdue Loans Reminder', 'itemloans')];   // Optional
            break;
      }
      return [];
   }

    public static function cronLoanConfirmOverdue($task)
    {
        global $DB;
        $cron_status = 1;

        $query = "SELECT `id` FROM " . Loans::getTable() . " WHERE `loan_returned` = 0 AND `return_by_date` < NOW() AND `send_reminder` = 1";
        $result = $DB->query($query);
        $num_overdue = $DB->numrows($result);

        if ($num_overdue > 0) {
            $template_name = 'Item Loan Overdue';
            $notification_template = new \NotificationTemplate();
            if (!$notification_template->getFromDBByCrit(['name' => $template_name])) {
                $task->log("Could not find notification template: $template_name");
                return GlpiCronTask::STATUS_ERROR;
            }
            $template_id = $notification_template->getID();

            while ($data = $DB->fetchAssoc($result)) {
                $loan = new Loans();
                if ($loan->getFromDB($data['id'])) {
                    $query_check = "SELECT COUNT(*) as count FROM `glpi_queuednotifications`
                                    WHERE `itemtype` = '" . addslashes(Loans::class) . "'
                                      AND `items_id` = " . $loan->getID() . "
                                      AND `notificationtemplates_id` = " . $template_id;
                    $res_check = $DB->query($query_check);
                    if ($DB->fetchAssoc($res_check)['count'] == 0) {
                        NotificationEvent::raiseEvent('overdue_loan', $loan, ['loan_id' => $loan->getID()]);
                    }
                }
            }
        }

        $task->setVolume($num_overdue);

        return $cron_status;
    }
}
