<?php

namespace common\models;

use algorithms\TabAlgorithm;
use JmesPath\Tests\_TestJsonStringClass;
use kartik\widgets\TimePicker;
use Yii;
use yii\base\Exception;
use yii\caching\DbDependency;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\UrlManager;
use DateTimeZone;
use URLify;

/**
 * This is the model class for table "tournament".
 *
 * @property integer $id
 * @property string $url_slug
 * @property integer $status
 * @property integer $hosted_by_id
 * @property string $name
 * @property string $fullname
 * @property string $start_date
 * @property string $end_date
 * @property string $timezone
 * @property string $logo
 * @property string $time
 * @property string $tabAlgorithmClass
 * @property integer $expected_rounds
 * @property integer $has_esl
 * @property integer $has_efl
 * @property integer $has_novice
 * @property integer $has_final
 * @property integer $has_semifinal
 * @property integer $has_quarterfinal
 * @property integer $has_octofinal
 * @property string $accessToken
 * @property string $badge
 * @property Adjudicator[] $adjudicators
 * @property Panel[] $panels
 * @property Round[] $rounds
 * @property Round[] $Inrounds
 * @property Round[] $Outrounds
 * @property Team[] $teams
 * @property User[] $convenors
 * @property User[] $tabmasters
 * @property User[] $cas
 * @property TournamentHasQuestion[] $tournamentHasQuestions
 * @property Question[] $questions
 * @property Venue[] $venues
 */
class Tournament extends \yii\db\ActiveRecord
{

    const STATUS_CREATED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_CLOSED = 2;
    const STATUS_HIDDEN = 3;

    public static function getStatusLabel($id)
    {
        $status = [
            self::STATUS_CREATED => Yii::t("app", "Created"),
            self::STATUS_RUNNING => Yii::t("app", "Running"),
            self::STATUS_CLOSED => Yii::t("app", "Closed"),
            self::STATUS_HIDDEN => Yii::t("app", "Hidden")
        ];
        return $status[$id];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tournament';
    }

    /**
     * Find a Tournament by Primary Key
     *
     * @param integer $id
     * @param boolean $live
     *
     * @uses Tournamnet::findOne
     * @return null|Tournamnet
     */
    public static function findByPk($id, $live = false)
    {
        $tournament = Yii::$app->cache->get("tournament_" . $id);
        if (!$tournament instanceof Tournament || $live) {
            $tournament = Tournament::findOne(["id" => $id]);
            Yii::$app->cache->set("tournament_" . $id, $tournament, 200);
        }

        return $tournament;
    }

    public static function getTabAlgorithmOptions()
    {
        $algos = [];
        $files = scandir(Yii::getAlias("@algorithms/algorithms/"));
        foreach ($files as $className) {
            if (substr($className, 0, 1) == ".") {
                continue;
            }
            $filename = pathinfo($className)['filename'];
            $class = Tournament::getTabAlgorithmClass($filename);
            if ($class::version() !== null) {
                $algos[$filename] = $class::title() . " (v" . $class::version() . ")";
            }
        }

        return $algos;
    }

    /**
     * Returns the fully clssified Class Name
     * @param $algoClass
     * @return string
     */
    public static function getTabAlgorithmClass($algoClass)
    {
        return 'algorithms\\algorithms\\' . $algoClass;
    }

    /**
     * Get a new Instance of the Tab Algorithm
     *
     * @return \algorithms\TabAlgorithm
     */
    public function getTabAlgorithmInstance()
    {
        $className = Tournament::getTabAlgorithmClass($this->tabAlgorithmClass);
        return new $className();
    }

    public static function getTimeZones()
    {
        $now = new \DateTime();
        $timezones = [];

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $now->setTimezone(new DateTimeZone($timezone));
            $offsets[] = $offset = $now->getOffset();
            $timezones[$timezone] = '(' . self::format_GMT_offset($offset) . ') ' . self::format_timezone_name($timezone);
        }

        array_multisort($offsets, $timezones);
        return $timezones;
    }

    private static function format_GMT_offset($offset)
    {
        $hours = intval($offset / 3600);
        $minutes = abs(intval($offset % 3600 / 60));
        return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
    }

    private static function format_timezone_name($name)
    {
        $name = str_replace('/', ', ', $name);
        $name = str_replace('_', ' ', $name);
        $name = str_replace('St ', 'St. ', $name);
        return $name;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url_slug', 'hosted_by_id', 'name', 'start_date', 'end_date', 'timezone'], 'required'],
            [['hosted_by_id', 'expected_rounds', 'status'], 'integer'],
            [['name', 'url_slug'], 'trim'],
            [['start_date', 'end_date', 'time', 'has_esl', 'has_efl', 'has_novice', 'has_final', 'has_semifinal', 'has_octofinal', 'has_quarterfinal'], 'safe'],
            [['url_slug', 'name', 'tabAlgorithmClass'], 'string', 'max' => 100],
            [['logo', 'badge'], 'string', 'max' => 255],
            ['accessToken', 'string'],
            [['url_slug'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'Tournament') . ' ' . Yii::t('app', 'ID'),
            'hosted_by_id' => Yii::t('app', 'Hosted by'),
            'name' => Yii::t('app', 'Tournament Name'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'timezone' => Yii::t("app", 'Timezone'),
            'logo' => Yii::t('app', 'Logo'),
            'time' => Yii::t('app', 'Time'),
            'url_slug' => Yii::t('app', 'URL Slug'),
            'tabAlgorithmClass' => Yii::t('app', 'Tab Algorithm'),
            'expected_rounds' => Yii::t("app", "Expected number of rounds"),
            'has_esl' => Yii::t("app", "Show ESL Ranking"),
            'has_efl' => Yii::t("app", "Show EFL Ranking"),
            'has_novice' => Yii::t("app", "Show Novice Ranking"),
            'has_final' => Yii::t("app", "Has a grand final round"),
            'has_semifinal' => Yii::t("app", "Has semifinal rounds"),
            'has_quarterfinal' => Yii::t("app", "Has quarterfinals rounds"),
            'has_octofinal' => Yii::t("app", "Has octofinals rounds"),
            'accessToken' => Yii::t("app", 'Access Token'),
            'badge' => Yii::t("app", 'Participant Badge'),
        ];
    }

    /**
     * Call before model save
     *
     * @param type $insert
     *
     * @return boolean
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert === true) //Do only on new Records
            {
                $this->generateUrlSlug();
                $this->generateAccessToken();
            }

            return true;
        }

        return false;
    }

    /**
     * Generate a unique URL SLUG ... never fails  ;)
     */
    public function generateUrlSlug()
    {
        $potential_slug = URLify::filter($this->fullname);
        $potential_slug = preg_replace("/\s?20\d\d\s?/", "", $potential_slug);
        $potential_slug = str_replace(" ", "-", $potential_slug);

        if (Tournament::findByUrlSlug($potential_slug) !== null) {
            $i = 1;
            $iterate_slug = $potential_slug . "-" . $i;
            while (Tournament::findByUrlSlug($iterate_slug) !== null) {
                $i++;
                $iterate_slug = $potential_slug . "-" . $i;
            }
            $potential_slug = $iterate_slug;
        }
        $this->url_slug = $potential_slug;

        return true;
    }

    public static function findByUrlSlug($slug)
    {
        return Tournament::findOne(["url_slug" => $slug]);
    }

    /**
     * Generate an accessURL for Runners and DrawDisplay
     *
     * @return string
     */
    public function generateAccessToken()
    {
        return $this->accessToken = substr(md5(uniqid(mt_rand(), true)), 0, 10);
    }

    /**
     * Check if user is the CA of the torunament
     *
     * @param int $userID
     *
     * @return boolean
     */
    public function isCA($userID)
    {
        $count = Ca::find()->tournament($this->id)->andWhere(["user_id" => $userID])->count();
        if ($count > 0) {
            return true;
        } else {
            if (Yii::$app->user->isAdmin()) //Admin secure override
            {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if user is registered
     *
     * @param integer $userID
     *
     * @return bool
     */
    public function isRegistered($userID)
    {

        if (Yii::$app->user->isAdmin() || $this->isConvenor($userID) || $this->isLanguageOfficer($userID) || $this->isTabMaster($userID)) {
            return true;
        }

        if ($this->isTeam($userID) || $this->isAdjudicator($userID)) {
            return true;
        }

        return false;

    }

    /**
     * Check if user is the convenor of the torunament
     *
     * @param int $userID
     *
     * @return boolean
     */
    public function isConvenor($userID)
    {
        $count = Convenor::find()->tournament($this->id)->andWhere(["user_id" => $userID])->count();
        if ($count > 0) {
            return true;
        } else {
            if (Yii::$app->user->isAdmin()) //Admin secure override
            {
                return true;
            }
        }

        return false;
    }

    public function isLanguageOfficer($userID)
    {
        if ($this->status != Tournament::STATUS_CLOSED) {
            $count = LanguageOfficer::find()->tournament($this->id)->andWhere(["user_id" => $userID])->count();
            if ($count > 0
            ) {
                \Yii::trace("User is LanguageOfficer for Tournament #" . $this->id, __METHOD__);

                return true;
            } else {
                if (Yii::$app->user->isAdmin()) //Admin secure override
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user is the tabmaster of the torunament
     *
     * @param int $userID
     *
     * @return boolean
     */
    public function isTabMaster($userID)
    {
        $count = Tabmaster::find()->tournament($this->id)->andWhere(["user_id" => $userID])->count();
        if ($count > 0) {
            return true;
        } else {
            if (Yii::$app->user->isAdmin()) //Admin secure override
            {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if user is Team
     *
     * @param $userID
     *
     * @return bool
     */
    public function isTeam($userID)
    {
        if ($this->isTeamA($userID) || $this->isTeamB($userID)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is Team A
     *
     * @param $userID
     *
     * @return bool
     */
    public function isTeamA($userID)
    {
        //check if Team
        $team = Team::find()->tournament($this->id)
            ->andWhere(["speakerA_id" => $userID])
            ->count();
        if ($team > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is Team B
     *
     * @param $userID
     *
     * @return bool
     */
    public function isTeamB($userID)
    {
        //check if Team
        $team = Team::find()->tournament($this->id)
            ->andWhere(["speakerB_id" => $userID])
            ->count();
        if ($team > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is Adjudicator
     *
     * @param $userID
     *
     * @return bool
     */
    public function isAdjudicator($userID)
    {
        //check if Adjudicator
        $adju = Adjudicator::find()->tournament($this->id)
            ->andWhere(["user_id" => $userID])
            ->count();
        if ($adju > 0) {
            return true;
        }

        return false;
    }

    public function getStatusOptions($id = null)
    {
        $options = [
            self::STATUS_CREATED => Yii::t("app", "Created"),
            self::STATUS_RUNNING => Yii::t("app", "Running"),
            self::STATUS_CLOSED => Yii::t("app", "Closed"),
        ];

        return ($id) ? $options[$id] : $options;
    }

    /**
     * Validate an AccessToken with the object
     *
     * @param $testToken
     *
     * @return bool
     */
    public function validateAccessToken($testToken)
    {
        if ($testToken == false) {
            return false;
        }

        if ($testToken == $this->accessToken) {
            return true;
        }

        return false;
    }

    public function getSchema($json = true)
    {
        $objStartDateTime = new \DateTime($this->start_date);
        $objEndDateTime = new \DateTime($this->end_date);

        $schema = [
            "@type" => "Festival",
            "name" => $this->fullname,
            "image" => $this->getLogo(true),
            "organizer" => [
                "name" => $this->hostedby->fullname,
            ],
            "startDate" => $objStartDateTime->format(\DateTime::ISO8601),
            "endDate" => $objEndDateTime->format(\DateTime::ISO8601),
            "url" => \yii\helpers\Url::to(["tournament/view", "id" => $this->id], true),
            "sameAs" => \yii\helpers\Url::to(["tournament/view", "id" => $this->id], true),
            "location" => [
                "@type" => "Place",
                "name" => $this->hostedby->fullname,
                "address" => $this->hostedby->city . ", " . $this->hostedby->country->name
            ]
        ];
        if ($json) {
            $schema = ["@context" => "http://schema.org"] + $schema;
        }

        return ($json) ? json_encode($schema, JSON_UNESCAPED_SLASHES) : $schema;
    }

    public function getLogo($absolute = false, $urlManager = null)
    {
        if (!$urlManager instanceof UrlManager) {
            $urlManager = Yii::$app->urlManager;
        }

        if ($this->logo !== null) {
            if ($absolute && substr($this->logo, 0, 4) != "http") {
                return $urlManager->createAbsoluteUrl($this->logo);
            } else {
                /** ende */
                return $this->logo;

            }
        } else {
            $defaultPath = Yii::getAlias("@frontend/assets/images/") . "default-tournament.png";

            if ($absolute)
                return Yii::$app->params["appUrl"] . Yii::$app->assetManager->publish($defaultPath)[1];
            else
                return Yii::$app->assetManager->publish($defaultPath)[1];
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdjudicators()
    {
        return $this->hasMany(Adjudicator::className(), ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getHostedby()
    {
        return $this->hasOne(Society::className(), ['id' => 'hosted_by_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnergyConfigs()
    {
        return $this->hasMany(EnergyConfig::className(), ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInrounds()
    {
        return $this->hasMany(Round::className(), ['tournament_id' => 'id'])->andWhere("type = " . Round::TYP_IN);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOutrounds()
    {
        return $this->hasMany(Round::className(), ['tournament_id' => 'id'])->andWhere("type > " . Round::TYP_IN);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeams()
    {
        return $this->hasMany(Team::className(), ['tournament_id' => 'id']);
    }

    public function getCAs()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('ca', ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConvenors()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('convenor', ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTabmasters()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('tabmaster', ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTournamentHasQuestions()
    {
        return $this->hasMany(TournamentHasQuestion::className(), ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions($type)
    {

        switch ($type) {
            case Feedback::FROM_CHAIR:
                $field = "C2W";
                break;
            case Feedback::FROM_WING:
                $field = "W2C";
                break;
            case Feedback::FROM_TEAM:
                $field = "T2C";
                break;
            default:
                throw new Exception("Wrong Parameter");
        }

        return $this->hasMany(Question::className(), ['id' => 'questions_id'])
            ->viaTable('tournament_has_question', ['tournament_id' => 'id'])
            ->where(["apply_" . $field => 1]);
    }

    public function getSocieties()
    {
        return $this->hasMany(Society::className(), ['id' => 'society_id'])
            ->viaTable('team', ['tournament_id' => 'id'])
            ->union(
                $this->hasMany(Society::className(), ['id' => 'society_id'])
                    ->viaTable('adjudicator', ['tournament_id' => 'id'])
            );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVenues()
    {
        return $this->hasMany(Venue::className(), ['tournament_id' => 'id']);
    }

    /**
     * Get the panels in that tournament
     *
     * @return type
     */
    public function getPanels()
    {
        return $this->hasMany(Panel::className(), ['tournament_id' => 'id']);
    }

    public function getLogoImage($width_max = null, $height_max = null, $options = [])
    {

        $alt = ($this->name) ? $this->getFullname() : "";
        $img_options = array_merge($options, ["alt" => $alt,
            "style" => "max-width: " . $width_max . "px; max-height: " . $height_max . "px;",
            "width" => $width_max,
            "height" => $height_max,
        ]);
        $img_options["class"] = "img-responsive img-rounded center-block" . (isset($img_options["class"]) ? " " . $img_options["class"] : "");

        return Html::img($this->getLogo(), $img_options);
    }

    public function getFullname()
    {
        return $this->name . " " . Yii::$app->formatter->asDate($this->end_date, "Y");
    }

    /**
     * Get the Badge URL
     * @return mixed|string
     */
    public function getBadge()
    {
        if ($this->badge !== null) {
            if (substr($this->badge, 0, 4) != "http") {
                return Url::to($this->badge, true);
            } else {
                return $this->badge;
            }
        } else {
            return "";
        }
    }

    /**
     * Get's the last round
     * @param bool $onlyOpen gets the last open round
     * @return Round
     */
    public function getLastRound($onlyOpen = false)
    {
        $roundQuery = $this->getRounds()
            ->where(["displayed" => 1])
            ->orderBy(['id' => SORT_DESC]);

        if ($onlyOpen == true)
            $roundQuery->andWhere(["closed" => 0]);

        return $roundQuery->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRounds()
    {
        return $this->hasMany(Round::className(), ['tournament_id' => 'id']);
    }

    /**
     * Get the Amount of Teams breaking in this tournament
     *
     * @return int
     */
    public function getAmountBreakingTeams()
    {
        if ($this->id == '526' || $this->id == 160 || $this->id == 1146) {
            //TODO: Fix this properly.
            return 48;
        }
        if ($this->has_octofinal) {
            return 32;
        }
        if ($this->has_quarterfinal) {
            return 16;
        }
        if ($this->has_semifinal) {
            return 8;
        }
        if ($this->has_final) {
            return 4;
        }

        return 0;
    }

    /**
     * Save a Tournament Logo
     *
     * @param \yii\web\UploadedFile $file
     */
    public function saveLogo($file)
    {
        if ($file) {
            $path = "tournaments/TournamentLogo-" . $this->url_slug . "." . $file->extension;
            $this->logo = Yii::$app->s3->save($file, $path);
        }
    }

    /**
     * Save a Tournament Badge
     *
     * @param \yii\web\UploadedFile $file
     */
    public function saveBadge($file)
    {
        if ($file) {
            $path = "badges/Badge-" . $this->url_slug . "." . $file->extension;
            $this->badge = Yii::$app->s3->save($file, $path);
        }
    }

    /**
     * @param null $id
     * @return bool / Team / Adjudicator
     */
    public function user_role($id = null)
    {
        if ($id == null) {
            $id = Yii::$app->user->id;
        }

        if (!is_int($id)) {
            return false;
        }

        $team = Team::find()
            ->tournament($this->id)
            ->andWhere("speakerA_id = $id OR speakerB_id = $id")
            ->one();

        if ($team instanceof Team) {
            return $team;
        }

        $adju = Adjudicator::find()
            ->tournament($this->id)
            ->andWhere("user_id = $id")
            ->one();
        if ($adju instanceof Adjudicator) {
            return $adju;
        }

        return false;
    }

    /**
     * @param null $id
     * @return bool / string
     */
    public function user_role_string($id = null)
    {
        if ($id == null) {
            $id = Yii::$app->user->id;
        }

        $role_obj = $this->user_role($id);
        if ($role_obj == false) {
            return $role_obj;
        }

        $role = null;
        if ($role_obj instanceof Team) {
            $role = 'debater';
        } elseif ($role_obj instanceof Adjudicator) {
            $role = 'adjudicator';
        }

        return $role;
    }

    /**
     * Returns the formatted Timezone to display
     * @return string
     */
    public function getFormatedTimeZone()
    {
        $now = new \DateTime('now', new DateTimeZone($this->timezone));
        $offset = $now->getOffset();

        return self::format_timezone_name($this->timezone) . " (" . self::format_GMT_offset($offset) . ")";
        //return $this->getNowUTC();
    }

    /**
     * @param Round $lastRound
     *
     * @return Array
     */
    public function hasOpenFeedback($userid)
    {
        $cache_key = $this->cacheKey("feedback_" . $userid);

        $feedbackDebates = false; //Yii::$app->cache->get($cache_key);

        if (!is_array($feedbackDebates)) {
            $team = Team::find()
                ->tournament($this->id)
                ->andWhere("speakerA_id = :aid OR speakerB_id = :bid", [
                    "aid" => $userid,
                    "bid" => $userid
                ])
                ->one();

            $judge = Adjudicator::find()
                ->tournament($this->id)
                ->andWhere(["user_id" => $userid])
                ->one();

            if ($team instanceof Team) {

                foreach (Team::getPos() as $pos) {
                    $debateQuery = Debate::find()
                        ->joinWith("round")
                        ->orderBy(["debate.round_id" => SORT_DESC])
                        ->andWhere($pos . "_team_id = " . $team->id)
                        ->andWhere($pos . "_feedback = 0")
                        ->andWhere('round.displayed=1');

                    $debate = $debateQuery->all();

                    foreach ($debate as $d) {
                        if ($d instanceof Debate)
                            $feedbackDebates[$d->round_id] = ["type" => Feedback::FROM_TEAM, "debate" => $d, "ref" => $d->{$pos . "_team_id"}];
                    }
                }

                if (is_array($feedbackDebates))
                    krsort($feedbackDebates);

            }

            if ($judge instanceof Adjudicator) {
                $aips = AdjudicatorInPanel::find()
                    ->joinWith("debate")
                    ->where([
                        "adjudicator_id" => $judge->id,
                        "got_feedback" => 0
                    ])
                    ->orderBy(["debate.round_id" => SORT_DESC])
                    ->all();

                foreach ($aips as $aip) {

                    /** @Todo Do better then that shit */
                    if (!$aip->panel || !$aip->panel->debate || !$aip->panel->debate->round || $aip->panel->debate->round->displayed != 1) continue;

                    if ($aip->function == Panel::FUNCTION_CHAIR) {
                        $type = Feedback::FROM_CHAIR;
                    } else {
                        $type = Feedback::FROM_WING;
                    }

                    if ($aip->panel->debate instanceof Debate) //Keep in for preset panel, they dont have a debate yet
                        $feedbackDebates[] = ["type" => $type, "debate" => $aip->panel->debate, "ref" => $judge->id];
                }
            }

            /*
            $dependency = new DbDependency([
                "sql" => "SELECT count(*) FROM feedback",
            ]);
            Yii::$app->cache->set($cache_key, $feedbackDebates, 120, $dependency);
            */
        }

        return $feedbackDebates;
    }

    public function cacheKey($key = null)
    {
        return $this->url_slug . (($key != null) ? ("_" . $key) : "");
    }


    public function updateAccessToken($random_string_length = 255)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        for ($i = 0; $i < $random_string_length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->accessToken = $string;
        $this->save();
    }
}
