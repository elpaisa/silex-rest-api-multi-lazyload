<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Login micro service
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Login;

class LoginService extends \App\BaseService
{
    
    /**
     * @var string
     */
    public $serviceModel = 'users';

    /**
     * Get user data
     *
     * @param $attributes
     * @return mixed
     */
    public function getFullUser($attributes)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('*')
           ->from($this->modelName, 'u')
           ->where("u.username = :username AND u.public_key = :public_key")
           ->setParameter('username', $attributes['email'])
           ->setParameter('public_key', $attributes['x-requested-with']);

        return $qb->execute()->fetch();

    }
    
    /**
     * Saves a token string into DB
     *
     * @param string $userId
     * @param string $token
     * @param string $ipAddress
     * @return int
     */
    public function addToken($userId, $token, $ipAddress)
    {
        $this->db->insert('tokens', array('user_id'=>$userId, 'token'=>$token, 'remote_ip'=> $ipAddress,
                                          'date_created' => date('y-m-d H:i:s', time())));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Validates a token against DB
     *
     * @param string $token
     * @param string $ipAddress
     * @return array|bool
     */
    public function validateToken($token, $ipAddress)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('t.user_id')
           ->from('tokens', 't')
           ->where("t.token = :token AND remote_ip = :ip AND t.date_created > (NOW() - INTERVAL 10 DAY)")
           ->setParameter('token', $token)
           ->setParameter('ip', $ipAddress);

        return $qb->execute()->fetch();
    }
}
