<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Countries controller.
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Countries;


class CountriesController extends \App\Controller
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
                "$endpoint/states/{code}"     => $endpoint . ".controller:getStates",
            )
        );

    }

    /**
     * Get states list by country code
     *
     * @param string $code example: CO
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getStates($code)
    {
        return $this->response($this->service->getStatesByCountryCode($code));
    }

}
