<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Kosmosafive\Access\Admin\UI\Manager;

Loader::includeModule('kosmosafive.access');

$request = Context::getCurrent()->getRequest();

$moduleId = filter_var($request->getQuery('moduleId'), FILTER_SANITIZE_STRING);
if (!$moduleId || !Loader::includeModule($moduleId)) {
    include $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/admin/404.php';
    die();
}

$configClass = filter_var($request->getQuery('config'), FILTER_SANITIZE_STRING);
if (!$configClass || !class_exists($configClass)) {
    include $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/admin/404.php';
    die();
}

$manager = new Manager(new $configClass());

if ($manager->isPopup()) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_popup_admin.php');
} else {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
}

$manager->process();

if ($manager->isPopup()) {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_popup_admin.php');
} else {
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
}