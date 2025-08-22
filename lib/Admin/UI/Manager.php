<?php

namespace Kosmosafive\Access\Admin\UI;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Kosmosafive\Access\Component\ConfigPermissionsInterface;

class Manager
{
    protected Request $request;
    protected bool $popup;

    public function __construct(
        protected ConfigPermissionsInterface $config
    ) {
        $this->request = Context::getCurrent()?->getRequest();
        $this->popup = $this->request->getQuery('popup') === 'Y';
    }

    public function isPopup(): bool
    {
        return $this->popup;
    }

    public function process(): void
    {
        global $APPLICATION;

        $APPLICATION->SetTitle($this->config::getTitle());

        $APPLICATION->IncludeComponent(
            'kosmos:config.permissions',
            '',
            [
                'CONFIG' => $this->config::class,
                'MODULE_ID' => $this->config::getModuleId()
            ],
            null,
            ['HIDE_ICONS' => true]
        );
    }
}