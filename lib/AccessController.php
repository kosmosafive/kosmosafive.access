<?php

namespace Kosmosafive\Access;

use Bitrix\Main\Access\Exception\UnknownActionException;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\AccessibleItem;

abstract class AccessController extends BaseAccessController
{
    /**
     * @throws UnknownActionException
     */
    public function checkByItemId(string $action, int $itemId = null, $params = null): bool
    {
        $item = ($params['entity'] instanceof AccessibleItem) ? $params['entity'] : $this->loadItem($itemId);
        return $this->check($action, $item, $params);
    }
}