<?php
namespace GlpiPlugin\Itemloans;

use CommonDBTM;
use Session;
use GlpiPlugin\Itemloans\Loans;
use Glpi\Application\View\TemplateRenderer;

class Search_Loans extends Loans
{
    /**
     *  Name of the itemtype
     */
    static function getTypeName($nb=0)
    {
        return _n('Loan', 'Loans', $nb);
    }

     static function getMenuName($nb = 0)
    {
        // call class label
        return self::getTypeName($nb);
    }
}
