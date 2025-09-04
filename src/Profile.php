<?php
namespace GlpiPlugin\Itemloans;

use GlpiPlugin\Itemloans\Loans;
use CommonDBTM;
use CommonGLPI;
use Html;
use Profile as Glpi_Profile;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Profile extends CommonDBTM
{
    public static $rightname = 'profile';

    static function getTypeName($nb = 0)
    {
        return _n('Loan', 'Loans', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Glpi_Profile && $item->getField('id')) {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Glpi_Profile && $item->getField('id')) {
            self::showForProfile($item->getID());
        }
        return true;
    }

    static function getAllRights($all = false)
    {
        $rights = [
            [
                'itemtype' => Loans::class,
                'label'    => Loans::getTypeName(),
                'field'    => 'plugin_itemloans'
            ]
        ];
        return $rights;
    }

    static function showForProfile($profiles_id = 0)
    {
        $profile = new Glpi_Profile();
        $profile->getFromDB($profiles_id);

        $all_rights = self::getAllRights();
        foreach ($all_rights as &$right_group) {
            $itemtype_class = $right_group['itemtype'];
            $item = new $itemtype_class();
            $available_rights = $item->getRights();

            $current_rights_value = $profile->fields[$right_group['field']] ?? 0;

            $processed_rights = [];
            foreach ($available_rights as $right_value => $right_label) {
                $processed_rights[] = [
                    'value'   => $right_value,
                    'label'   => $right_label,
                    'checked' => (($current_rights_value & $right_value) == $right_value)
                ];
            }
            $right_group['rights'] = $processed_rights;
        }

        TemplateRenderer::getInstance()->display('@itemloans/profile.html.twig', [
            'can_edit' => Session::haveRight('profile', UPDATE),
            'profile'  => $profile,
            'rights'   => $all_rights
        ]);
    }
}
