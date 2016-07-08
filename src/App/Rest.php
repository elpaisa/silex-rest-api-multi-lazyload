<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Created by PhpStorm.
 * User: johndiaz
 * Date: 30/03/16
 * Time: 3:08 PM
 *
 * This class cannot be extended and its supposed to be the entry point of the API.
 * @author John L. Diaz, support@secaudit.co
 * @final
 */
namespace App;

use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;

final class Rest
{
    /**
     * @var Application
     */
    private $api;

    /**
     * @var array $globalConfig
     */
    public $globalConfig;
    
    /**
     * Rest constructor.
     *
     * @param string $environment
     */
    public function __construct($environment = 'prod')
    {
        $this->api = new Application();
        $this->loadConfig($environment);
        $this->registerApiHandler();
        $this->registerModules();
        
    }
    
    /**
     * @param string $environment possible values are dev, prod
     */
    public function loadConfig($environment)
    {
        $app = $this->api;

        require __DIR__ . "/../../config/$environment.php";

    }
    
    /**
     * Register api
     */
    public function registerApiHandler()
    {
        $this->api->before(function (Request $request) {

            if ($request->getMethod() === "OPTIONS") {
                $response = new Response();
                #CORS PREFLIGHT enabled
                #$response->headers->set("Access-Control-Allow-Origin", "*");
                $response->headers->set("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS");
                $response->headers->set("Access-Control-Allow-Headers", "Content-Type,X-Requested-With,x-token");
                
                $response->setStatusCode(200);
                
                return $response->send();
            }
            
        }, Application::EARLY_EVENT);
        
        $this->api->before(function (Request $request) {
            $this->api['authorization.service']->checkPermissions($request);
        });

        $this->api->after(function (Request $request, Response $response) {
            #CORS enabled
            $response->headers->set("Access-Control-Allow-Origin", "*");
            $response->headers->set("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS");
        });
        
        $this->api->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);

                $request->request->replace(is_array($data) ? $data : array());
            }
        });

    }
    
    /**
     * Register modules
     */
    public function registerModules()
    {
        $this->api->register(new Authorization());
        $this->api->register(new ServiceControllerServiceProvider());
        $this->api->register(new DoctrineServiceProvider(), $this->api['db.config']);
        $this->api->register(
            new HttpCacheServiceProvider(),
            array("http_cache.cache_dir" => ROOT_PATH . "/cache",)
        );

        $this->api->register(
            new MonologServiceProvider(), 
            array(
                "monolog.logfile" => ROOT_PATH . "/logs/" . 
                    Carbon::now(
                        $this->api['global.config']['timeZone']
                    )->format($this->api['global.config']['dateFormat']) . 
                    ".log",
                "monolog.level"   => $this->api["log.level"],
                "monolog.name"    => "application"
            )
        );
        
        $this->api->error(
            function (\Exception $e, $code) {
                $this->api['monolog']->addError($e->getMessage());
                $this->api['monolog']->addError($e->getTraceAsString());

                $response = array(
                    "statusCode" => $code,
                    "message" => $e->getMessage()
                );

                if ($this->api['debug']) {
                    $response['stacktrace'] = $e->getTraceAsString();
                }

                return new JsonResponse($response);
            }
        );

    }
    
    /**
     * Run the API
     *
     * @param string $configFile
     * @return mixed
     */
    public static function run($configFile = 'prod')
    {
        $rest = new self($configFile);
        
        #Lazy load of services
        \App\Route::mount($rest->api, Request::createFromGlobals());
        
        return $rest->api['http_cache']->run();
    }
}
