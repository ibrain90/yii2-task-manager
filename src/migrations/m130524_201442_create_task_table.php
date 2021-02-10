<?php

use brain90\taskManager\models\enums\TaskStatus;
use yii\db\Migration;

class m130524_201442_create_task_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('task', [
            'id' => $this->bigPrimaryKey()->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'queue' => $this->smallInteger()->notNull(),
            'params' => $this->text(),
            'parent_id' => $this->bigInteger(),
            'chain_id' => $this->string()->notNull(),
            'attempt' => $this->smallInteger()->notNull(),
            'creation_type' => $this->smallInteger()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(TaskStatus::PENDING),
            'run_at' => $this->bigInteger(),
            'start_at' => $this->bigInteger(),
            'end_at' => $this->bigInteger(),

            'created_at' => $this->bigInteger()->notNull(),
            'updated_at' => $this->bigInteger()->notNull(),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->addForeignKey('fk__task__parent_id__to__task__id',
            'task', 'parent_id',
            'task', 'id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk__task__parent_id__to__task__id', 'task');
        $this->dropTable('task');
    }
}
