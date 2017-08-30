<?php

namespace snewer\relationselect;

use Closure;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\widgets\Pjax;
use yii\grid\DataColumn;
use yii\db\Expression;
use yii\data\ActiveDataProvider;

/**
 * @property \yii\db\ActiveRecord $model
 */
class RelationselectWidget extends InputWidget
{

    public $inputTemplate = '{input}';

    public $inputOptions;

    public $inputCellOptions = ['class' => 'text-center'];

    public $columns = [];

    /**
     * Название атрибута (без []).
     * @var string
     */
    private $attributeName;

    private $inputId;

    private $inputName;

    private $selectionInputName;

    private $pjaxId;

    private $filterModel;

    private $dataProvider;

    /**
     * RelationselectBehavior поведение, связанное атрибутом виджета.
     * @var RelationselectBehavior
     */
    private $behavior;

    private function initBehavior()
    {
        // Поиск связанного с виджетом RelationselectBehavior поведение модели.
        foreach ($this->model->behaviors as $behavior) {
            if ($behavior instanceof RelationselectBehavior && $behavior->attributeName == $this->attributeName) {
                $this->behavior = $behavior;
                break;
            }
        }
        // Использовать виджет без привязки к соответствующему поведению модели нет смысла.
        if ($this->behavior === null) {
            throw new InvalidConfigException("К модели должно быть привязано поведение RelationselectBehavior для атрибута '{$this->attribute}'.");
        }
    }

    private function initFilterModel()
    {
        if ($this->behavior->filterModel) {
            $this->filterModel = new $this->behavior->filterModel;
            if (is_callable([$this->filterModel, 'search'])) {
                $this->dataProvider = $this->filterModel->search(Yii::$app->request->queryParams);
            } else {
                throw new InvalidConfigException('Модель для фильтра должна иметь метод search.');
            }

        }
    }

    private function initDataProvider()
    {
        if ($this->dataProvider === null) {
            $modelClass = $this->behavior->getRelation()->modelClass;
            /* @var \yii\db\ActiveQuery $query */
            $query = call_user_func([$modelClass, 'find']);
            $modelsIds = $this->behavior->getOldRelatedModelsIds($this->behavior->getOldRelatedModels());
            if ($modelsIds) {
                $query->addOrderBy(new Expression('[[id]] IN (' . implode(', ', $modelsIds) . ') DESC'));
            }
            $this->dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 10
                ]
            ]);
        }

        if ($this->behavior->queryModifier instanceof Closure) {
            call_user_func($this->behavior->queryModifier, $this->dataProvider->query);
        }

    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->attributeName = Html::getAttributeName($this->attribute);
        $this->selectionInputName = $this->model->formName() . '-' . $this->attributeName . '--helperInput';
        $this->pjaxId = Pjax::$autoIdPrefix . Pjax::$counter++;
        $this->inputId = Html::getInputId($this->model, $this->attribute);
        $this->inputName = Html::getInputName($this->model, $this->attribute);
        $this->initBehavior();
        $this->initFilterModel();
        $this->initDataProvider();
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
        return $this->behavior->getRelation()->multiple;
    }

    private $_models;

    /**
     * @return array
     */
    private function getModels()
    {
        if (!isset($this->_models)) {
            $this->_models = $this->behavior->getNewRelatedModels() ?: $this->behavior->getOldRelatedModels();
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

    private function getSelectionInput($pk)
    {
        $checked = in_array($pk, $this->getModelsIds());
        $inputType = $this->multiple() ? 'checkbox' : 'radio';
        $inputOptions = $this->inputOptions ?: [];
        $inputOptions['value'] = $pk;
        $input = Html::$inputType($this->selectionInputName, $checked, $inputOptions);
        return str_replace('{input}', $input, $this->inputTemplate);
    }

    private function getSelectionColumn()
    {

        $ids = implode(',', $this->getModelsIds());

        return [
            'class' => DataColumn::className(),
            'attribute' => 'ids',
            'header' => 'Выбор',
            'content' => function ($model, $key, $index) {
                return $this->getSelectionInput($model->primaryKey);
            },
            'contentOptions' => $this->inputCellOptions,
            'filter' => [
                $ids => 'Выбрано',
                '!' . $ids => 'Не выбрано',
            ]
        ];
    }

    private function getFirstRow(RelationselectGridView $grid)
    {
        if ($this->multiple()) {
            return null;
        } else {
            $columnsCount = count($grid->columns);
            $cell = Html::tag('td', 'Не выбирать:', [
                'colspan' => $columnsCount - 1,
                'class' => 'text-right'
            ]);
            $cell2 = Html::tag('td', $this->getSelectionInput(''), $this->inputCellOptions);
            return Html::tag('tr', $cell . $cell2);
        }
    }

    /**
     * Удаляет символы переноса строки и все пробельные символы вокруг них.
     * Используется для вывода JS кода в одну строку.
     * @param string $str
     * @param string $replacement
     * @return string
     */
    private function removeNewlines($str, $replacement = '')
    {
        return preg_replace("/\\s*\n\\s*/", $replacement, trim($str));
    }

    private function registerJs()
    {
        Asset::register($this->view);
        $idsFilterName = '';
        if ($this->filterModel) {
            $idsFilterName = Html::getInputName($this->filterModel, 'ids');
        }
        $js = $this->removeNewlines("relationselectWidget({
            selectionInputName: '{$this->selectionInputName}',
            inputId: '{$this->inputId}',
            pjaxId: '{$this->pjaxId}',
            idsFilterName: '$idsFilterName'
        });");
        $this->view->registerJs($js);
    }

    private function getHtml()
    {
        ob_start();
        echo Html::beginTag('select', [
            'id' => $this->inputId,
            'name' => $this->inputName,
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
            'id' => $this->pjaxId,
            'timeout' => 5000,
            'enablePushState' => false
        ]);
        echo RelationselectGridView::widget([
            'dataProvider' => $this->dataProvider,
            'filterModel' => $this->filterModel,
            'columns' => $this->columns,
            'appendColumns' => $this->getSelectionColumn(),
            'firstRow' => function ($gridViewInstance) {
                return $this->getFirstRow($gridViewInstance);
            }
        ]);
        Pjax::end();
        return ob_get_clean();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerJs();
        return $this->getHtml();
    }

}