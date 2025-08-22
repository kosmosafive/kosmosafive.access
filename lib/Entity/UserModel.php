<?php

namespace Kosmosafive\Access\Entity;

use Bitrix\Main\Access\User\UserModel as Base;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

abstract class UserModel extends Base
{
    private ?array $permissions = null;

    abstract protected function getPermissionTableClass(): string;

    abstract protected function getRoleRelationTableClass(): string;

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getRoles(): array
    {
        if ($this->roles === null) {
            $this->roles = [];

            if (($this->userId === 0) || empty($this->getAccessCodes())) {
                return $this->roles;
            }

            $roleRelationTableClass = $this->getRoleRelationTableClass();

            $query = $roleRelationTableClass::query()
                ->addSelect('ROLE_ID')
                ->whereIn('RELATION', $this->getAccessCodes())
                ->exec();
            while ($relation = $query->fetchObject()) {
                $this->roles[] = (int)$relation->getRoleId();
            }
        }

        return $this->roles;
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getPermission(string $permissionId): ?int
    {
        $permissions = $this->getPermissions();

        return $permissions[$permissionId] ?? null;
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getPermissions(): array
    {
        if ($this->permissions === null) {
            $this->permissions = [];
            $roles = $this->getRoles();

            if (empty($roles)) {
                return $this->permissions;
            }

            $permissionTableClass = $this->getPermissionTableClass();

            $query = $permissionTableClass::query()
                ->setSelect(['PERMISSION_ID', 'VALUE'])
                ->whereIn('ROLE_ID', $roles)
                ->exec();
            while ($permission = $query->fetchObject()) {
                $permissionId = $permission->getPermissionId();
                $value = (int)$permission->getValue();

                if (!array_key_exists($permissionId, $this->permissions)) {
                    $this->permissions[$permissionId] = 0;
                }

                if ($value > $this->permissions[$permissionId]) {
                    $this->permissions[$permissionId] = $value;
                }
            }
        }

        return $this->permissions;
    }
}