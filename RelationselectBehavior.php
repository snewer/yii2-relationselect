<?php

namespace snewer\relationselect;

use Closure;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * @property ActiveRecord $owner
 */
class RelationselectBehavior extends Behavior
{

    /**
     * @var string
     */
    public $relationName;

    /**
     * @var string
     */
    public $attributeName;

    /**
     * @var Closure
     */
    public $queryModifier;

    /**
     * @var bool
     */
    public $processLinking = true;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave'
        ];
    }

    private function linkModels($models)
    {
       // if (!$this->processLinking) return;
        foreach ($models as $model) {
            $this->owner->link($this->relationName, $model);
        }
    }

    private function unlinkModels($models, $delete = true)
    {
      //  if (!$this->processLinking) return;
        foreach ($models as $model) {
            $this->owner->unlink($this->relationName, $model, $delete);
        }
    }

    public function getNewRelatedModels()
    {
        $attributeValue = $this->owner->{$this->attributeName};
        if ($attributeValue) {
            $modelClass = $this->getRelation()->modelClass;
            /* @var \yii\db\ActiveQuery $models */
            $models = call_user_func([$modelClass, 'find']);
            return $models->where(['id' => $attributeValue])->all();
        } else {
            return [];
        }
    }

    public function getOldRelatedModels()
    {
        $models = $this->owner->{$this->relationName};
        return is_array($models) ? $models : [$models];
    }

    private function getOldRelatedModelsIds($models)
    {
        $ids = [];
        foreach ($models as $model) {
            $ids[] = $model->primaryKey;
        }
        return $ids;
    }

    private $_relationObject;

    /**
     * Возвращает \yii\db\ActiveQuery объект связи.
     * @return \yii\db\ActiveQuery
     */
    public function getRelation()
    {
        if (!isset($this->_relationObject)) {
            $this->_relationObject = $this->owner->{'get' . ucfirst($this->relationName)}();
        }
        return $this->_relationObject;
    }

    public function beforeSave($event)
    {
        $oldModels = $this->getOldRelatedModels();
        $newModels = $this->getNewRelatedModels();
        $this->linkModels($this->modelsDiff($newModels, $oldModels));
        $this->unlinkModels($this->modelsDiff($oldModels, $newModels));
    }

    private function modelsDiff($aModels, $bModels)
    {
        return array_udiff($aModels, $bModels, function (ActiveRecord $a, ActiveRecord $b) {
            return strnatcmp($a->primaryKey, $b->primaryKey);
        });
    }

    public function getRelationModelsDataProvider()
    {
        $modelClass = $this->getRelation()->modelClass;
        /* @var \yii\db\ActiveQuery $query */
        $query = call_user_func([$modelClass, 'find']);
        $modelsIds = $this->getOldRelatedModelsIds($this->getOldRelatedModels());
        if ($modelsIds) {
            $query->addOrderBy(new Expression('[[id]] IN (' . implode(', ', $modelsIds) . ') DESC'));
        }
        if ($this->queryModifier instanceof Closure) {
            call_user_func($this->queryModifier, $query);
        }
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 5
            ]
        ]);
    }

}