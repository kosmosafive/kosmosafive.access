<?php

namespace Kosmosafive\Access\Role;

use Bitrix\Main\Access\Role\RoleUtil as Base;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserGroupTable;
use RuntimeException;

abstract class RoleUtil extends Base
{
    abstract protected static function getRoleTableClass(): string;

    abstract public static function getRoleDictionaryClass(): ?string;

    protected static function getRoleRelationTableClass(): string
    {
        new RuntimeException(Loc::getMessage('KOSMOSAFIVE_ACCESS_ROLE_UTIL_ERROR_ROLE_RELATION_TABLE_CLASS'));
    }

    protected static function getPermissionTableClass(): string
    {
        new RuntimeException(Loc::getMessage('KOSMOSAFIVE_ACCESS_ROLE_UTIL_ERROR_PERMISSION_TABLE_CLASS'));
    }

    protected static function getUserGroupTableClass(): string
    {
        return UserGroupTable::class;
    }

    public static function getMembersByPermission(string $permission, bool $includeAdmin = false): array
    {
        $permissionTable = static::getPermissionTableClass();

        $roleId = array_column(
            $permissionTable::getList(
                [
                    'select' => ['ROLE_ID'],
                    'filter' => [
                        '=PERMISSION_ID' => $permission,
                        '=VALUE' => 1
                    ]
                ]
            )->fetchAll(),
            'ROLE_ID'
        );

        if (empty($roleId)) {
            return [];
        }

        $roleRelationTable = static::getRoleRelationTableClass();

        $userId = [];
        $groupId = [];

        $query = $roleRelationTable::getList(
            [
                'select' => ['RELATION'],
                'filter' => ['=ROLE_ID' => $roleId]
            ]
        );
        while ($row = $query->fetch()) {
            $entityId = filter_var($row['RELATION'], FILTER_SANITIZE_NUMBER_INT);

            if (str_starts_with($row['RELATION'], 'U')) {
                $userId[] = $entityId;
            } elseif (str_starts_with($row['RELATION'], 'G')) {
                $groupId[] = $entityId;
            }
        }

        if ($includeAdmin) {
            $groupId[] = 1;
        }

        if (!empty($groupId)) {
            $userGroupTableClass = static::getUserGroupTableClass();

            array_push(
                $userId,
                ...array_column(
                $userGroupTableClass::getList(
                    [
                        'select' => ['USER_ID'],
                        'filter' => ['=GROUP_ID' => $groupId]
                    ]
                )->fetchAll(),
                'USER_ID'
            )
            );
        }

        return array_unique($userId);
    }
}