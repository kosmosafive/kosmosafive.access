<?php

namespace Kosmosafive\Component;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Access\Exception\AccessException;
use Bitrix\Main\Access\Exception\PermissionSaveException;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\Access\Exception\RoleSaveException;
use Bitrix\Main\Error;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Request;
use Kosmosafive\Access\Component\ConfigPermissionsInterface;
use ReflectionClass;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class ConfigPermissionsAjaxController extends Controller
{
    protected ?ConfigPermissionsInterface $configPermissions;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $this->configPermissions = null;
    }

    /**
     * @return array[][]
     */
    public function configureActions(): array
    {
        return [
            'save' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(['POST']),
                    new ActionFilter\Csrf(),
                ],
            ],
            'delete' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(['POST']),
                    new ActionFilter\Csrf(),
                ],
            ],
            'load' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                ]
            ]
        ];
    }

    /**
     * @param array $userGroups
     * @return HttpResponse
     */
    public function saveAction(array $userGroups = []): HttpResponse
    {
        foreach ($userGroups as $roleSettings) {
            $this->saveRoleSettings($roleSettings);
        }

        if (!$this->errorCollection->isEmpty()) {
            return Response\AjaxJson::createError($this->errorCollection);
        }

        return Response\AjaxJson::createSuccess();
    }

    /**
     * @param int $roleId
     * @return HttpResponse
     */
    public function deleteAction(int $roleId): HttpResponse
    {
        $this->deleteRole($roleId);

        if (!$this->errorCollection->isEmpty()) {
            return Response\AjaxJson::createError($this->errorCollection);
        }

        return Response\AjaxJson::createSuccess();
    }

    public function loadAction(): HttpResponse
    {
        return Response\AjaxJson::createSuccess(
            [
                'USER_GROUPS' => $this->configPermissions->getUserGroups(),
                'ACCESS_RIGHTS' => $this->configPermissions->getAccessRights()
            ]
        );
    }

    /**
     * @return bool
     * @throws LoaderException
     */
    protected function prepareParams(): bool
    {
        $params = $this->getUnsignedParameters();

        foreach (['kosmosafive.access', $params['MODULE_ID']] as $moduleId) {
            if (!Loader::includeModule($moduleId)) {
                $this->errorCollection[] = new Error(
                    Loc::getMessage(
                        'CONFIG_PERMISSIONS_ERROR_MODULE',
                        ['#NAME#' => $params['MODULE_ID']]
                    )
                );
                return false;
            }
        }

        $this->configPermissions = new $params['CONFIG']();

        Loc::loadMessages((new ReflectionClass($this->configPermissions))->getFileName());

        return parent::prepareParams();
    }

    /**
     * @param int $roleId
     * @return void
     */
    protected function deleteRole(int $roleId): void
    {
        $roleUtilClass = $this->configPermissions->getRoleUtilClass();

        (new $roleUtilClass($roleId))->deleteRole();
    }

    protected function processBeforeAction(Action $action): bool
    {
        if (!$this->checkPermissions($action)) {
            return false;
        }

        return parent::processBeforeAction($action);
    }

    /**
     * @param Action $action
     * @return bool
     */
    private function checkPermissions(Action $action): bool
    {
        $accessControllerClass = $this->configPermissions->getAccessControllerClass();
        $actionDictionaryClass = $this->configPermissions->getActionDictionaryClass();

        $currentUser = CurrentUser::get();
        return $accessControllerClass::can($currentUser->getId(), $actionDictionaryClass::ACTION_ADMIN);
    }

    /**
     * @param array $roleSettings
     * @return void
     */
    private function saveRoleSettings(array $roleSettings): void
    {
        $roleSettings = $this->prepareSettings($roleSettings);

        $roleId = (int)$roleSettings['id'];
        $roleTitle = $roleSettings['title'];

        $roleUtilClass = $this->configPermissions->getRoleUtilClass();

        if ($roleId === 0) {
            try {
                $roleId = $roleUtilClass::createRole($roleTitle);
            } catch (RoleSaveException) {
                $this->errorCollection[] = new Error(Loc::getMessage('CONFIG_PERMISSIONS_ERROR_DB'));
            }
        }

        if (!$roleId) {
            return;
        }

        $role = new $roleUtilClass($roleId);
        try {
            $role->updateTitle($roleTitle);
        } catch (AccessException) {
            $this->errorCollection[] = new Error(Loc::getMessage('CONFIG_PERMISSIONS_ERROR_DB'));
            return;
        }

        $permissions = array_combine(
            array_column($roleSettings['accessRights'], 'id'),
            array_column($roleSettings['accessRights'], 'value')
        );
        try {
            $role->updatePermissions($permissions);
        } catch (PermissionSaveException) {
            $this->errorCollection[] = new Error(Loc::getMessage('CONFIG_PERMISSIONS_ERROR_DB'));
            return;
        }

        try {
            $role->updateRoleRelations($roleSettings['accessCodes']);
        } catch (RoleRelationSaveException) {
            $this->errorCollection[] = new Error(Loc::getMessage('CONFIG_PERMISSIONS_ERROR_DB'));
            return;
        }
    }

    /**
     * @param array $settings
     * @return array
     */
    private function prepareSettings(array $settings): array
    {
        $settings['id'] = (int)$settings['id'];
        $settings['title'] = Encoding::convertEncodingToCurrent($settings['title']);

        if (!array_key_exists('accessRights', $settings)) {
            $settings['accessRights'] = [];
        }

        if (!array_key_exists('accessCodes', $settings)) {
            $settings['accessCodes'] = [];
        }

        return $settings;
    }
}

