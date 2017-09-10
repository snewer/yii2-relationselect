<?php

namespace snewer\relationselect;

use yii\base\Behavior;
use yii\validators\Validator;

class RelationselectFilterModelBehavior extends Behavior
{

    public $ids;
    public $ids_operator;

    /**
     * @param \yii\base\Model $owner
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $validators = $owner->validators;
        $validators->append(Validator::createValidator('ids', $owner, 'match', ['pattern' => '/(\d+,)*\d+/']));
        $validators->append(Validator::createValidator('in', $owner, 'ids_operator', ['range' => ['in', 'not in']]));
    }

}