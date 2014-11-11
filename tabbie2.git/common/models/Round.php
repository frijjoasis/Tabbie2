<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "round".
 *
 * @property integer $id
 * @property integer $tournament_id
 * @property string $motion
 * @property string $infoslide
 * @property string $time
 * @property bool $published
 *
 * @property DrawAfterRound[] $drawAfterRounds
 * @property Tournament $tournament
 */
class Round extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'round';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'tournament_id', 'motion'], 'required'],
            [['id', 'tournament_id', 'published'], 'integer'],
            [['motion', 'infoslide'], 'string'],
            [['time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'Round Number'),
            'tournament_id' => Yii::t('app', 'Tournament ID'),
            'motion' => Yii::t('app', 'Motion'),
            'infoslide' => Yii::t('app', 'Info Slide'),
            'time' => Yii::t('app', 'Time'),
            'published' => Yii::t('app', 'Published'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDrawAfterRounds() {
        return $this->hasMany(DrawAfterRound::className(), ['round_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTournament() {
        return $this->hasOne(Tournament::className(), ['id' => 'tournament_id']);
    }

}