<?php

/**
 * Canonical action metadata for lookup controls and query routing.
 */
class ActionDefinitions
{
    const SIGN = 0x00020000;
    const ITEM = 0x00040000;
    const INVENTORY = 0x00080000;
    const SESSION_LOGIN = 0x00100000;
    const SESSION_LOGOUT = 0x00200000;
    const ITEM_ADD = 0x00400000;
    const ITEM_REMOVE = 0x00800000;
    const INVENTORY_ADD = 0x01000000;
    const INVENTORY_REMOVE = 0x02000000;

    public static function all() {
        return [
            'block_remove' => [
                'label' => '-Block',
                'bit' => 0x0001,
                'domId' => 'lookup-a-block-sub',
                'coreProtectActions' => [0],
                'source' => 'block',
            ],
            'block_place' => [
                'label' => '+Block',
                'bit' => 0x0002,
                'domId' => 'lookup-a-block-add',
                'coreProtectActions' => [1],
                'source' => 'block',
            ],
            'click' => [
                'label' => 'Click',
                'bit' => 0x0004,
                'domId' => 'lookup-a-click',
                'coreProtectActions' => [2],
                'source' => 'block',
            ],
            'kill' => [
                'label' => 'Kill',
                'bit' => 0x0008,
                'domId' => 'lookup-a-kill',
                'coreProtectActions' => [3],
                'source' => 'block',
            ],
            'container_remove' => [
                'label' => '-Container',
                'bit' => 0x0010,
                'domId' => 'lookup-a-container-sub',
                'coreProtectActions' => [4, 0],
                'source' => 'container',
            ],
            'container_add' => [
                'label' => '+Container',
                'bit' => 0x0020,
                'domId' => 'lookup-a-container-add',
                'coreProtectActions' => [4, 1],
                'source' => 'container',
            ],
            'chat' => [
                'label' => 'Chat',
                'bit' => 0x0040,
                'domId' => 'lookup-a-chat',
                'coreProtectActions' => [6],
                'source' => 'chat',
            ],
            'command' => [
                'label' => 'Command',
                'bit' => 0x0080,
                'domId' => 'lookup-a-command',
                'coreProtectActions' => [7],
                'source' => 'command',
            ],
            'session' => [
                'label' => 'Session',
                'bit' => 0x0100,
                'domId' => 'lookup-a-session',
                'coreProtectActions' => [8],
                'source' => 'session',
            ],
            'session_login' => [
                'label' => '+Session',
                'bit' => self::SESSION_LOGIN,
                'domId' => 'lookup-a-session-add',
                'coreProtectActions' => [8, 1],
                'source' => 'session',
            ],
            'session_logout' => [
                'label' => '-Session',
                'bit' => self::SESSION_LOGOUT,
                'domId' => 'lookup-a-session-sub',
                'coreProtectActions' => [8, 0],
                'source' => 'session',
            ],
            'username' => [
                'label' => 'Username',
                'bit' => 0x0200,
                'domId' => 'lookup-a-username',
                'coreProtectActions' => [9],
                'source' => 'username_log',
            ],
            'sign' => [
                'label' => 'Sign',
                'bit' => self::SIGN,
                'domId' => 'lookup-a-sign',
                'coreProtectActions' => [10],
                'source' => 'sign',
                'requiresTable' => 'sign',
            ],
            'item' => [
                'label' => 'Item',
                'bit' => self::ITEM,
                'domId' => 'lookup-a-item',
                'coreProtectActions' => [11],
                'source' => 'item',
                'requiresTable' => 'item',
            ],
            'item_add' => [
                'label' => '+Item',
                'bit' => self::ITEM_ADD,
                'domId' => 'lookup-a-item-add',
                'coreProtectActions' => [11, 1],
                'source' => 'item',
                'requiresTable' => 'item',
            ],
            'item_remove' => [
                'label' => '-Item',
                'bit' => self::ITEM_REMOVE,
                'domId' => 'lookup-a-item-sub',
                'coreProtectActions' => [11, 0],
                'source' => 'item',
                'requiresTable' => 'item',
            ],
            'inventory' => [
                'label' => 'Inventory',
                'bit' => self::INVENTORY,
                'domId' => 'lookup-a-inventory',
                'coreProtectActions' => [4, 11],
                'source' => 'inventory',
                'requiresTable' => 'item',
            ],
            'inventory_add' => [
                'label' => '+Inventory',
                'bit' => self::INVENTORY_ADD,
                'domId' => 'lookup-a-inventory-add',
                'coreProtectActions' => [4, 11, 0],
                'source' => 'inventory',
                'requiresTable' => 'item',
            ],
            'inventory_remove' => [
                'label' => '-Inventory',
                'bit' => self::INVENTORY_REMOVE,
                'domId' => 'lookup-a-inventory-sub',
                'coreProtectActions' => [4, 11, 1],
                'source' => 'inventory',
                'requiresTable' => 'item',
            ],
        ];
    }

    public static function groups() {
        return [
            'primary' => ['block_place', 'block_remove', 'container_add', 'container_remove', 'kill', 'sign'],
            'messages' => ['click', 'chat', 'command', 'session', 'session_login', 'session_logout', 'username'],
            'items' => ['item', 'item_add', 'item_remove', 'inventory'],
        ];
    }
}
