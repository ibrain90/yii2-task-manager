<?php

namespace brain90\taskManager\models\enums;

use yii2mod\enum\helpers\BaseEnum;

class TaskQueue extends BaseEnum
{
    const DEFAULT = 1;

    public static $list = [
        self::DEFAULT => 'default',
    ];
}