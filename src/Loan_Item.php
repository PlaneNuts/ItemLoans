<?php
namespace GlpiPlugin\Itemloans;
use CommonDBTM;
use Search;
use DBIterator;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Loan_Item extends CommonDBTM
{
    static function getTypeName($nb=0)
    {
        return _n('Loan Item', 'Loan Items', $nb);
    }

    static function getMenuName($nb = 0)
    {
        // call class label
        return self::getTypeName($nb);
    }

    function showForm($ID, $options=[])
    {
        $this->initForm($ID, $options);
        // @itemloans is a shortcut to the **templates** directory of your plugin
        TemplateRenderer::getInstance()->display('@itemloans/loan_item.form.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);

        return true;
    }   
}
