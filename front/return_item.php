<?php

include ('../../../inc/includes.php');

use GlpiPlugin\Itemloans\Loans;
use Session;

Session::checkRight(Loans::$rightname, Loans::RETURN_LOAN);

// TODO: Add logic to return items.
use GlpiPlugin\Itemloans\Return_Item;
use Glpi\Application\View\TemplateRenderer;
use Html;

Html::header(
    Return_Item::getTypeName(),
    $_SERVER['PHP_SELF'],
    "plugins",
    Loans::class,
    "return_item"
);

$items=[];
if (isset ($_SESSION['return_items'])){
    $items = $_SESSION['return_items'];
};


TemplateRenderer::getInstance()->display('@itemloans/return_item.form.html.twig', [
    'items' => $items,
]);

Html::footer();
