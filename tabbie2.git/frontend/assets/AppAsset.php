<?php

/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
class AppAsset extends AssetBundle
{

	public $sourcePath = '@frontend/assets';
	public $css = [
		'css/site.css',
	];
	public $js = [
		'js/site.js',
		'js/notification.js',
		'js/socket.io-1.3.5.js',
	];
	public $depends = [
		'yii\web\YiiAsset',
		'yii\web\JqueryAsset',
		'yii\bootstrap\BootstrapAsset',
	];
}