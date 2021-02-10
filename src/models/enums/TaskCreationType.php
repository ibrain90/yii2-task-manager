<?php

namespace brain90\taskManager\models\enums;

use yii2mod\enum\helpers\BaseEnum;

class TaskCreationType extends BaseEnum
{
    /**
     * Задача создана в коде приложения для разового выполнения.
     */
    const SIMPLE = 1;

    /**
     * Задача переодически создается кроном.
     */
    const CRONNED = 2;
}