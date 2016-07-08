<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Authorization, checks the user permissions against the global application, it does not have in account granular
 * permissions to specific services and controllers.
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
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class Authorization implements ServiceProviderInterface
{

    /**
     * @var Application
     */
    private $api;

    /**
     * Default authorization roles for the API, '*' matches all the permissions
     *
     * @var array
     */
    public static $DEFAULT_ROLES = array(1 => '*', 2 => array('GET', 'POST'), 3 => array('GET'));

    /**
     * @var array
     */
    public static $EXEMPT_SERVICES = array('login');

    /**
     *
     * @param \Silex\Application $app
     * @return bool
     */
    public function register(Application $app)
    {
    }
    
    /**
     * @param \Silex\Application $app
     */
    public function boot(Application $app)
    {
        $this->api                          = $app;
        $this->api['authorization.service'] = $this;
    }
    
    /**
     * Checks permissions for current user to the api endpoint
     * 
     * @param Request $request
     * @return bool
     * @throws AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     */
    public function checkPermissions(Request $request)
    {
        $path     = Route::getservicenamefrompath($this->api, $request);
        $isExempt = in_array($path, self::$EXEMPT_SERVICES);
        $token    = $request->headers->get('x-token');

        if (!$token && !$isExempt) {
            throw new AccessDeniedHttpException(BaseRestApi::i18n('authorization_required_to_access_this_resource', $this->api));
        }

        if ($isExempt) {
            return true;
        }
        
        Route::service($this->api, 'login');

        if (!$this->api['login.controller']->validatetoken($token, $request)) {
            throw new AccessDeniedHttpException(BaseRestApi::i18n('token_invalid', $this->api));
        }

    }

}