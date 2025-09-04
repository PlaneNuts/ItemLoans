<?php
namespace GlpiPlugin\Itemloans;
use CommonDBTM;

class My_Loans extends CommonDBTM
{
    static function getTypeName($nb=0)
    {
        return _n('My Loan', 'My Loans', $nb);
    }

    static function getMenuName($nb = 0)
    {
        return self::getTypeName($nb);
    }

    static function getMenuContent()
    {
        $menu = [];
        $menu['title'] = self::getTypeName(2);
        $menu['page']  = '/plugins/itemloans/front/my_loans.php';
        $menu['icon']  = self::getIcon();
        return $menu;
    }

    static function getIcon() {
      return "fas fa-user-tag";
    }
}
