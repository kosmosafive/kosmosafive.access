<?php

namespace Kosmosafive\Access;

use Bitrix\Main\Access\AccessibleController;
use RuntimeException;

class AccessControllerDelegate
{
    protected function checkClassName(string $className): void
    {
        $this->assertClassExists($className);
        $this->assertClassImplementsInterface($className);
    }

    protected function assertClassExists(string $className): void
    {
        if (!class_exists($className)) {
            throw new RuntimeException('Class ' . $className . ' not found');
        }
    }

    protected function assertClassImplementsInterface(string $className): void
    {
        if (!in_array(
            AccessibleController::class,
            class_implements($className),
            true
        )) {
            throw new RuntimeException('Class ' . $className . ' does not implement ' . AccessibleController::class);
        }
    }

    public function getInstance(string $className, $userId): AccessController
    {
        $this->checkClassName($className);

        return $className::getInstance($userId);
    }

    public function can(string $className, $userId, string $action, $itemId = null, $params = null): bool
    {
        $this->checkClassName($className);

        return $className::can($userId, $action, $itemId, $params);
    }
}