<?php

namespace snewer\relationselect;

use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\widgets\Pjax;

/**
 * @property RelationselectBehavior|\yii\db\ActiveRecord $model
 */
class RelationselectWidget extends InputWidget
{

    public $columns = [];

    public $dataProvider;

    /**
     * Название атрибута (без []).
     * @var string
     */
    private $attributeName;

    /**
     * RelationselectBehavior поведение, связанное атрибутом виджета.
     * @var RelationselectBehavior
     */
    private $_behavior;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->attributeName = Html::getAttributeName($this->attribute);
        // Поиск связанного с виджетом RelationselectBehavior поведение модели.
        foreach ($this->model->behaviors as $behavior) {
            if ($behavior instanceof RelationselectBehavior && $behavior->attributeName == $this->attributeName) {
                $this->_behavior = $behavior;
                break;
            }
        }
        // Использовать виджет без привязки к соответствующему поведению модели нет смысла.
        if ($this->_behavior === null) {
            throw new InvalidConfigException("К модели должно быть привязано поведение RelationselectBehavior для атрибута '{$this->attribute}'.");
        }
        // Использовать виджет с небезопасным атрибутом нет смысла.
        if (!$this->model->isAttributeSafe($this->attributeName)) {
            throw new InvalidConfigException("Атрибут '{$this->attributeName}' должен быть безопасным.");
        }
    }

    /**
     * Является ли связь множественной.
     * @return boolean
     */
    private function multiple()
    {
        return $this->_behavior->getRelation()->multiple;
    }

    private $_models;

    /**
     * @return array
     */
    private function getModels()
    {
        if (!isset($this->_models)) {
            $this->_models = $this->_behavior->getNewRelatedModels() ?: $this->_behavior->getOldRelatedModels();
        }
        return $this->_models;
    }

    private function getModelsIds()
    {
        $result = [];
        /* @var \yii\db\ActiveRecord $model */
        foreach ($this->getModels() as $model) {
            $result[] = $model->primaryKey;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $selectInputId = Html::getInputId($this->model, $this->attribute);
        $pjaxId = Pjax::$autoIdPrefix . Pjax::$counter++;
        $this->registerJs($selectInputId, $pjaxId);

        ob_start();
        echo Html::beginTag('select', [
            'id' => $selectInputId,
            'name' => Html::getInputName($this->model, $this->attribute),
            'multiple' => $this->multiple(),
            'style' => 'display:none'
        ]);
        foreach ($this->getModels() as $model) {
            echo Html::tag('option', $model->id, [
                'value' => $model->id,
                'selected' => true
            ]);
        }
        echo Html::endTag('select');
        Pjax::begin([
            'enablePushState' => false,
            'id' => $pjaxId
        ]);
        echo RelationselectGridView::widget([
            'dataProvider' => $this->dataProvider ?: $this->_behavior->getRelationModelsDataProvider(),
            'columns' => $this->columns,
            'selectedModelsIds' => $this->getModelsIds(),
            'multiple' => $this->multiple()
        ]);
        Pjax::end();

        return ob_get_clean();
    }

    private function registerJs($selectInputId, $pjaxId)
    {
        Asset::register($this->view);
        $this->view->registerJs("relationselectWidget('$selectInputId', '$pjaxId');");
    }

}