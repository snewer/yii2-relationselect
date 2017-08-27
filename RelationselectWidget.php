<?php

namespace snewer\relationselect;

use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\grid\GridView;
use yii\grid\Column;
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
        $this->columns[] = $this->getGridColumn();
        $this->attributeName = Html::getAttributeName($this->attribute);
        foreach ($this->model->behaviors as $behavior) {
            if ($behavior instanceof RelationselectBehavior && $behavior->attributeName == $this->attributeName) {
                $this->_behavior = $behavior;
                break;
            }
        }
        if ($this->_behavior === null) {
            throw new InvalidConfigException("К модели должно быть привязано поведение RelationselectBehavior для атрибута '{$this->attribute}'.");
        }
    }

    private function getGridColumn()
    {
        return [
            'class' => Column::className(),
            'content' => function ($model, $key, $index) {
                $active = in_array($model->primaryKey, $this->getModelsIds());
                $result = '';
                $result .= Html::tag('button', '<span class="glyphicon glyphicon-remove"></span> ' . ($this->isMultiselect() ? 'Отвязать' : 'Отменить'), [
                    'data-id' => $model->primaryKey,
                    'data-action' => 'unlink',
                    'style' => 'width: 100%; text-align: left;' .  ($active ? '' : ' display:none;'),
                    'type' => 'button',
                    'class' => 'btn btn-danger'
                ]);

                $result .= Html::tag('button', '<span class="glyphicon glyphicon-plus"></span> ' . ($this->isMultiselect() ? 'Привязать' : 'Выбрать'), [
                    'data-id' => $model->primaryKey,
                    'data-action' => 'link',
                    'style' => 'width: 100%; text-align: left;' .  ($active ? ' display:none;' : ''),
                    'type' => 'button',
                    'class' => 'btn btn-primary'
                ]);
                return $result;
            },
            'contentOptions' => [
                'style' => 'width: 1px;'
            ]
        ];
    }

    /**
     * Является ли связь множественной.
     * @return boolean
     */
    private function isMultiselect()
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

        ob_start();
        echo Html::beginTag('select', [
            'id' => $selectInputId,
            'name' => Html::getInputName($this->model, $this->attribute),
            'multiple' => $this->isMultiselect(),
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
        echo GridView::widget([
            'dataProvider' => $this->dataProvider ?: $this->_behavior->getRelationModelsDataProvider(),
            'columns' => $this->columns
        ]);
        Pjax::end();
        $this->registerJs($selectInputId, $pjaxId);
        return ob_get_clean();
    }

    /**
     * Удаляет символ переноса строки и все пробельные символы вокруг них.
     * Используется для вывода JS кода в одну строку.
     * @param string $str
     * @param string $replacement
     * @return string
     */
    private function removeNewlines($str, $replacement = '')
    {
        return preg_replace("/\\s*\n\\s*/", $replacement, trim($str));
    }

    private function registerJs($selectInputId, $pjaxId)
    {
        $this->view->registerJs($this->removeNewlines(<<<JS
(function(){
    var selectInput = jQuery('#$selectInputId');
    var multiple = selectInput.prop('multiple');
    var pjax = jQuery('#$pjaxId');
    pjax.on('pjax:success', function(){
        jQuery(this).find('[data-action=link]').each(function(){
            var id = jQuery(this).data('id');
            if (multiple) {
                if (selectInput.val().indexOf(id + '') > -1) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            } else {
                if (id == selectInput.val()) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            }
        });
        jQuery(this).find('[data-action=unlink]').each(function(){
            var id = jQuery(this).data('id');
            if (multiple) {
                if (selectInput.val().indexOf(id + '') > -1) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            } else {
                if (id == selectInput.val()) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            }
        });
    });
    pjax.on('click', '[data-action=link]', function(){
        var id = jQuery(this).data('id');
        if (multiple) {
            selectInput.append('<option value="' + id + '" selected>' + id + '</option>');
            jQuery(this).hide().siblings('[data-action=unlink]').show();
        } else {
            selectInput.html('<option value="' + id + '" selected>' + id + '</option>');
            pjax.find('[data-action=unlink]').hide();
            pjax.find('[data-action=link]').show();
            jQuery(this).hide().siblings('[data-action=unlink]').show();
        }
    });
    pjax.on('click', '[data-action=unlink]', function(){
        var id = jQuery(this).data('id');
        if (multiple) {
            selectInput.find('option[value=' + id + ']').remove();
            jQuery(this).hide().siblings('[data-action=link]').show();
        } else {
            selectInput.html('');
            pjax.find('[data-action=unlink]').hide();
            pjax.find('[data-action=link]').show();
        }
    });
})();
JS
        ));
    }

}