<?php

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO;
use Bitrix\Main\SiteTable;
use Bitrix\Main\UserFieldTable;

Loc::loadMessages(__FILE__);

class kosmosafive_access extends \CModule
{
    public $MODULE_ID = 'kosmosafive.access';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('KOSMOSAFIVE_ACCESS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('KOSMOSAFIVE_ACCESS_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('KOSMOSAFIVE_ACCESS_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('KOSMOSAFIVE_ACCESS_PARTNER_URI');
    }

    public function GetPath($notDocumentRoot = false): string
    {
        if ($notDocumentRoot) {
            return str_ireplace(realpath(Application::getDocumentRoot()), '', dirname(__DIR__));
        }

        return dirname(__DIR__);
    }

    public function DoInstall(): void
    {
        $this->DoInstallSilent();

        global $APPLICATION;

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('KOSMOSAFIVE_ACCESS_INSTALL_TITLE'),
            $this->GetPath() . '/install/step.php'
        );
    }

    public function DoInstallSilent(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallFiles();
        $this->InstallData();
    }

    public function DoUninstall(): void
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $step = (int)$request->get('step');

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KOSMOSAFIVE_ACCESS_UNINSTALL_TITLE'),
                $this->GetPath() . '/install/unstep1.php'
            );
        } elseif ($step === 2) {
            $this->DoUninstallSilent();

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('KOSMOSAFIVE_ACCESS_UNINSTALL_TITLE'),
                $this->GetPath() . '/install/unstep2.php'
            );
        }
    }

    public function DoUninstallSilent(bool $saveData = false): void
    {
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles($arParams = []): void
    {
        $dir = new IO\Directory($this->GetPath() . '/admin/');
        if ($dir->isExists()) {
            foreach ($dir->getChildren() as $item) {
                if (
                    !$item->isFile()
                    || in_array($item->getName(), $this->getExcludedAdminFiles(), true)
                ) {
                    continue;
                }

                $file = new IO\File(
                    Application::getDocumentRoot()
                    . '/bitrix/admin/'
                    . $this->MODULE_ID
                    . '_'
                    . $item->getName()
                );

                $file->putContents(
                    '<'
                    . '?php require($_SERVER["DOCUMENT_ROOT"]."'
                    . str_replace('\\', '/', $this->GetPath(true))
                    . '/admin/'
                    . $item->getName()
                    . '");'
                );
            }
        }

        CopyDirFiles(
            $this->GetPath() . '/install/components',
            Application::getDocumentRoot() . '/local/components/kosmos',
            true,
            true
        );

        CopyDirFiles(
            $this->GetPath() . '/install/js',
            Application::getDocumentRoot() . '/local/js/kosmosaccess',
            true,
            true
        );
    }

    public function UnInstallFiles(): void
    {
        $dir = new IO\Directory($this->GetPath() . '/admin/');
        if ($dir->isExists()) {
            foreach ($dir->getChildren() as $item) {
                if (
                    !$item->isFile()
                    || in_array($item->getName(), $this->getExcludedAdminFiles(), true)
                ) {
                    continue;
                }

                IO\File::deleteFile(
                    Application::getDocumentRoot()
                    . '/bitrix/admin/'
                    . $this->MODULE_ID
                    . '_'
                    . $item->getName()
                );
            }
        }
    }

    protected function getExcludedAdminFiles(): array
    {
        return [
            'menu.php'
        ];
    }

    protected function InstallData(): void
    {
        $userFieldCode = 'UF_DEPARTMENT';

        $userField = UserFieldTable::getList(
            [
                'select' => ['FIELD_NAME'],
                'filter' => [
                    '=ENTITY_ID' => 'USER',
                    '=FIELD_NAME' => $userFieldCode
                ]
            ]
        )->fetchObject();
        if ($userField) {
            return;
        }

        $iblockId = $this->getDepartmentIblockId();
        if (!($iblockId > 0)) {
            return;
        }

        $oUserTypeEntity = new CUserTypeEntity();

        $arFields = [
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => $userFieldCode,
            'USER_TYPE_ID' => 'iblock_section',
            'MULTIPLE' => 'Y',
            'EDIT_FORM_LABEL' => [
                'ru' => 'Подразделения',
                'en' => 'Departments'
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Подразделения',
                'en' => 'Departments'
            ],
            'SETTINGS' => [
                'DISPLAY' => 'LIST',
                'LIST_HEIGHT' => '8',
                'IBLOCK_ID' => $iblockId,
                'ACTIVE_FILTER' => 'Y'
            ]
        ];

        $oUserTypeEntity->Add($arFields);
    }

    protected function getDepartmentIblockId(): ?int
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $iblockCode = 'departments';

        $iblock = IblockTable::getList(
            [
                'select' => ['ID'],
                'filter' => ['=CODE' => $iblockCode]
            ]
        )->fetchObject();
        if ($iblock) {
            return $iblock->getId();
        }

        $iblockTypeId = 'structure';

        $iblockType = CIBlockType::GetList(['ID' => 'ASC'], ['=ID' => $iblockTypeId])
            ->GetNext(true, false);
        if ($iblockType) {
            return null;
        }

        $iblockType = new CIBlockType();
        $iblockTypeId = $iblockType->Add(
            [
                'ID' => $iblockTypeId,
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'SORT' => 100,
                'LANG' => [
                    'en' => [
                        'NAME' => 'Catalog',
                        'SECTION_NAME' => 'Sections',
                        'ELEMENT_NAME' => 'Elements'
                    ],
                    'ru' => [
                        'NAME' => 'Оргструктура',
                        'SECTION_NAME' => 'Разделы',
                        'ELEMENT_NAME' => 'Элементы'
                    ]
                ]
            ]
        );

        if (!$iblockTypeId) {
            return null;
        }

        $siteIdList = array_column(
            SiteTable::getList(
                [
                    'select' => ['LID']
                ]
            )->fetchAll(),
            'LID'
        );

        $iblock = new CIBlock();

        $departmentsIblockId = $iblock->Add(
            [
                'ACTIVE' => 'Y',
                'NAME' => 'Подразделения',
                'CODE' => $iblockCode,
                'API_CODE' => $iblockCode,
                'IBLOCK_TYPE_ID' => $iblockTypeId,
                'SITE_ID' => $siteIdList,
                'SORT' => 100,
                'GROUP_ID' => [2 => 'R'],
                'LIST_PAGE_URL' => '',
                'SECTION_PAGE_URL' => '',
                'DETAIL_PAGE_URL' => '',
                'INDEX_SECTION' => 'N',
                'INDEX_ELEMENT' => 'N',
                'SECTION_PROPERTY' => 'Y',
                'WORKFLOW' => 'N',
                'BIZPROC' => 'N'
            ]
        );

        return $departmentsIblockId;
    }
}