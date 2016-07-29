<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Customers controller, this exposes a series of endpoints for handling customer data
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Customers;


use Symfony\Component\HttpFoundation\Request;

class CustomersController extends \App\Controller
{

    /**
     * Array of endpoints to expose in the api
     *
     * @return array
     */
    public function endpoints()
    {
        $endpoint = $this->getControllerName();

        return array(
            'get'    => array(
                "$endpoint/get-customers-list/{offset}" => $endpoint . ".controller:getCustomersList",
                "$endpoint/{id}"                        => $endpoint . ".controller:getCustomerById",
                "$endpoint/search/{term}"               => $endpoint . ".controller:search",
            ),
            'post'   => array(
                $endpoint => $endpoint . ".controller:save"
            ),
            'put'    => array(
                "$endpoint/{id}" => $endpoint . ".controller:update"
            ),
            'delete' => array(
                "$endpoint/{id}" => $endpoint . ".controller:delete"
            )
        );

    }

    /**
     * Get customers list
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCustomersList()
    {
        return $this->response($this->service->getCustomersList());
    }

    /**
     * Gets customer details by customer Id
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getCustomerById($id)
    {
        return $this->response($this->service->getCustomerById($id));
    }

    /**
     * Saves a record into the current service
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {

        return $this->response($this->service->saveCustomer($this->getDataFromRequest($request)));
    }

    /**
     * Update customer
     *
     * @param int                                       $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function update($id, Request $request)
    {

        return $this->response(
            $this->service->updateCustomer($id, $this->getDataFromRequest($request))
        );

    }

    /**
     * Search in customers
     *
     * @param string $term
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function search($term)
    {
        return $this->response($this->service->searchCustomers($term));
    }

}
