Task Manager
============
task manager

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ibrain90/yii2-task-manager "*"
```

or add

```
"ibrain90/yii2-task-manager": "*"
```

to the require section of your `composer.json` file.


Migration:
```
php yii migrate --migrationPath=@vendor/ibrain90/yii2-task-manager/src/migrations
```

Tasks (example):

```
<?php

namespace common\services\task\tasks;

use ibrain90\taskManager\BaseTask;

class Test extends BaseTask
{
    public function run()
    {
        //TODO:
    }

    public function getRetryRunAt()
    {
        return null;
    }
}
```

Enum (example):

```
<?php

namespace common\models\enums;

use yii2mod\enum\helpers\BaseEnum;

class TaskType extends BaseEnum
{
    const TEST = 1;
}
```

Settings:

TaskFactory

`console/config/params.php`

```
'taskClassesName' => [
    TaskType => path/to/taskClass,
    ...
],
```

Worker

`console/config/main.php`

```
'controllerMap' => [
    'worker' => [
        'class' => 'brain90\taskManager\controllers\WorkerController',
    ],
    ...
],
```

Logger

`console/config/main.php`

```
'components' => [
    'log' => [
        'flushInterval' => 1,
        'targets' => [
            [
                'class' => 'yii\log\FileTarget',
                'levels' => ['error', 'info'],
                'categories' => ['worker'],
                'exportInterval' => 1,
            ],
            ...,
        ],
    ],
],

```

use example

```
php yii worker/listen
```

Usage
-----
