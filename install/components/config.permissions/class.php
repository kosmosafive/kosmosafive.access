<?php

namespace Kosmosafive\Component;

use Bitrix\Location\Exception\RuntimeException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Localization\Loc;
use Kosmosafive\Access\Component\ConfigPermissionsInterface;
use ReflectionClass;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class ConfigPermissions extends \CBitrixComponent
{
    /**
     * @param $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        if (!class_exists($arParams['CONFIG'])) {
            new RuntimeException(Loc::getMessage('KOSMOSAFIVE_CONFIG_PERMISSIONS_ERROR_CONFIG'));
        }

        return $arParams;
    }

    /**
     * @return string[]
     */
    protected function listKeysSignedParameters(): array
    {
        return [
            'MODULE_ID',
            'CONFIG'
        ];
    }

    /**
     * @return void
     */
    public function executeComponent(): void
    {
        /** @var ConfigPermissionsInterface $config */
        $config = new $this->arParams['CONFIG']();

        $accessControllerClass = $config->getAccessControllerClass();
        $actionDictionaryClass = $config->getActionDictionaryClass();

        $currentUser = CurrentUser::get();
        if (!$accessControllerClass::can($currentUser->getId(), $actionDictionaryClass::ACTION_ADMIN)) {
            ShowError(Loc::getMessage('KOSMOSAFIVE_CONFIG_PERMISSIONS_ERROR_ACCESS_DENIED'));
            return;
        }

        $reflectionClass = new ReflectionClass($config);

        Loc::loadMessages($reflectionClass->getFileName());

        $this->arResult['CODE'] = (new Converter(Converter::TO_SNAKE | Converter::TO_LOWER))
            ->process($reflectionClass->getShortName());
        $this->arResult['USER_GROUPS'] = $config->getUserGroups();
        $this->arResult['ACCESS_RIGHTS'] = $config->getAccessRights();

        $this->includeComponentTemplate();
    }
}