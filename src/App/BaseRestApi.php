<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Base rest api for services
 *
 * Created by PhpStorm.
 * User: johndiaz
 * Date: 30/03/16
 * Time: 3:08 PM
 *
 * @author John L. Diaz, support@secaudit.co
 */
namespace App;

use \Silex\Application;


class BaseRestApi
{

    /**
     * @var Application
     */
    public $api;

    /**
     * DB Instance
     *
     * @var mixed
     */
    protected $db;
    
    /**
     * User information, obtained from token authentication
     * 
     * @var array
     */
    public $userInfo;

    /**
     * @var array
     */
    private $_lang = array();

    /**
     * Find and return an instantiated service, if no .service is found in the string
     * the suffix will be added.
     *
     * @param string $serviceName
     * @return mixed
     * @throws \Exception
     */
    public function getService($serviceName)
    {

        if(strlen($serviceName) === 0) {
            throw new \Exception("ERROR: No service name specified in for find operation");
        }

        if(!isset($this->api["$serviceName.service"])) {
            return Route::service($this->api, $serviceName);
        }

        return $this->api["$serviceName.service"];

    }

    /**
     * Get an specified controller by string name, this method loads the full micro service specified
     * into the main $api object and returns the initialized controller, this is useful for service
     * lazy load, once a controller has been initialized, it will be available throughout all the application,
     * it will load only once.
     *
     * @param string $controllerName
     * @return mixed
     * @throws \Exception
     */
    public function getController($controllerName)
    {
        $service = str_replace(".controller", "", $controllerName);

        if(strlen($controllerName) === 0) {
            throw new \Exception("ERROR: No controller name specified in for find operation");
        }

        if(!preg_match("/\.controller$/", $controllerName)) {
            $controllerName = "$controllerName.controller";
        }

        if(!isset($this->api[$controllerName])) {
            $this->getService($service);
        }

        return $this->api[$controllerName];

    }

    /**
     * BaseRestApi constructor.
     *
     * @param Application $api
     */
    public function __construct(Application $api)
    {
        $this->api = $api;
        $this->db = $this->api[ "db" ];
    }

    /**
     * Parses the string to the current user language
     *
     * @param string $string
     * @return string
     */
    public function lang($string)
    {
        if (isset( $this->_lang[$string]) ) {
            return $this->_lang[$string];
        }
        
        $language = $this->getController('language');
        $this->_lang[$string] = $language->findPhrase($string);
        
        return $this->_lang[$string];
    }

    /**
     * Internationalization static method, this uses $this->lang which loads Language service.
     * Language service tries to fetch the phrases from the DB and if not found it tries to
     * translate them using Yandex translation service.
     *
     * @param string $string
     * @param Application\ $api
     * @return string
     */
    public static function i18n($string, $api)
    {
        $baseRest = new self($api);

        return $baseRest->lang($string);

    }
}
