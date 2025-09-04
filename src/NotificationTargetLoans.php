<?php
namespace GlpiPlugin\Itemloans;

use NotificationTarget;
use User;
use Notification;
use GlpiPlugin\Itemloans\Loans;

class NotificationTargetLoans extends NotificationTarget
{
    const LOAN_USER_RECIPIENT = 50; // Custom recipient ID for the user who loaned the item

    public function getEvents()
    {
        return [
            'overdue_loan'      => __('Overdue loan', 'itemloans'),
            'item_confirmation_summary' => __('Confirmation of loan (Summary)', 'itemloans'),
            'new_loan_summary' => __('New loan summary', 'itemloans'),
        ];
    }

    public function addAdditionalTargets($event = '')
    {
        parent::addAdditionalTargets($event);
        switch ($event) {
            case 'overdue_loan':
            case 'item_confirmation_summary':
            case 'new_loan_summary':
                $this->addTarget(self::LOAN_USER_RECIPIENT, __('User who loaned the item', 'itemloans'), Notification::USER_TYPE);
                break;
        }
    }

    public function addSpecificTargets($data, $options)
    {
        parent::addSpecificTargets($data, $options);

        if ($this->obj instanceof Loans) {
            if ($data['type'] == Notification::USER_TYPE && $data['items_id'] == self::LOAN_USER_RECIPIENT) {
                $this->addUserByField('loan_user_id');
            }
        }
    }

    public function addDataForTemplate($event, $options = [])
    {
        global $CFG_GLPI;

        switch ($event) {
            case 'item_confirmation':
            case 'overdue_loan':
                if (isset($options['loan_id'])) {
                    $loan = new Loans();
                    if ($loan->getFromDB($options['loan_id'])) {
                        $this->data['##loan.id##'] = $loan->fields['id'];
                        $this->data['##loan.item_name##'] = $loan->fields['glpi_name'];
                        $this->data['##loan.item_inventory_number##'] = $loan->fields['glpi_otherserial'];
                        $this->data['##loan.return_by_date##'] = $loan->fields['return_by_date'];

                        $user = new User();
                        if ($user->getFromDB($loan->fields['loan_user_id'])) {
                            $this->data['##loan.user_name##'] = $user->getName();
                        }

                        $this->data['##loan.confirmation_link##'] = $CFG_GLPI['url_base'] . '/plugins/itemloans/front/my_loans.php';
                    }
                }
                break;
            case 'item_confirmation_summary':
                if ($this->obj instanceof Loans) {
                    $user = new User();
                    if ($user->getFromDB($this->obj->fields['loan_user_id'])) {
                        $this->data['##loan.user_name##'] = $user->getName();
                    }
                }

                if (isset($options['loan_ids']) && is_array($options['loan_ids'])) {
                    $table = '<table border="1" style="width:100%">';
                    $table .= '<tr><th>' . __('Item Name') . '</th><th>' . __('Inventory Number') . '</th><th>' . __('Return by Date') . '</th></tr>';

                    foreach ($options['loan_ids'] as $loan_id) {
                        $loan = new Loans();
                        if ($loan->getFromDB($loan_id)) {
                            $table .= '<tr>';
                            $table .= '<td>' . $loan->fields['glpi_name'] . '</td>';
                            $table .= '<td>' . $loan->fields['glpi_otherserial'] . '</td>';
                            $table .= '<td>' . $loan->fields['return_by_date'] . '</td>';
                            $table .= '</tr>';
                        }
                    }
                    $table .= '</table>';

                    $this->data['##loans.confirmation_table##'] = $table;
                    $this->data['##loan.confirmation_link##'] = $CFG_GLPI['url_base'] . '/plugins/itemloans/front/my_loans.php';
                }
            case 'new_loan_summary':
                if ($this->obj instanceof Loans) {
                    $user = new User();
                    if ($user->getFromDB($this->obj->fields['loan_user_id'])) {
                        $this->data['##loan.user_name##'] = $user->getName();
                    }
                }

                if (isset($options['loan_ids']) && is_array($options['loan_ids'])) {
                    $table = '<table border="1" style="width:100%">';
                    $table .= '<tr><th>' . __('Item Name') . '</th><th>' . __('Inventory Number') . '</th><th>' . __('Return by Date') . '</th></tr>';

                    foreach ($options['loan_ids'] as $loan_id) {
                        $loan = new Loans();
                        if ($loan->getFromDB($loan_id)) {
                            $table .= '<tr>';
                            $table .= '<td>' . $loan->fields['glpi_name'] . '</td>';
                            $table .= '<td>' . $loan->fields['glpi_otherserial'] . '</td>';
                            $table .= '<td>' . $loan->fields['return_by_date'] . '</td>';
                            $table .= '</tr>';
                        }
                    }
                    $table .= '</table>';

                    $this->data['##loans.tableOfDevices##'] = $table;
                    $this->data['##loan.confirmation_link##'] = $CFG_GLPI['url_base'] . '/plugins/itemloans/front/my_loans.php';
                }
                break;
        }
    }
}