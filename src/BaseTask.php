<?php

namespace brain90\taskManager;

use brain90\taskManager\models\enums\TaskCreationType;
use brain90\taskManager\models\enums\TaskQueue;

/**
 * Базовый класс задач.
 */
abstract class BaseTask
{
    /**
     * @var integer идентификатор задачи
     */
    public $taskId;

    /**
     * @var boolean нужно ли повторять задачу.
     */
    public $retry = false;

    /**
     * @var array параметры для повторного запуска задачи.
     * Не заполняется, если параметры не меняются или не нужны.
     */
    public $retryParams = [];

    /**
     * @var string идентификатор цепочки задач
     */
    public $chainId = null;

    /**
     * @var int номер попытки
     */
    public $attempt = 1;

    /**
     * @var int тип создания задачи.
     */
    public $creationType = TaskCreationType::SIMPLE;

    /**
     * Запускает создание дочерних задач.
     * Не повторные задачи, создаваемые при ошибке, выполнения.
     * А задачи которые создаются в результате выполнения данной задачи.
     * @param $taskType
     * @param array|null $params
     * @param int $queue
     * @param int|null $runAt
     */
    protected function create($taskType, $params = null, $queue = TaskQueue::DEFAULT, $runAt = null)
    {
        TaskService::addTask($taskType, $params, [
            'parentId' => $this->taskId,
            'chainId' => $this->chainId,
            'attempt' => 1,
            'runAt' => $runAt,
            'queue' => $queue,
            'creationType' => $this->creationType,
        ]);
    }

    /**
     * Метод запуска задачи.
     */
    public abstract function run();

    /**
     * Метод получения времени повторного запуска.
     * @return null|int
     */
    abstract public function getRetryRunAt();
}