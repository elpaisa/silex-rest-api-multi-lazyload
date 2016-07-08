<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Users controller
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */
namespace App\Services\Rest\Users;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UsersController extends \App\Controller
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
                $endpoint . "/getUsersList" => $endpoint . ".controller:getUsersList",
                $endpoint . "/roles"        => $endpoint . ".controller:getRoles",
                $endpoint . "/by-role/{id}" => $endpoint . ".controller:getUsersByRole",
                $endpoint . "/{id}"         => $endpoint . ".controller:getByUserId"
            ),
            'post'   => array(
                $endpoint . "/checkUser" => $endpoint . ".controller:checkUser",
                $endpoint . "/saveUser"  => $endpoint . ".controller:saveUser",
            ),
            'put'    => array(
                $endpoint . "/{id}" => $endpoint . ".controller:updateUser"
            ),
            'delete' => array(
                $endpoint . "/{id}" => $endpoint . ".controller:delete"
            )
        );

    }

    /**
     * Gets a user by its unique ID
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getByUserId($id)
    {
        $user = $this->service->getByUserId((int)$id);

        if (!$user) {
            throw new NotFoundHttpException($this->lang('user_not_found'));
        }

        return new JsonResponse($user);
    }

    /**
     * Gets the users list from DB
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUsersList()
    {
        return new JsonResponse($this->service->getUsersList());
    }

    /**
     * Checks if a user already exists
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function checkUser(Request $request)
    {
        $attributes = $this->getDataFromRequest($request);

        if (!$attributes['username']) {
            throw new BadRequestHttpException($this->lang('no_user_specified'));
        }

        return new JsonResponse(array('exists' => $this->service->checkUser($attributes['username'])));
    }

    /**
     * Get users roles
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getRoles()
    {
        return new JsonResponse($this->service->getRoles());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUsersByRole($id)
    {
        return new JsonResponse($this->service->getUsersByRole($id));
    }

    /**
     * Save a user
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function saveUser(Request $request)
    {
        $attributes = $this->getDataFromRequest($request);

        if (!$attributes['username'] || !$attributes['company_id'] || !$attributes['full_name'] ||
            !$attributes['password'] || !$attributes['role']
        ) {
            throw new BadRequestHttpException($this->lang('please_fill_all_the_required_fields'));
        }
        $companies = $this->getService('companies');
        $company = $companies->getByPK((int)$attributes['company_id']);
        $attributes['public_key'] = $company['public_key'];
        $attributes['password'] = sha1($attributes['password']);
        $attributes['date_created'] = date('y-m-d H:i:s', time());

        $id = $this->service->save($attributes);

        unset($attributes['password'], $attributes['repeat_password']);

        if (!$id) {
            throw new BadRequestHttpException($this->lang('there_was_an_error_trying_to_save_user'));
        }

        return new JsonResponse($this->service->getByUserId($id));
    }

    /**
     * Update user
     * 
     * @param  int                                      $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateUser($id, Request $request)
    {

        $attributes = $this->getDataFromRequest($request);

        unset($attributes['role_name'], $attributes['repeat_password'], $attributes['company_name']);

        if (isset($attributes['password'])) {
            $attributes['password'] = sha1($attributes['password']);
        }

        return new JsonResponse($this->service->updateUser($id, $attributes));
    }

}
