<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "society".
 *
 * @property integer       $id
 * @property string        $fullname
 * @property string        $abr
 * @property string        $city
 * @property integer       $country_id
 * @property Adjudicator[] $adjudicators
 * @property InSociety[]   $inSocieties
 * @property Country       $country
 * @property User[]        $users
 * @property Team[]        $teams
 */
class Society extends \yii\db\ActiveRecord
{

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'society';
	}

	public static function generateAbr($name)
	{
		$name = trim($name);
		$abr = "";
		$parts = explode(" ", $name);
		if (count($parts) == 1) {
			return Society::uniqueAbr($name);
		}

		foreach ($parts as $part) {
			if (isset($part[0]))
				$abr .= $part[0];
		}
		$abr = strtoupper($abr);

		return Society::uniqueAbr($abr);
	}

	public static function uniqueAbr($abr)
	{
		$candidate = $abr;
		$count = 1;
		$i = 1;
		while (Society::find()->where(["abr" => $candidate])->exists()) {
			$candidate = $abr . $i;
			$i++;
		}

		return $candidate;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['country_id', 'fullname', 'abr'], 'required'],
			[['country_id'], 'integer'],
			[['fullname', 'city'], 'string', 'max' => 255],
			[['abr'], 'string', 'max' => 45],
			[['abr'], 'unique'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id'         => Yii::t('app', 'ID'),
			'fullname'   => Yii::t('app', 'Fullname'),
			'abr'        => Yii::t('app', 'Abbrevation'),
			'city'       => Yii::t('app', 'City'),
			'country_id' => Yii::t('app', 'Country'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAdjudicators()
	{
		return $this->hasMany(Adjudicator::className(), ['society_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getInSocieties()
	{
		return $this->hasMany(InSociety::className(), ['society_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::className(), ['id' => 'username_id'])
			->viaTable('in_society', ['society_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCountry()
	{
		return $this->hasOne(Country::className(), ['id' => 'country_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTournaments()
	{
		return $this->hasMany(Tournament::className(), ['hosted_by_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTeams()
	{
		return $this->hasMany(Team::className(), ['society_id' => 'id']);
	}
}
