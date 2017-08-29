<?php

namespace snewer\relationselect;

use Closure;
use yii\grid\GridView;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class RelationselectGridView extends GridView
{

    /**
     * @var array
     */
    public $appendColumns;

    /**
     * @var Closure
     */
    public $firstRow;

    /**
     * @inheritdoc
     */
    protected function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        if ($this->appendColumns) {
            $isArrayOfArrays = isset($this->appendColumns[0]) && is_array($this->appendColumns[0]);
            $this->columns = array_merge($this->columns, $isArrayOfArrays ? $this->appendColumns : [$this->appendColumns]);
        }
        parent::initColumns();
    }

    /**
     * @inheritdoc
     */
    public function renderTableBody()
    {

        if ($this->firstRow instanceof Closure) {
            $firstRow = call_user_func($this->firstRow, $this);
            $rows = $firstRow ? [$firstRow] : [];
        } else {
            $rows = [];
        }

        // from parent:

        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        foreach ($models as $index => $model) {
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($model, $key, $index);

            if ($this->afterRow !== null) {
                $row = call_user_func($this->afterRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows) && $this->emptyText !== false) {
            $colspan = count($this->columns);

            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        } else {
            return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view = $this->getView();
        $view->registerJs("jQuery('#$id').yiiRelationselectGridView($options);");
        // Код ниже добавлен из метода run класса BaseListView, от которого наследуется
        // класс GridView, от которого наследуется данный класс.
        // Сделано это вместо вызова parent::run(), который бы приводил
        // к добавлению конфликтного Javascript кода из GridView класса.
        if ($this->showOnEmpty || $this->dataProvider->getCount() > 0) {
            $content = preg_replace_callback("/{\\w+}/", function ($matches) {
                $content = $this->renderSection($matches[0]);

                return $content === false ? $matches[0] : $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'div');
        echo Html::tag($tag, $content, $options);
    }

}