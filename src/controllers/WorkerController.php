<?php

namespace brain90\taskManager\controllers;

use brain90\taskManager\models\domains\Task;
use brain90\taskManager\models\enums\TaskQueue;
use brain90\taskManager\models\enums\TaskStatus;
use brain90\taskManager\TaskFactory;
use brain90\taskManager\TaskService;
use Exception;
use Yii;
use yii\console\Controller;
use yii\db\Exception as DBException;
use yii\db\Transaction;

class WorkerController extends Controller
{
    public static $messageCategory = 'worker';

    /**
     * @var int|null идентификатор обрабатываемой очереди.
     */
    protected $queue = null;

    /**
     * @var int время ожидания потока между проверками задач.
     */
    protected $sleep = 2;

    /**
     * Запускает прослушивание задач на выполнение.
     * @param int|null $queue
     * @throws DBException
     */
    public function actionListen($queue = null)
    {
        if ($queue === null || TaskQueue::isValidValue($queue)) {
            $this->queue = $queue;
        }

        pcntl_signal(SIGTERM, 'self::handleSignal');
        pcntl_signal(SIGINT, 'self::handleSignal');

        while (true) {
            pcntl_signal_dispatch();

            if (!$this->tryProcess()) {
                sleep($this->sleep);
            }
        }
    }

    /**
     * Обработчик сигналов остановки процесса.
     * @param $signo
     */
    private static function handleSignal($signo)
    {
        Yii::info('Получен сигнал остановки. Воркер завершает работу.', self::$messageCategory);
        die;
    }

    /**
     * Запускает задачу.
     * @return bool
     * @throws DBException
     */
    protected function tryProcess()
    {
        /** @var Transaction $transaction */
        $transaction = Yii::$app->db->beginTransaction();
        try {
            /** @var Task $taskModel */
            $taskModel = TaskService::getTaskToRun($this->queue);

            if (empty($taskModel)) {
                return false;
            }

            $taskModel->start_at = time();

            // Для выполнения задачи используется вложенная транзакция (В Postgres генерируются точки сохранения)
            /** @var Transaction $transactionNested */
            $transactionNested = Yii::$app->db->beginTransaction();
            try {
                $task = TaskFactory::getTaskByModel($taskModel);

                $task->run();

                $transactionNested->commit();
                // Ошибка в базе данных. При этом транзакцию невозможно успешно завершить.
            } catch (DBException $ex) {
                // Все записи в базе данных, созданные в ходе выполнения ($task->run()) этой задачи будут отменены.
                $transactionNested->rollBack();
                throw $ex;
            } catch (Exception $ex) {
                $transactionNested->commit();
                throw $ex;
            }

            $taskModel->status = TaskStatus::SUCCESS;
        } catch (Exception $ex) {
            $taskModel->status = TaskStatus::FAIL;
            Yii::error($ex, self::$messageCategory);
        } finally {
            if ($taskModel !== null) {
                $taskModel->end_at = time();
                $taskModel->save();
                // создает повторную задачу
                if (isset($task) && $task->retry) {
                    TaskService::addRetryTask($taskModel, $task->getRetryRunAt(), $task->retryParams);
                }
            }
            $transaction->commit();
        }
        return true;
    }
}