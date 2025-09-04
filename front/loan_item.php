<?php

include ('../../../inc/includes.php');

use GlpiPlugin\Itemloans\Loans;
use GlpiPlugin\Itemloans\Loan_Item;
use Glpi\Application\View\TemplateRenderer;
use Session;
use Html;

Session::checkRight(Loans::$rightname, Loans::CREATE_LOAN);

Html::header(
    Loan_Item::getTypeName(),
    $_SERVER['PHP_SELF'],
    "plugins",
    Loans::class,
    "loan_item"
);

// Prepare variables for the template
$items = $_SESSION['loan_items'] ?? [];
$multiple_results = $_SESSION['multiple_results'] ?? [];

TemplateRenderer::getInstance()->display('@itemloans/loan_item.form.html.twig', [
    'items'             => $items,
    'multiple_results'  => $multiple_results,
    'active_loan_confirmation' => $_SESSION['active_loan_confirmation'] ?? null,
    'options'           => Loans::class,
]);

Html::footer();