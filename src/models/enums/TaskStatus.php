<?php

namespace brain90\taskManager\models\enums;

use yii2mod\enum\helpers\BaseEnum;

/**
 * Статус задачи.
 */
class TaskStatus extends BaseEnum
{
    /**
     * В ожидании.
     */
    const PENDING = 1;

    /**
     * Выполняется.
     */
    const IN_PROGRESS = 2;

    /**
     * Выполнена успешно.
     */
    const SUCCESS = 3;

    /**
     * Не выполнена.
     */
    const FAIL = 4;

    /**
     * Отменена.
     */
    const CANCELED = 5;

    public static $list = [
        self::PENDING => 'pending',
        self::IN_PROGRESS => 'in progress',
        self::SUCCESS => 'success',
        self::FAIL => 'fail',
        self::CANCELED => 'canceled',
    ];
}