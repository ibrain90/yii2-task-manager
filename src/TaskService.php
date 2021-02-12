<?php

namespace brain90\taskManager;


use brain90\taskManager\models\domains\Task;
use brain90\taskManager\models\enums\TaskCreationType;
use brain90\taskManager\models\enums\TaskQueue;
use brain90\taskManager\models\enums\TaskStatus;
use Yii;
use yii\helpers\Json;

/**
 * Сервис задач.
 */
class TaskService
{
    private static $messageCategory = 'task-service';

    /**
     * Получает задачу на запуск.
     * Полученную строку блокирует для записи (FOR UPDATE).
     * При этом пропускает уже заблокированные строки (SKIP LOCKED).
     * @param int|null $queue очередь
     * @return null|\yii\db\ActiveRecord
     */
    public static function getTaskToRun($queue = null)
    {
        // при сортировке по возрастанию значения null выдаются последними
        $sql = 'SELECT * FROM task WHERE is_deleted = false AND status = :status AND (run_at <= :time OR run_at IS NULL)';
        $params = [
            ':status' => TaskStatus::PENDING,
            ':time' => time(),
        ];

        if ($queue !== null) {
            $sql .= ' AND queue = :queue';
            $params[':queue'] = $queue;
        }

        $sql .= ' ORDER BY run_at, created_at, id LIMIT 1 FOR UPDATE SKIP LOCKED';

        return Task::findBySql($sql, $params)->one();
    }

    /**
     * Добавляет задачу.
     * @param integer $taskType TaskType value.
     * @param array|null $params
     * @param $taskParams
     * @return Task|null
     */
    public static function addTask($taskType, $params = null, $taskParams = [])
    {
        $taskModel = new Task();
        $taskModel->type = $taskType;
        $taskModel->params = Json::encode($params);
        if (isset($taskParams['parentId']) && $taskParams['parentId'] !== null) {
            $taskModel->parent_id = $taskParams['parentId'];
        }
        if (isset($taskParams['chainId']) && $taskParams['chainId'] !== null) {
            $taskModel->chain_id = $taskParams['chainId'];
        } else {
            $taskModel->chain_id = self::generateChainId();
        }
        if (isset($taskParams['attempt']) && $taskParams['attempt'] !== null) {
            $taskModel->attempt = $taskParams['attempt'];
        } else {
            $taskModel->attempt = 1;
        }
        if (isset($taskParams['runAt']) && $taskParams['runAt'] !== null) {
            $taskModel->run_at = $taskParams['runAt'];
        }
        if (isset($taskParams['queue']) && $taskParams['queue'] !== null) {
            $taskModel->queue = $taskParams['queue'];
        } else {
            $taskModel->queue = TaskQueue::DEFAULT;
        }
        if (isset($taskParams['creationType']) && $taskParams['creationType'] !== null) {
            $taskModel->creation_type = $taskParams['creationType'];
        } else {
            $taskModel->creation_type = TaskCreationType::SIMPLE;
        }
        if (!$taskModel->save()) {
            Yii::error($taskModel->errors, self::$messageCategory);
            return null;
        }
        return $taskModel;
    }

    /**
     * Генерирует guid в качестве chainId.
     * @return string
     */
    public static function generateChainId()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        } else {
            return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }
    }

    /**
     * Создает повторную задачу. Если задачи создаются кроном, предварительно проверяет не была ли уже создана еще одна
     * такая же задача.
     * @param Task $taskModel
     * @param int|null $runAt
     * @param array $params параметры, с которыми должна перезапускаться задача, если не заданы, то используются
     * прежние параметры
     */
    public static function addRetryTask($taskModel, $runAt, $params)
    {
        if ($taskModel->creation_type !== TaskCreationType::CRONNED || !self::checkNewCronnedTask($taskModel)) {
            $taskModelRetry = new Task();
            $taskModelRetry->type = $taskModel->type;
            $taskModelRetry->queue = $taskModel->queue;
            if (!empty($params)) {
                $taskModelRetry->params = $params;
            } else {
                $taskModelRetry->params = $taskModel->params;
            }
            $taskModelRetry->parent_id = $taskModel->id;
            $taskModelRetry->chain_id = $taskModel->chain_id;
            $taskModelRetry->attempt = ++$taskModel->attempt;
            $taskModelRetry->creation_type = $taskModel->creation_type;
            $taskModelRetry->status = TaskStatus::PENDING;
            if ($runAt !== null) {
                $taskModelRetry->run_at = $runAt;
            }
            $taskModelRetry->save();
        }
    }

    /**
     * Проверяет нет ли новой задачи такого же типа, созданной кроном.
     * @param Task $taskModel
     * @return bool
     */
    public static function checkNewCronnedTask($taskModel)
    {
        $subQuery = Task::find()->select('created_at')
            ->where([
                'parent_id' => null,
                'chain_id' => $taskModel->chain_id,
                'is_deleted' => false,
            ])->limit(1);

        return Task::find()->where([
            'parent_id' => null,
            'type' => $taskModel->type,
            'is_deleted' => false,
        ])
            ->andWhere([
                '>', 'created_at', $subQuery,
            ])
            ->exists();
    }
}