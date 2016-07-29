<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * This is the global controller to use when trying to extend the application, every controller
 * must extend this class, also if you are declaring a controllername other than the SQL table name,
 * must specify the service name
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller extends \App\BaseRestApi
{
    /**
     * Controller linked service
     *
     * @var string $this->endpointName
     */
    public $service;

    /**
     * Url endpoint name is equivalent to the controller name
     *
     * @var string
     */
    public $endpointName;

    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    private $jsonResponse;

    /**
     * Controller constructor, $service param must use naming conventions provided in the config file
     *
     * @param Application $api
     * @param string      $service Service name used to describe the micro service in the application
     */
    public function __construct($api, $service)
    {
        $this->api = $api;
        parent::__construct($api);
        $this->service = $service;
    }

    /**
     * Formats the given class name to an api endpoint name.
     * Composite class names with CamelCase are mapped to camel-case
     *
     * @return mixed|string
     */
    public function getControllerName()
    {

        $class = new \ReflectionClass(get_class($this));

        $shortName = trim(str_replace("Controller", "",$class->getShortName()));
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1-$2', $shortName);

        $this->endpointName = strtolower($shortName);

        return $this->endpointName;
    }

    /**
     * Must return an array with api calls to register
     *
     * @return array
     */
    abstract public function endpoints();

    /**
     * Gets all records for the current service
     *
     * @return JsonResponse
     */
    public function getAll()
    {
        return new JsonResponse($this->service->getAll());
    }

    /**
     * Saves a record into the current service
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        $attributes = $this->getDataFromRequest($request);

        return new JsonResponse(array("id" => $this->service->save($attributes)));

    }

    /**
     * Basic update method for all controllers, this method can be override from the
     * child controller itself
     *
     * @param int                                          $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update($id, Request $request)
    {
        $attributes = $this->getDataFromRequest($request);
        $this->service->update($id, $attributes);

        return new JsonResponse($attributes);

    }

    /**
     * Basic delete method for all controllers, this method can be override from the
     * child controller itself
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function delete($id)
    {

        return new JsonResponse($this->service->delete($id));

    }

    /**
     * Get request params, gets an array of the php:input, the received request must have the service name as value
     * i.e for LoginService: {login: ["username"=>"john_doe", "pass"=>"admin"]}, get request will take as param
     * root login.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function getDataFromRequest(Request $request)
    {
        return $request->request->get($this->endpointName);
    }

    /**
     * Instantiates JsonResponse
     */
    private function setResponse()
    {
        if (!$this->jsonResponse) {
            $this->jsonResponse = new JsonResponse();
            $this->jsonResponse->setEncodingOptions(JSON_NUMERIC_CHECK);
        }
    }

    /**
     * @param array $data
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function response($data)
    {
        $this->setResponse();
        $this->jsonResponse->setData($data);

        return $this->jsonResponse;
    }

}
