<?php
namespace GlpiPlugin\Itemloans;

use CronTask as GlpiCronTask;
use GlpiPlugin\Itemloans\Loans;
use NotificationEvent;
use User;

class NewLoanSummaryCronTask extends GlpiCronTask
{

    public static function getTypeName($nb = 0)
    {
        return __('Item Loan', 'itemloans');
    }

    static function cronInfo($name)
    {
        switch ($name) {
            case 'LoanConfirmNew':
                return [
                    'description' => __('Send a summary of new items pending confirmation to users.', 'itemloans')
                ];
        }
        return [];
    }

    public static function cronLoanConfirmNew($task)
    {
        global $DB;
        $cron_status = 1;
        $loan_table = Loans::getTable();

        // Get all users with new items pending confirmation
        $query = "SELECT DISTINCT `loan_user_id`
                  FROM `$loan_table`
                  WHERE `loan_returned` = 0
                    AND `ask_to_confirm` = 1
                    AND `confirmation_sent` = 0";
        $result = $DB->query($query);
        $volume = 0;

        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetchAssoc($result)) {
                $user_id = $data['loan_user_id'];

                // Get all new pending loans for this user
                $loans = new Loans();
                $pending_loans = $loans->find([
                    'loan_user_id'      => $user_id,
                    'loan_returned'     => 0,
                    'ask_to_confirm'    => 1,
                    'confirmation_sent' => 0,
                ]);

                if (count($pending_loans) > 0) {
                    $loan_ids = array_keys($pending_loans);
                    $last_loan_id = end($loan_ids);
                    $last_loan = new Loans();
                    if ($last_loan->getFromDB($last_loan_id)) {
                        NotificationEvent::raiseEvent('new_loan_summary', $last_loan, ['loan_ids' => $loan_ids]);

                        // Mark these loans as having had the confirmation sent
                        $query_update = "UPDATE `$loan_table`
                                         SET `confirmation_sent` = 1
                                         WHERE `id` IN (" . implode(',', $loan_ids) . ")";
                        $DB->query($query_update);
                        $volume += count($loan_ids);
                    }
                }
            }
        }

        $task->setVolume($volume);

        return $cron_status;
    }
}
