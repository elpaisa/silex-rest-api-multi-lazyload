<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Language micro service
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */
namespace App\Services\Rest\Language;

class LanguageService extends \App\BaseService
{

    public $serviceModel = 'language';
    
    /**
     * @param string $varName
     * @return string
     */
    public function findByVarName($varName, $lang)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('l.value')
           ->from($this->modelName, 'l')
           ->where("l.var_name = :var_name AND l.lang_code = :lang")
           ->setParameter('var_name', $varName)
           ->setParameter('lang', $lang);
    
        return $qb->execute()->fetch();
    }

    /**
     *
     * @param string $varName
     * @param string $string
     * @param string $lang
     * @return int
     */
    public function addPhrase($varName, $string, $lang)
    {
        return $this->db->insert($this->serviceModel, array('var_name'=> $varName, 'lang_code' => $lang, 'value'=> $string));
    }


}
