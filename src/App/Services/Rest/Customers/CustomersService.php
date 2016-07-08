<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Customers micro service
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */

namespace App\Services\Rest\Customers;

class CustomersService extends \App\BaseService
{

    /**
     * Table name for the service
     *
     * @var string
     */
    public $serviceModel = 'customers';

    /**
     * Get list of generator customers
     *
     * @return array
     */
    public function getCustomersList()
    {

        return $this->fetchAll(
            "
              SELECT id, name, tin
              FROM $this->modelName
               ORDER BY name ");

    }

    /**
     * Retrieves a customer by ID
     *
     * @param int $id customer id
     * @return mixed
     */
    public function getCustomerById($id)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('c.*, p.name as parent_name')
            ->from($this->modelName, 'c')
            ->leftJoin('c', 'customers', 'p', 'p.id = c.parent_customer_id')
            ->where('c.id = :id')
            ->setParameter('id', (int)$id);

        return $qb->execute()->fetch();

    }

    /**
     * Retrieve customer children by parent_customer_id
     *
     * @param $id customer id
     * @return array
     */
    public function getCustomerChildren($id)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('c.id, c.name')
            ->from($this->modelName, 'c')
            ->where('c.parent_customer_id = :id')
            ->setParameter('id', (int)$id);

        $children = $qb->execute()->fetch();

        return $children ? $children : array();

    }

    /**
     * @param array $customer
     * @return array
     * @throws \Exception
     */
    public function saveCustomer($customer)
    {
        if ($this->customerExists($customer)) {
            throw new \Exception($this->lang('customer_already_exists'));
        }

        $customer[ 'date_created' ] = date('y-m-d H:i:s', time());

        return $this->getCustomerById($this->save($customer));
    }

    /**
     * Update customer
     *
     * @param int   $id
     * @param array $attributes
     * @return mixed
     * @throws \Exception
     */
    public function updateCustomer($id, $attributes)
    {
        $customer = $attributes;

        $update = $this->db->update($this->modelName, $attributes, ['id' => $id]);

        if (!$update) {
            throw new \Exception($this->lang('customer_not_modified'));
        }

        return $customer;
    }

    /**
     * Checks if a customer already exists by name and TIN (tax identification number)
     *
     * @param array $attributes
     * @return array
     */
    public function customerExists($attributes)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('c.id, c.name, c.tin')
            ->from($this->modelName, 'c')
            ->where('c.name = :name OR c.tin = :tin')
            ->setParameter('name', $attributes[ 'name' ])
            ->setParameter('tin', (int)$attributes[ 'tin' ]);

        return $qb->execute()->fetchAll();
    }

    /**
     * Searches a customer by name, tin, contact_name or email
     *
     * @param string $term
     * @return array
     */
    public function searchCustomers($term)
    {
        $qb         = $this->db->createQueryBuilder();
        $offset     = (int)$this->api['limit_offset'];
        $maxResults = (int)$this->api['global.config']['max_results'];

        $qb->select('c.id, c.name, c.tin')
            ->from($this->modelName, 'c')
            ->where('c.name LIKE :term OR c.tin LIKE :term OR c.contact_name LIKE :term OR c.email LIKE :term')
            ->setParameter('term', "%$term%")
            ->setFirstResult($offset)
            ->setMaxResults($maxResults);

        $results = $qb->execute()->fetchAll();

        return $this->results($results, 0, $offset, ($offset + $maxResults));
    }

}
