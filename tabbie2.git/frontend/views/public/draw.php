<?php

use kartik\grid\GridView;

$this->context->menuItems = [
	['label' => Yii::t("app", 'Overview'), 'url' => ["public/rounds", "tournament_id" => $round->tournament_id, "accessToken" => $round->tournament->accessToken]],
	['label' => Yii::t("app", 'Run'), 'url' => "#run", "linkOptions" => ["class" => "run"]],
];

$this->title = "Round " . $round->number . " Draw";
?>
<div class="row" id="table">
	<div class="col-sm-12">
		<?
		$gridColumns = [
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'venue.name',
				'label' => Yii::t("app", 'Venue'),
				'width' => "15%",
			],
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'og_team.name',
				'label' => Yii::t("app", "OG Team"),
			],
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'oo_team.name',
				'label' => Yii::t("app", "OO Team"),
			],
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'cg_team.name',
				'label' => Yii::t("app", 'CG Team'),
			],
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'co_team.name',
				'label' => Yii::t("app", 'CO Team'),
			],
			[
				'class' => '\kartik\grid\DataColumn',
				'attribute' => 'panel',
				'label' => Yii::t("app", 'Adjudicator'),
				'format' => 'raw',
				'width' => '40%',
				'value' => function ($model, $key, $index, $widget) {
					$list = array();
					$panel = common\models\Panel::findOne($model->panel_id);
					if ($panel) {
						$chair = common\models\AdjudicatorInPanel::findOne([
							"panel_id" => $panel->id,
							"function" => "1",
						]);

						foreach ($panel->adjudicators as $adj) {
							if ($adj->id == $chair->adjudicator_id) {
								array_unshift($list, "<b>" . $adj->user->name . "</b>");
							}
							else
								$list[] = $adj->user->name;
						}

						return implode(", ", $list);
					}
					return "";
				}
			],
		];

		echo GridView::widget([
			'dataProvider' => $dataProvider,
			'columns' => $gridColumns,
			'showPageSummary' => false,
			'bootstrap' => true,
			'hover' => true,
			'responsive' => false,
			'floatHeader' => true,
			'layout' => "{items}\n{pager}",
			'floatHeaderOptions' => ['scrollingTop' => 50],
			'id' => 'team-table',
		])
		?>
	</div>
</div>
<? if ($round->infoslide): ?>
	<? $motionStyle = "display:none;"; ?>
	<div class="row" id="drawdisplay" style="width: 90%; margin: 0 auto;  display:none;">
		<center>
			<?= yii\helpers\Html::button(Yii::t("app", "Show Info Slide"), ["id" => 'infoslide', "class" => "btn btn-success"]) ?>
			<?= yii\helpers\Html::button(Yii::t("app", "Show Motion"), ["disabled" => "disabled", "class" => "btn btn-success", "id" => 'motion']) ?>
		</center>
		<div class="col-sm-12" id="infoslideContent" style="display:none; margin-bottom: 100%">
			<h2><?= $round->infoslide ?></h2>
		</div>
		<div class="col-sm-12 text-center" id="motionContent"
		     data-href="<?= yii\helpers\Url::to(["public/start-round",
			     "id" => $round->id,
			     "tournament_id" => $round->tournament_id,
			     "accessToken" => $round->tournament->accessToken
		     ]) ?>"
		     style="display:none; margin-top: 20px;">
			<h1><?= $round->motion ?></h1>
		</div>
	</div>
<? else: ?>
	<div class="row" id="drawdisplay" style="width: 90%; margin: 0 auto; display:none;">
		<center>
			<?= yii\helpers\Html::button(Yii::t("app", "Show Motion"), ["class" => "btn btn-success", "id" => 'motion']) ?>
		</center>
		<div class="col-sm-12 text-center" id="motionContent"
		     data-href="<?= yii\helpers\Url::to(["public/start-round",
			     "id" => $round->id,
			     "tournament_id" => $round->tournament_id,
			     "accessToken" => $round->tournament->accessToken
		     ]) ?>"
		     style="display:none; margin-top: 50px;">
			<h1><?= $round->motion ?></h1>
		</div>
	</div>
<? endif; ?>
</div>
</div>
</div>


</div>