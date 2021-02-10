<?php

namespace brain90\taskManager\models\domains;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "task".
 *
 * @property int $id
 * @property int $queue очередь задач (группа задач)
 * @property string $params параметры запуска задачи, json
 * @property int $parent_id идентификатор задачи, вызвавшей создание данной задачи
 * @property string $chain_id идентификатор цепочки задач, guid
 * @property int $attempt номер попытки
 * @property int $creation_type тип способа создания задачи
 * @property int $status статус выполнения задачи
 * @property int $type тип задачи
 * @property int $run_at время, в которое задача должна быть запущена (таймер)
 * @property int $start_at время, в которое задача была запущена
 * @property int $end_at время окончания выполнения задачи
 * @property int $created_at
 * @property int $updated_at
 * @property bool $is_deleted
 *
 * @property Task $parent
 */
class Task extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'queue', 'chain_id', 'creation_type', 'attempt'], 'required'],
            [['params', 'chain_id'], 'string'],
            [['status', 'type', 'run_at', 'parent_id', 'queue', 'start_at', 'end_at', 'creation_type'], 'integer'],
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Task::class, ['id' => 'parent_id']);
    }
}
