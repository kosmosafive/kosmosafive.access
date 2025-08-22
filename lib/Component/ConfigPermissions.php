<?php

namespace Kosmosafive\Access\Component;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\UI\AccessRights\DataProvider;
use Bitrix\Main\UI\AccessRights\Exception\UnknownEntityTypeException;
use Bitrix\Main\Web\Uri;
use ReflectionClass;

abstract class ConfigPermissions implements ConfigPermissionsInterface
{
    protected const LIMIT_PREVIEW = 0;

    abstract protected function getSections(): array;

    abstract protected function getPermissionDictionaryClass(): string;

    abstract public function getRoleUtilClass(): string;

    public function getAccessRights(): array
    {
        $sections = $this->getSections();

        $reflectionClass = new ReflectionClass(static::class);
        $prefix = (new Converter(Converter::TO_SNAKE_DIGIT | Converter::TO_UPPER))
            ->process($reflectionClass->getShortName());

        $res = [];

        $permissionDictionaryClass = $this->getPermissionDictionaryClass();

        foreach ($sections as $sectionName => $permissions) {
            $rights = [];

            foreach ($permissions as $permissionId) {
                $rights[] = $permissionDictionaryClass::getPermission($permissionId);
            }

            $res[] = [
                'sectionTitle' => Loc::getMessage($prefix . '_' . $sectionName) ?: $sectionName,
                'rights' => $rights
            ];
        }

        return $res;
    }

    /**
     * @throws UnknownEntityTypeException
     */
    public function getUserGroups(): array
    {
        $roleUtilClass = $this->getRoleUtilClass();
        $roleDictionaryClass = $roleUtilClass::getRoleDictionaryClass();

        $list = $roleUtilClass::getRoles();

        $roles = [];
        foreach ($list as $row) {
            $roleId = (int)$row['ID'];

            $roles[] = [
                'id' => $roleId,
                'title' => ($roleDictionaryClass) ? $roleDictionaryClass::getRoleName($row['NAME']) : $row['NAME'],
                'accessRights' => $this->getRoleAccessRights($roleId),
                'members' => $this->getRoleMembers($roleId)
            ];
        }

        return $roles;
    }

    /**
     * @throws UnknownEntityTypeException
     */
    private function getRoleMembers(int $roleId): array
    {
        $roleUtilClass = $this->getRoleUtilClass();

        $members = [];
        $relations = (new $roleUtilClass($roleId))->getMembers($this->getLimitPreview());
        foreach ($relations as $row) {
            $accessCode = $row['RELATION'];
            $members[$accessCode] = $this->getMemberInfo($accessCode);
        }

        return $members;
    }

    /**
     * @throws UnknownEntityTypeException
     */
    private function getMemberInfo(string $code): array
    {
        $accessCode = new AccessCode($code);
        $member = (new DataProvider())->getEntity($accessCode->getEntityType(), $accessCode->getEntityId());
        return $member->getMetaData();
    }

    private function getRoleAccessRights(int $roleId): array
    {
        $roleUtilClass = $this->getRoleUtilClass();

        $permissions = (new $roleUtilClass($roleId))->getPermissions();

        $accessRights = [];
        foreach ($permissions as $permissionId => $value) {
            $accessRights[] = [
                'id' => $permissionId,
                'value' => $value
            ];
        }

        return $accessRights;
    }

    protected function getLimitPreview(): int
    {
        return static::LIMIT_PREVIEW;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('KOSMOSAFIVE_ACCESS_CONFIG_PERMISSION_TITLE');
    }

    public static function getUri(): string
    {
        $uri = new Uri(BX_ROOT . '/admin/kosmos.access_router.php');
        $uri->addParams(
            [
                'moduleId' => static::getModuleId(),
                'config' => static::class
            ]
        );

        return $uri->getUri();
    }
}