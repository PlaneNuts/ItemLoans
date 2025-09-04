<?php

use GlpiPlugin\Itemloans\Loans;
use Search;
use Html;

include ('../../../inc/includes.php');

use Session;

Session::checkRight(Loans::$rightname, Loans::VIEW_LOANS);

Html::header(
    Loans::getTypeName(),
    $_SERVER['PHP_SELF'],
    "plugins",
    Loans::class,
    "loans"
);
Search::show(Loans::class);
Html::footer();