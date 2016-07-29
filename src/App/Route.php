<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Mounts required services "Classes", if they are available.
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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Route
{    
    
    /**
     * Mount service if it exists
     *
     * @param \Silex\Application                        $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public static function mount(Application $app, Request $request)
    {
        $serviceName = self::getServiceNameFromPath($app, $request);

        return self::service($app, $serviceName);
    }
    
    /**
     * Builds and instantiates all micro service
     * 
     * @param \Silex\Application $app
     * @param string             $serviceName
     * @return mixed
     */
    public static function service(Application $app, $serviceName)
    {
        if(!preg_match("/\.service$/", $serviceName)) {
            $serviceName = "$serviceName.service";
        }
        
        if (isset($app[$serviceName])) {
            return $app[$serviceName];
        }
        
        $serviceName = str_replace(".service", "", $serviceName);
        
        if (!isset($app['route_mapping'][$serviceName])) {
            #Just don't do anything, app will throw a 404
            return;
        }
    
        $routing = new \App\Routing($app, $serviceName, $app['route_mapping'][$serviceName]);
        
        return $routing->register();
    }

    /**
     * Retrieves the service required by request path info
     *
     * @param Application $app
     * @param Request     $request
     * @return mixed
     * @throws ConflictHttpException
     */
    public static function getServiceNameFromPath(Application $app, Request $request)
    {
        $pathInfo = explode("/", trim($request->getPathInfo(), "/"));
        $key = array_search($app['api.version'], $pathInfo);

        if (!$key || !isset($pathInfo[$key + 1])) {
            throw new ConflictHttpException(BaseRestApi::i18n('invalid_path_received', $app));
        }

        return $pathInfo[$key + 1];
    }

}
