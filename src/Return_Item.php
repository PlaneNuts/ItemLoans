<?php
namespace GlpiPlugin\Itemloans;
use CommonDBTM;
use DBIterator;
use CommonGLPI;
use GlpiPlugin\Itemloans\Loans;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Return_Item extends CommonDBTM
{

    function showForm($ID, $options=[])
    {
        $this->initForm($ID, $options);
        // @itemloans is a shortcut to the **templates** directory of your plugin
        TemplateRenderer::getInstance()->display('@itemloans/return_item.form.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);

        return true;
    }

    static function getTypeName($nb=0)
    {
        return _n('Return Item', 'Return Items', $nb);
    }

    static function getMenuName($nb = 0)
    {
        // call class label
        return self::getTypeName($nb);
    }
}
