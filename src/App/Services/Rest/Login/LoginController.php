<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Login controller is in care of login and authentication stuff
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Login;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpFoundation\Request;

class LoginController extends \App\Controller
{
    /**
     * @var string
     */
    public static $AUTH_ERROR = "ERROR: Authentication error";

    /**
     * Array of endpoints to expose in the api.
     * WARNING: This endpoints are not protected, don't put here anything you can
     * regret of.
     *
     * @return array
     */
    public function endpoints()
    {
        $endpoint = $this->getControllerName();
        
        return array(
            'get'  => array(
                $endpoint . "/logout" => $endpoint . ".controller:logout"
            ),
            'post' => array(
                $endpoint => $endpoint . ".controller:login",
                $endpoint."/language" => $endpoint . ".controller:getPhrases"
            )
        );
        
    }
    
    /**
     * Two factor authentication, to login must call this method twice, the first request must be without
     * a password, only 'email' param and 'x-requested-with' header are required.
     * The first request will return a random salt string that must be concatenated with the real password and
     * encrypted using SHA1 algorithm in the client side.
     * side
     * The second request must contain 'email', 'x-requested-with' header, 'pass', the pass parameter must be the password
     * string
     * concatenated
     * with the provided salt and encrypted using SHA1 algorithm.
     *
     * All generated tokens have a 24 hour TTL, after that, client must resubmit login credentials to create a new
     * token.
     *
     * This two factor authentication is not willing to prevent man in the middle attacks, but it can avoid these
     * attacks steal users passwords.
     *
     * The same error message will be responded on error, indifferently of finding or not matching records for
     * 'email, x-requested-with', 'email,pass', to prevent username harvesting from attackers.
     *
     * WARNING!: man in the middle can still intercept valid tokens, that will give them access to API for the validity
     * period.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AccessDeniedHttpException
     */
    public function login(Request $request)
    {
        $attributes = $this->getDataFromRequest($request);

        $attributes['x-requested-with'] = $request->headers->get('X-Requested-With');
        
        if (!isset($attributes['email']) && !isset($attributes['x-requested-with']) || !isset($attributes['pass'])) {
            throw new AccessDeniedHttpException(self::$AUTH_ERROR);
        }

        return $this->response(array('token' => $this->validateLogin($attributes, $request)));
    }

    /**
     * Validates login credentials
     *
     * @param array                                     $attributes
     * @param Request $request
     * @return string
     * @throws AccessDeniedHttpException
     */
    private function validateLogin($attributes, Request $request)
    {

        $user = $this->service->getFullUser($attributes);
        
        if (!$user) {
            throw new AccessDeniedHttpException(self::$AUTH_ERROR);
        }

        if (sha1($attributes['x-requested-with'] . $user['password']) !== $attributes['pass']) {
            throw new AccessDeniedHttpException(self::$AUTH_ERROR);
        }

        $token = sha1(mt_rand(1, 10) . mt_rand(300, 1000));
        $ip    = $this->getIp($request);

        if (!$this->service->addToken($user['id'], $token, $ip)) {
            throw new AccessDeniedHttpException(self::$AUTH_ERROR);
        }
        
        return $token;
        
    }

    /**
     * Get user public IP address
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getIp(Request $request)
    {
        $ip = $request->getClientIp();

        if ($ip === '127.0.0.1') {
            $ip = gethostbyname(gethostname());
        }

        return $ip;
    }

    /**
     * Validates a token string
     *
     * @param string                                    $token
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function validateToken($token, Request $request)
    {
        $ip = $this->getIp($request);
        $user = $this->service->validateToken($token, $ip);

        if (!$user) {
            return false;
        }

        $this->api['user.info'] = $this->getService('users')->getByUserId($user['user_id']);
        $this->api['user.lang'] = $this->api['user.info']['lang'];

        return $this->api['user.info'];
    }

    /**
     * Get language phrases stored in DB or from Yandex service
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getPhrases(Request $request)
    {
        $phrases = $this->getDataFromRequest($request);
        $lang = $this->getController('language');

        return $this->response($lang->getPhrasesByList($phrases));
    }
}
