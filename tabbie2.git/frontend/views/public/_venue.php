<?php


/* @var $model Debate */
?>
<?= \kartik\helpers\Html::a(
	'<div class="status ' . (($model->result) ? "entered" : "missing") . '"></div>' . $model->venue->name,
	["result/create", "tournament_id" => $model->tournament_id, "id" => $model->id, "accessToken" => $model->tournament->accessToken],
	["class" => "btn btn-default", "data-pjax" => 0]
) ?>
