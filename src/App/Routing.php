<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * General routing class
 *
 * Created by PhpStorm.
 * User: johndiaz
 * Date: 30/03/16
 * Time: 3:08 PM
 *
 * @author John L. Diaz, support@secaudit.co
 */
namespace App;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Routing
{
    /**
     * @var Application
     */
    private $api;

    /**
     * Routing constructor.
     *
     * @param Application $app
     * @param bool        $service
     * @param string      $mappedClass
     */
    public function __construct(Application $app, $service, $mappedClass)
    {
        $this->api         = $app;
        $this->mappedClass = $mappedClass;
        $this->service     = $service;
    }

    /**
     * @return object
     */
    private function getController()
    {

        $controller = "App\\Services\\" . $this->api['route_workspace'] . "\\" . $this->mappedClass . "\\" . $this->mappedClass . "Controller";
        $controller = new $controller($this->api, $this->api[$this->service . ".service"]);

        $this->api["$this->service.controller"] = $this->api->share(function () use ($controller) {
            return $controller;
        });


        return $controller;

    }

    /**
     * @return mixed
     */
    public function registerService()
    {
        $this->api[$this->service . ".service"] = $this->api->share(function () {

            $service = "App\\Services\\" . $this->api['route_workspace'] . "\\" . $this->mappedClass . "\\" . $this->mappedClass . "Service";

            return new $service($this->api);
        });

        return $this->api[$this->service . ".service"];
    }


    /**
     * Register the controller endpoints
     */
    public function register()
    {
        $service            = $this->registerService();
        $controller         = $this->getController();
        $endpoints          = $controller->endpoints();
        $controllersFactory = $this->api["controllers_factory"];

        foreach ($endpoints as $method => $endpoint) {
            self::registerEndpoints($this->api, $controllersFactory, $method, $endpoint);
        }

        $this->api->mount($this->api["api.endpoint"] . '/' . $this->api["api.version"], $controllersFactory);

        return $service;
    }

    /**
     * Register endpoints, add default param Offset for get Queries
     *
     * @param Application $api
     * @param array       $controllersFactory
     * @param string      $method
     * @param array       $endpoints
     */
    public static function registerEndpoints($api, &$controllersFactory, $method, array $endpoints)
    {
        foreach ($endpoints as $endpoint => $callback) {
            $controllersFactory->{$method}($endpoint, $callback)
                               ->assert('offset', '\d+')
                               ->value('offset', 0)
                               ->before(function (Request $request) use ($api) {

                                   $offset              = $request->attributes->get('offset');
                                   $api['limit_offset'] = $offset ? (int)$offset : 0;

                               });
        }
    }

}
