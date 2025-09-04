<?php
include ('../../../inc/includes.php');

use GlpiPlugin\Itemloans\Loans;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

// Authenticated users only
Session::checkLoginUser();

$loan = new Loans();

$user_id = Session::getLoginUserID();

$unconfirmed_loans = [];
$confirmed_loans = [];

// Get all active loans for the current user
$all_loans = $loan->find([
    'loan_user_id' => $user_id,
    'loan_returned' => 0
]);

foreach ($all_loans as $loan_data) {
    if ($loan_data['ask_to_confirm'] && !$loan_data['confirmed_by_user']) {
        $unconfirmed_loans[] = $loan_data;
    } else {
        $confirmed_loans[] = $loan_data;
    }
}

Html::header(
    __('My Loans', 'itemloans'),
    $_SERVER['PHP_SELF'],
    "plugins",
    Loans::class,
    "my_loans"
);

TemplateRenderer::getInstance()->display('@itemloans/my_loans.html.twig', [
    'unconfirmed_loans' => $unconfirmed_loans,
    'confirmed_loans'   => $confirmed_loans,
]);

Html::footer();
