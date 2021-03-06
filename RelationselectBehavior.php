<?php

namespace snewer\relationselect;

use Closure;
use yii\base\Behavior;
use yii\db\ActiveRecord;

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

    /**
     * @var string
     */
    public $filterModel;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterUpdateEventHandler',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdateEventHandler'
        ];
    }

    private function linkModels($models)
    {
        if (!$this->processLinking) return;
        foreach ($models as $model) {
            $this->owner->link($this->relationName, $model);
        }
    }

    private function unlinkModels($models, $delete = true)
    {
        if (!$this->processLinking) return;
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
        if ($models) {
            return is_array($models) ? $models : [$models];
        } else {
            return [];
        }
    }

    public function getOldRelatedModelsIds($models)
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

    public function afterUpdateEventHandler($event)
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

}