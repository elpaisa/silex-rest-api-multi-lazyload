<?php

namespace App\Services\Rest\Countries;

class CountriesService extends \App\BaseService
{

    /**
     * Table name for the service
     *
     * @var string
     */
    public $serviceModel = 'countries';

    /**
     * Retrieves states by country code
     *
     * @param int $code Country CODE
     * @return array
     */
    public function getStatesByCountryCode($code)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('s.*')
           ->from('states', 's')
           ->leftJoin('s', 'countries', 'c', 'c.id = s.country_id')
           ->where('c.code = :code')
           ->setParameter('code', $code);

        return $qb->execute()->fetchAll();

    }

}
