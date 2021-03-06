<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$tournament = $this->context->_getContext();
$this->params['breadcrumbs'][] = ['label' => $tournament->fullname, 'url' => ['tournament/view', "id" => $tournament->id]];

$this->title = Yii::t('app', 'Language Status Review');

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="language-index">

	<?
	$gridColumns = [
		[
			'class' => '\kartik\grid\SerialColumn',
		],
		[
			'class'              => '\kartik\grid\DataColumn',
			'attribute'          => 'name',
			'filterType'         => GridView::FILTER_SELECT2,
			'filter' => \common\models\search\TeamSearch::getSpeakerSearchArray($tournament->id),
			//'filter' => \common\models\search\UserSearch::getSearchTeamArray($tournament->id),
			'filterWidgetOptions' => [
				'pluginOptions' => ['allowClear' => true],
			],
			'filterInputOptions' => ['placeholder' => Yii::t("app", 'Any {object} ...', ['object' => Yii::t("app", 'User')])],
		],
		[
			'class'              => '\kartik\grid\DataColumn',
			'attribute'          => 'language_status',
			'format'             => "raw",
			'value'              => function ($model, $key, $index, $widget) {
				return \common\models\User::getLanguageStatusLabel($model->language_status);
			},
			'width'              => '25%',
			'filterType'         => GridView::FILTER_SELECT2,
			'filter' => \common\models\User::getLanguageStatusLabelArray(true, true),
			'filterWidgetOptions' => [
				'pluginOptions' => ['allowClear' => true],
			],
			'filterInputOptions' => ['placeholder' => Yii::t("app", 'Any {object} ...', ['object' => Yii::t("app", 'Status')])],
		],
		/*[
			'class' => '\kartik\grid\DataColumn',
			'attribute' => 'societyName',
			'filterType' => GridView::FILTER_SELECT2,
			'filter' => \common\models\search\SocietySearch::getSearchArray($tournament->id),
			'filterWidgetOptions' => [
				'pluginOptions' => ['allowClear' => true],
			],
			'filterInputOptions' => ['placeholder' => 'Any Society'],
		],*/
		[
			'class'    => 'kartik\grid\ActionColumn',
			'template' => '{ENL}&nbsp;{ESL}&nbsp;{EFL}&nbsp;{Interview}&nbsp;{NOV}',
			'buttons'  => [
				"Interview" => function ($url, $model) {
					return Html::a("Request Interview", $url, [
						'title'              => Yii::t('app', 'Request an interview'),
						'data-pjax'          => '0',
						'data-toggle-active' => $model->id,
						'class'              => 'btn btn-default',
					]);
				},
				"ENL" => function ($url, $model) {
					return Html::a("Set to ENL", $url, [
						'title'     => Yii::t('app', 'Set ENL'),
						'data-pjax' => '0',
						'data-toggle-active' => $model->id,
						'class'     => 'btn btn-default',
					]);
				},
				"ESL" => function ($url, $model) {
					return Html::a("Set to ESL", $url, [
						'title'     => Yii::t('app', 'Set ESL'),
						'data-pjax' => '0',
						'data-toggle-active' => $model->id,
						'class'     => 'btn btn-default',
					]);
				},
				"EFL" => function ($url, $model) {
					return Html::a("Set to EFL", $url, [
						'title'     => Yii::t('app', 'Set ESL'),
						'data-pjax' => '0',
						'data-toggle-active' => $model->id,
						'class' => 'btn btn-default',
					]);
				},
				"NOV" => function ($url, $model) {
					return Html::a("Set to Novice", $url, [
						'title' => Yii::t('app', 'Set Novice'),
						'data-pjax' => '0',
						'data-toggle-active' => $model->id,
						'class'     => 'btn btn-default',
					]);
				},
			],
			'urlCreator' => function ($action, $model, $key, $index) {
				return \yii\helpers\Url::to(["language/set", "userid" => $key, "status" => $action, "tournament_id" => $this->context->_getContext()->id]);
			},
			'dropdown' => true,
			'vAlign'   => 'middle',
			'width'    => '120px',
		],
	];

	$toolbar = [
		'{export}',
		'{toggleData}',
	];

	echo GridView::widget([
		'dataProvider'    => $dataProvider,
		'filterModel'     => $searchModel,
		'columns'         => $gridColumns,
		'id'              => 'language',
		'pjax'            => true,
		'showPageSummary' => false,
		'responsive'      => false,
		'hover'           => true,
		'floatHeader'     => true,
		'floatHeaderOptions' => ['scrollingTop' => 100],
		'panel'   => [
			'type'    => GridView::TYPE_DEFAULT,
			'heading' => Html::encode($this->title),
		],
		'toolbar' => $toolbar,

	])
	?>

</div>
