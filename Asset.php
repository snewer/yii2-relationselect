<?php

namespace snewer\relationselect;

use yii\web\AssetBundle;

class Asset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/snewer/yii2-relationselect/assets';

    public $css = [
        'style.css'
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'script.js',
        'yii.gridView.js'
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset'
    ];

}