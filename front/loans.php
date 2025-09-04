<?php

include ('../../../inc/includes.php');

use GlpiPlugin\Itemloans\Loans;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

Html::header(
    Loans::getTypeName(),
    $_SERVER['PHP_SELF'],
    "plugins",
    Loans::class,
    "Actions Menu"
);

/* $all_loan_actions = [
    [
        'name'  => __('Loan Items', 'itemloans'),
        'link'  => 'loan_item.php',
        'icon'  => 'fa-fw fas fa-level-down-alt',
        'right' => Loans::CREATE_LOAN,
    ],
    [
        'name'  => __('Search Loans', 'itemloans'),
        'link'  => 'search_loans.php',
        'icon'  => 'fa-fw ti ti-book',
        'right' => Loans::VIEW_LOANS,
    ],
    [
        'name'  => __('Return Items', 'itemloans'),
        'link'  => 'return_item.php',
        'icon'  => 'fa-fw fas fa-level-up-alt',
       'right' => Loans::RETURN_LOAN,
    ],
];

$loan_actions = [];
foreach ($all_loan_actions as $action) {
    if (Session::haveRight(Loans::$rightname, $action['right'])) {
        $loan_actions[] = $action;
    }
}

TemplateRenderer::getInstance()->display('@itemloans/itemloans_actions.html.twig', [
    'loan_actions' => $loan_actions,
]);
 */

// Set default sort order to loan date, descending
if (!isset($_GET['sort'])) {
    $_GET['sort'] = 405; // ID for 'date_loaned_created'
    $_GET['order'] = 'DESC';
}

Search::show(Loans::class);


Html::footer();
