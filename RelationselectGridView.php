<?php

namespace snewer\relationselect;

use yii\grid\GridView;
use yii\grid\Column;
use yii\helpers\Html;

class RelationselectGridView extends GridView
{

    public $selectedModelsIds = [];

    public $multiple;

    protected function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        $this->columns[] = [
            'class' => Column::className(),
            'content' => function ($model, $key, $index) {
                $active = in_array($model->primaryKey, $this->selectedModelsIds);
                $result = '';
                $result .= Html::tag('button', '<span class="glyphicon glyphicon-remove"></span> ' . ($this->multiple ? 'Отвязать' : 'Отменить'), [
                    'data-id' => $model->primaryKey,
                    'data-action' => 'unlink',
                    'style' => 'width: 100%; text-align: left;' . ($active ? '' : ' display:none;'),
                    'type' => 'button',
                    'class' => 'btn btn-danger'
                ]);

                $result .= Html::tag('button', '<span class="glyphicon glyphicon-plus"></span> ' . ($this->multiple ? 'Привязать' : 'Выбрать'), [
                    'data-id' => $model->primaryKey,
                    'data-action' => 'link',
                    'style' => 'width: 100%; text-align: left;' . ($active ? ' display:none;' : ''),
                    'type' => 'button',
                    'class' => 'btn btn-primary'
                ]);
                return $result;
            },
            'contentOptions' => [
                'style' => 'width: 1px;'
            ]
        ];
        parent::initColumns();
    }

}