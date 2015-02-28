<?php

namespace tests\codeception\common\unit\components;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use Codeception\Verify;
use common\components\TabAlgorithmus;
use Codeception\Util\Debug;
use common\models\Team;
use common\models\Venue;
use common\models\Adjudicator;

/**
 * Test StrictWUDCRules Class
 */
class StrictWUDCRulesTest extends DbTestCase {

    public $algo;

    public function setUp() {
        parent::setUp();
        $tournament = \common\models\Tournament::findByPk(1);
        $this->algo = $tournament->getTabAlgorithmInstance();
    }

    protected function tearDown() {
        parent::tearDown();
    }

    public function testSortAndRandomisationOfTeams() {
        $teams = \common\models\Team::findAll(["tournament_id" => 1]);
        $new_teams = $this->algo->sort_teams($teams);
        $new_teams = $this->algo->randomise_within_points($new_teams);

        expect("Preserve Amount", count($new_teams))->equals(count($teams));
        for ($i = 0; $i < count($new_teams); $i++) {
            /* @var $t Team */
            $t = $new_teams[$i];

            $this->assertInstanceOf(Team::className(), $t);
            if ($i > 0) {
                expect("Sort Order", $t->getPoints())->lessOrEquals($new_teams[$i - 1]->getPoints());
            }
        }
    }

    public function tesRunFullDraw() {
        $venues = Venue::findAll(["tournament_id" => 1]);
        $teams = Team::findAll(["tournament_id" => 1]);
        $adjudicators = Adjudicator::findAll(["tournament_id" => 1]);
        $draw = $this->algo->makeDraw($venues, $teams, $adjudicators);
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
            'team' => [
                'class' => \tests\codeception\common\fixtures\TeamFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/team.php',
            ],
            'venue' => [
                'class' => \tests\codeception\common\fixtures\VenueFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/venue.php',
            ],
            'adjudicator' => [
                'class' => \tests\codeception\common\fixtures\AdjudicatorFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/adjudicator.php',
            ],
            'tournament' => [
                'class' => \tests\codeception\common\fixtures\TournamentFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/tournament.php',
            ],
            'round' => [
                'class' => \tests\codeception\common\fixtures\RoundFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/round.php',
            ],
            'round' => [
                'class' => \tests\codeception\common\fixtures\RoundFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/round.php',
            ],
            'drawafterround' => [
                'class' => \tests\codeception\common\fixtures\DrawafterroundFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/drawafterround.php',
            ],
            'drawposition' => [
                'class' => \tests\codeception\common\fixtures\DrawPositionFixture::className(),
                'dataFile' => '@tests/codeception/common/fixtures/data/drawposition.php',
            ],
        ];
    }

}