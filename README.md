# Права доступа

## Введение

Концепция прав доступа описана в [документации](https://dev.1c-bitrix.ru/api_d7/bitrix/main/access/concept.php).
Модуль предоставляет инструментарий для упрощения создания интерфейсов конфигурации, расширяет заложенный функционал.

## Установка

- Установить модуль
- (опционально) При использовании страницы настроек из модульного решения необходимо убедиться, что установлены модули
  ядра "Поиск" и "Социальная сеть"

### Установка через composer

В composer.json (пример для директории local) проекта добавьте

```json
{
  "require": {
    "wikimedia/composer-merge-plugin": "dev-master"
  },
  "config": {
    "allow-plugins": {
      "wikimedia/composer-merge-plugin": true
    }
  },
  "extra": {
    "merge-plugin": {
      "require": [
        "../bitrix/composer-bx.json",
        "modules/*/composer.json"
      ],
      "recurse": true,
      "replace": true,
      "ignore-duplicates": false,
      "merge-dev": true,
      "merge-extra": false,
      "merge-extra-deep": false,
      "merge-scripts": false
    },
    "installer-paths": {
      "modules/{$name}/": [
        "type:bitrix-d7-module"
      ]
    }
  }
}
```

## Использование

На уровне модульного решения необходимо создать набор классов, описывающих права доступа.

В примере модульное решение созвучно со своей ключевой сущностью. Наследование, предлагаемое в примерах, не является
обязательным.

### Модели

Каждый модуль должен хранить роли и права доступа в своих таблицах.

*local/modules/kosmosafive.example/lib/Infrastructure/Model/ExamplePermissionTable.php*

```php
<?php

namespace Kosmosafive\Example\Infrastructure\Model;

use Bitrix\Main\Access\Permission\AccessPermissionTable;

class ExamplePermissionTable extends AccessPermissionTable
{
    public static function getTableName(): string
    {
        return 'kosmosafive_example_permission';
    }
}
```

*local/modules/kosmosafive.example/lib/Infrastructure/Model/ExampleRoleTable.php*

```php
<?php

namespace Kosmosafive\Example\Infrastructure\Model;

use Bitrix\Main\Access\Role\AccessRoleTable;

class ExampleRoleTable extends AccessRoleTable
{
    public static function getTableName(): string
    {
        return 'kosmosafive_example_role';
    }
}
```

*local/modules/kosmosafive.example/lib/Infrastructure/Model/ExampleRoleRelationTable.php*

```php
<?php

namespace Kosmosafive\Example\Infrastructure\Model;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

class ExampleRoleRelationTable extends AccessRoleRelationTable
{
    public static function getTableName(): string
    {
        return 'kosmosafive_example_role_relation';
    }
}
```

### Контроллер

Предполагается, что будет существовать хотя бы один контроллер — контроллер модуля.
В примере контроллер модуля отвечает и за ключевую сущность этого модуля.

*local/modules/kosmosafive.example/lib/Domain/Access/ExampleAccessController.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\Access\AccessibleItem;
use Kosmosafive\Example\Domain\Entity\Example;
use Kosmosafive\Example\Domain\Entity\UserModel;
use Kosmosafive\Access\AccessController;

class ExampleAccessController extends AccessController
{
    protected function loadItem(int $itemId = null): ?AccessibleItem
    {
        return ($itemId) ? Example::createFromId($itemId) : null;
    }

    protected function loadUser(int $userId): AccessibleUser
    {
        return UserModel::createFromId($userId);
    }
}
```

### Словарь действий

В словаре перечисляются все возможные действия.

*local/modules/kosmosafive.example/lib/Domain/Access/ActionDictionary.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access;

use Kosmosafive\Access\ActionDictionary as Base;

class ActionDictionary extends Base
{
    public const
        ACTION_CREATE = 'create',
        ACTION_EDIT = 'edit'
    ;
}
```

### Словарь прав доступа

*local/modules/kosmosafive.example/lib/Domain/Access/Permission/PermissionDictionary.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access\Permission;

use Kosmosafive\Access\Permission\PermissionDictionary as Base;

class PermissionDictionary extends Base
{
    public const
        EXAMPLE_CREATE = 'example_create',
        EXAMPLE_EDIT_OWN = 'example_edit_own',
        EXAMPLE_EDIT_ALL = 'example_edit_all'
    ;
}
```

### Словарь ролей

*local/modules/kosmosafive.example/lib/Domain/Access/Role/RoleDictionary.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access\Role;

use Bitrix\Main\Access\Role\RoleDictionary as Base;

class RoleDictionary extends Base
{
	public const
		EXAMPLE_ROLE_ADMIN = 'EXAMPLE_ROLE_ADMIN'
    ;
}
```

### Утилиты прав доступа

*local/modules/kosmosafive.example/lib/Domain/Access/Role/RoleUtil.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access\Role;

use Kosmosafive\Core\ORM\Model\UserGroupTable;
use Kosmosafive\Example\Infrastructure\Model;
use Kosmosafive\Access\Role\RoleUtil as Base;

class RoleUtil extends Base
{
    /**
     * @return string
     */
	protected static function getRoleTableClass(): string
	{
		return Model\ExampleRoleTable::class;
	}

    /**
     * @return string
     */
	protected static function getRoleRelationTableClass(): string
	{
		return Model\ExampleRoleRelationTable::class;
	}

    /**
     * @return string
     */
	protected static function getPermissionTableClass(): string
	{
		return Model\ExamplePermissionTable::class;
	}

    /**
     * @return string|null
     */
	public static function getRoleDictionaryClass(): ?string
	{
		return RoleDictionary::class;
	}

    /**
     * @return string
     */
    protected static function getUserGroupTableClass(): string
    {
        return UserGroupTable::class;
    }
}
```

### Правила

Каждое правило описывается в отдельном файле.

*local/modules/kosmosafive.example/lib/Domain/Access/Rule/CreateRule.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Kosmosafive\Example\Domain\Access\Permission\PermissionDictionary;

class CreateRule extends AbstractRule
{
    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin()){
            return true;
        }

        if ($this->user->getPermission(PermissionDictionary::EXAMPLE_CREATE)){
            return true;
        }

        return false;
    }
}
```

### Конфигурация для вывода интерфейса

*local/modules/kosmosafive.example/lib/Domain/Access/Component/ExampleConfigPermissions.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Access\Component;

use Kosmosafive\Example\Domain\Access\ActionDictionary;
use Kosmosafive\Example\Domain\Access\Permission\PermissionDictionary;
use Kosmosafive\Example\Domain\Access\Role\RoleUtil;
use Kosmosafive\Example\Domain\Access\ExampleAccessController;
use Kosmosafive\Access\Component\ConfigPermissions;

class ExampleConfigPermissions extends ConfigPermissions
{
    /**
     * @return array[]
     */
	protected function getSections(): array
	{
		return [
            'SECTION_ADMIN' => [
                PermissionDictionary::ADMIN
            ],
            'SECTION_EXAMPLE' => [
                PermissionDictionary::EXAMPLE_CREATE,
                PermissionDictionary::EXAMPLE_EDIT_OWN,
                PermissionDictionary::EXAMPLE_EDIT_ALL
            ]
		];
	}

    /**
     * @return string
     */
    protected function getPermissionDictionaryClass(): string
    {
        return PermissionDictionary::class;
    }

    /**
     * @return string
     */
    public function getRoleUtilClass(): string
    {
        return RoleUtil::class;
    }

    /**
     * @return string
     */
    public static function getModuleId(): string
    {
        return 'kosmosafive.example';
    }

    /**
     * @return string
     */
    public function getAccessControllerClass(): string
    {
        return ExampleAccessController::class;
    }

    /**
     * @return string
     */
    public function getActionDictionaryClass(): string
    {
        return ActionDictionary::class;
    }
}
```

### Модель пользователя

*local/modules/kosmosafive.example/lib/Domain/Entity/UserModel.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Entity;

use Kosmosafive\Example\Infrastructure\Model\ExamplePermissionTable;
use Kosmosafive\Example\Infrastructure\Model\ExampleRoleRelationTable;
use Kosmosafive\Access\Entity\UserModel as Base;

class UserModel extends Base
{
    protected function getPermissionTableClass(): string
    {
        return ExamplePermissionTable::class;
    }

    protected function getRoleRelationTableClass(): string
    {
        return ExampleRoleRelationTable::class;
    }
}
```

### Модель сущности

Модель сущности может отсутствовать.

*local/modules/kosmosafive.example/lib/Domain/Entity/Example.php*

```php
<?php

namespace Kosmosafive\Example\Domain\Entity;

use Bitrix\Main\Access\AccessibleItem;
use Kosmosafive\Example\Infrastructure\Model;

class Example extends Model\EO_Example implements AccessibleItem
{
    public static function createFromId(int $itemId): AccessibleItem
    {
        ...
    }

    public function getId(): int
    {
        return (int) parent::getId();
    }
}
```

### Страница настроек

Для добавления пункта меню, ссылающегося на страницу, можно воспользоваться файлом
local/modules/kosmosafive.example/admin/menu.php.
Заголовок и ссылка могут быть получены программно.

```php
'text' => ExampleConfigPermissions::getTitle(),
'url' => ExampleConfigPermissions::getUri()
```

## Использование

Больше примеров использования можно найти
в [документации](https://dev.1c-bitrix.ru/api_d7/bitrix/main/access/concept.php).

### Проверка возможности совершения действия

```php
use Kosmosafive\Example\Domain\Access\ActionDictionary;
use Kosmosafive\Example\Domain\Access\ExampleAccessController;

ExampleAccessController::can(
    $userId,
    ActionDictionary::ACTION_CREATE,
    $this->example->getId()
)
```

При необходимости можно явно передать сущность в контроллер.

```php
use Kosmosafive\Example\Domain\Access\ActionDictionary;
use Kosmosafive\Example\Domain\Access\ExampleAccessController;

ExampleAccessController::can(
    $userId,
    ActionDictionary::ACTION_CREATE,
    $this->example->getId(),
    ['entity' => $this->example]
)
```

### Список идентификаторов пользователей, имеющих определенное право

```php
use Kosmosafive\Example\Domain\Access\Role\RoleUtil;
use Kosmosafive\Example\Domain\Access\Permission\PermissionDictionary;

$userIdList = RoleUtil::getMembersByPermission(PermissionDictionary::EXAMPLE_CREATE)
```

## Миграция

* [Миграция с 1.x на 2.0](doc/migration/2.0.md)