<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * User micro service
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */
namespace App\Services\Rest\Users;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UsersService extends \App\BaseService
{
    
    /**
     * @var string
     */
    public $serviceModel = 'users';


    /**
     * @param int $id
     * @return mixed
     */
    public function getByUserId($id)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('u.id, u.username, u.company_id, u.full_name, u.role, u.date_created, c.name as company_name, r
        .role_name, u.phone, u.lang')
           ->from($this->modelName, 'u')
           ->leftJoin('u', 'companies', 'c', 'c.id = u.company_id')
           ->leftJoin('u', 'user_roles', 'r', 'r.id = u.role')
           ->where("u.id = :id")
           ->setParameter('id', $id);

        return $qb->execute()->fetch();

    }

    /**
     * @return mixed
     */
    public function getUsersList()
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('u.id, u.username, r.role_name')
           ->from($this->modelName, 'u')
           ->leftJoin('u', 'user_roles', 'r', 'r.id = u.role');

        return $qb->execute()->fetchAll();
    }

    /**
     * @param int $roleId
     * @return array
     */
    public function getUsersByRole($roleId)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('u.id, u.username, u.full_name, r.role_name')
           ->from($this->modelName, 'u')
           ->leftJoin('u', 'user_roles', 'r', 'r.id = u.role')
           ->where("u.role = :id")
           ->setParameter('id', $roleId);

        return $qb->execute()->fetchAll();
    }
    
    /**
     * @param $userName
     * @return mixed
     */
    public function checkUser($userName)
    {
        $qb = $this->db->createQueryBuilder();
    
        $qb->select('u.id')
           ->from($this->modelName, 'u')
           ->where("u.username = :username")
           ->setParameter('username', $userName);
    
        return $qb->execute()->fetch();
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->db->fetchAll("SELECT id, role_name FROM user_roles");
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function updateUser($id, $attributes)
    {
        if (!$this->update($id, $attributes)) {
            throw new BadRequestHttpException($this->lang('user_not_modified'));
        }

        return $this->getByUserId($id);
    }

    /**
     * Searches a customer by username or full_name
     *
     * @param string $term
     * @return mixed
     */
    public function search($term)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('u.id, u.username')
           ->from($this->modelName, 'u')
           ->where('u.username LIKE :term OR u.full_name LIKE :term')
           ->setParameter('term', "%$term%");

        return $qb->execute()->fetchAll();
    }

}
