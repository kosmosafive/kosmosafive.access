<?php

namespace Kosmosafive\Access\Component;

interface ConfigPermissionsInterface
{
    public function getAccessRights(): array;

    public function getUserGroups(): array;

    public static function getModuleId(): string;

    public static function getTitle(): string;

    public static function getUri(): string;

    public function getAccessControllerClass(): string;

    public function getActionDictionaryClass(): string;

    public function getRoleUtilClass(): string;
}