<?php

namespace brain90\taskManager;

use brain90\taskManager\models\domains\Task;
use Exception;
use Yii;
use yii\helpers\Json;

/**
 * Фабрика задач.
 */
class TaskFactory
{
    /**
     * Получает экземпляр задачи для выполнения.
     * @param Task $taskModel
     * @return BaseTask
     * @throws Exception
     */
    public static function getTaskByModel(Task $taskModel)
    {
        if (!isset(Yii::$app->params['taskClassesName'])) {
            throw new Exception('Необходимо прописать в настройки названия классов для типов задач.');
        }
        $className = Yii::$app->params['taskClassesName'][$taskModel->type];
        if (!class_exists($className)) {
            throw new Exception('Task class does not exists.');
        }

        if (!is_subclass_of($className, '\makxxxs\taskManager\BaseTask')) {
            throw new Exception('Task class does not subclass of \makxxxs\taskManager\BaseTask.');
        }

        $params = Json::decode($taskModel->params);
        if (empty($params)) {
            $params = [];
        }
        $params['class'] = $className;
        $params['taskId'] = $taskModel->id;
        $params['attempt'] = $taskModel->attempt;
        $params['chainId'] = $taskModel->chain_id;

        return Yii::createObject($params);
    }
}