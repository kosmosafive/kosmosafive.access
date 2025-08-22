<?php

namespace Kosmosafive\Component;

use Bitrix\Main\GroupTable;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/main.ui.selector/ajax.php';

class MainUISelectorComponentAjaxController extends \CMainUISelectorComponentAjaxController
{
    public function getDataAction(
        array $options = [],
        array $entityTypes = [],
        array $selectedItems = []
    ): HttpResponse {
        $data = parent::getDataAction($options, $entityTypes, $selectedItems);

        $query = GroupTable::getList(
            [
                'select' => ['NAME', 'ID'],
                'order' => ['C_SORT' => 'ASC']
            ]
        );
        while ($group = $query->fetchObject()) {
            $id = 'G' . $group->getId();

            $data['ENTITIES']['GROUPS']['ITEMS'][$id] = [
                'id' => $id,
                'name' => $group->getName(),
                'searchable' => true
            ];
        }

        $data['TABS']['groups'] = [
            'id' => 'groups',
            'name' => Loc::getMessage('KOSMOSAFIVE_MAIN_UI_SELECTOR_GROUPS'),
            'sort' => 100
        ];

        return Response\AjaxJson::createSuccess($data);
    }
}
