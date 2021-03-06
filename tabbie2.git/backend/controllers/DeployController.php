<?php
namespace backend\controllers;

use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\LoginForm;
use yii\filters\VerbFilter;

/**
 * Site controller
 */
class DeployController extends Controller
{

    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionGitPushHook()
    {
        // set the exception handler to get error messages
        set_exception_handler(function ($e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
            die();
        });

        //check if brunch master
        $master_ref = "refs/heads/master";

        try {

            $body = Yii::$app->request->getBodyParam("payload");
            $payload = json_decode($body, true);

            if ($payload === null) {
                header('500 Internal Server Error');
                echo "Payload was null\n";
                echo "Received raw body was: \n";
                echo Yii::$app->request->getRawBody();
                die();
            }

            if (!isset($payload["ref"]) || $payload["ref"] != $master_ref) {
                header('HTTP/1.1 200 OK - But no Master Push');
                echo "Not a push to " . $master_ref . "\n";
                echo "Instead push to " . $payload["ref"] . " received";
                die();
            }

            // set the secret key
            $hookSecret = Yii::$app->params["hookSecret"];

            // check if we have a signature
            if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE']))
                throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
            else if (!extension_loaded('hash'))
                throw new \Exception("Missing 'hash' extension to check the secret code validity.");

            // check if the algo is supported
            list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + ['', ''];
            if (!in_array($algo, hash_algos(), true))
                throw new \Exception("Hash algorithm '$algo' is not supported.");

            // check if the key is valid
            $rawPost = file_get_contents('php://input');
            if ($hash !== hash_hmac($algo, $rawPost, $hookSecret))
                throw new \Exception('Hook secret does not match.');

            /** @var string $git_root BasePath to the Root git directory */
            $git_root = Yii::$app->basePath . "/../../";

            $out[] = "<h3>=== Git Pulls ===</h3>";
            // execute
            exec("cd $git_root && git pull", $out);

            //make migrations
            $out[] = "<h3>=== Migrate ===</h3>";
            exec("php $git_root/tabbie2.git/yii migrate/up --interactive=0", $out);

            //update translation
            $translate = [];
            $out[] = "<h3>=== Translations ===</h3>";
            exec("php $git_root/tabbie2.git/yii message/extract $git_root/tabbie2.git/common/messages/config.php", $translate);
            //Only last 2 lines
            if (count($translate) > 2) {
                $last = count($translate) - 1;
                $out[] = $translate[$last - 1];
                $out[] = $translate[$last];
            }

            //update API Documentation
            $out[] = "<h3>=== Create API Documentation ===</h3>";
            exec("cd $git_root && rm -rf ./tabbie2.git/api/web/doc/ && php ./tabbie2.git/yii api/index ./tabbie2.git/api/controllers,./tabbie2.git/api/models/ ./tabbie2.git/api/web/doc --pageTitle='Tabbie2 API Documentation'", $out);

            //Flush Caches
            $out[] = "<h3>=== Flush Cache ===</h3>";
            exec("php $git_root/tabbie2.git/yii cache/flush-schema --interactive=0", $out);
            exec("php $git_root/tabbie2.git/yii cache/flush-all --interactive=0", $out);

            //KILL files
            exec("rm -rf $git_root/tabbie2.git/frontend/runtime/cache/*", $out);
            exec("rm -rf $git_root/tabbie2.git/backend/runtime/cache/*", $out);
            exec("rm -rf $git_root/tabbie2.git/api/runtime/cache/*", $out);
            exec("rm -rf $git_root/tabbie2.git/console/runtime/cache/*", $out);

            //output
            print_r($out);

            $html = "<h2>Git Pull Report $out[1]</h2>\n";
            $html .= implode("<br>\n", $out);

            Yii::$app->mailer->compose()
                ->setFrom(['git-report@tabbie.org' => "Git Pull Report"])
                ->setTo(Yii::$app->params["supportEmail"])
                ->setSubject($out[1] . " " . $out[2])
                ->setHtmlBody($html)
                ->send();

        } catch (Exception $ex) {
            echo "<h1>Exception</h1>";
            print_r($ex);
        }
    }
}
