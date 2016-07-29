<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Base class for common methods and attributes to be used across the API controllers and services
 *
 * Created by PhpStorm.
 * User: johndiaz
 * Date: 30/03/16
 * Time: 3:08 PM
 *
 * @author John L. Diaz, support@secaudit.co
 */
namespace App;


class BaseService extends BaseRestApi
{

    /**
     * This is the equivalent table Name in the database, make sure all database tables
     * use `id` as primary key column name, thats a must standard used by this api
     *
     * @var string
     */
    public $modelName;

    /**
     * BaseService constructor.
     *
     * @param Application $api
     */
    public function __construct($api)
    {
        parent::__construct($api);

        $this->getServiceName();
    }

    /**
     * Standard query results
     *
     * @param array    $results
     * @param int      $totalRows
     * @param int|bool $from
     * @param int|bool $to
     *
     * @return array
     */
    public function results($results, $totalRows, $from = false, $to = false)
    {
        return array(
            'results'    => $results,
            'from'       => $from,
            'to'         => $to,
            'row_count'  => count($results),
            'total_rows' => $totalRows
        );
    }

    /**
     * Formats the given class name to an api endpoint name.
     *
     * @return string
     */
    public function getServiceName()
    {

        $class = new \ReflectionClass(get_class($this));

        if (isset($this->serviceModel)) {
            $this->modelName = $this->serviceModel;
        } else {
            $modelName       = $class->getShortName();
            $this->modelName = str_replace("service", "", strtolower($modelName));
        }


        return $this->modelName;
    }

    /**
     * Gets a record by primary key, by standard all database tables must have "id" as name for the primary key
     * field
     * 
     * @param int $id
     * @return array
     */
    public function getByPK($id)
    {
        $qb = $this->db->createQueryBuilder();

        $qb->select('d.*')
           ->from($this->modelName, 'd')
           ->where('d.id = :id')
           ->setParameter('id', (int)$id);


        return $qb->execute()->fetch();
    }

    /**
     * Fetches a raw query by the modelName string, default limit is 1000, for pagination use fetchAll method
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM $this->modelName LIMIT 1000");
    }

    /**
     * This parses the SELECT col1, col2, FROM and converts it to a SELECT count(*)
     * then executes it to retrieve the full row count for the query
     *
     * @param string $query
     * @return int
     * @throws \App\LogicException
     */
    public function getCountByQuery($query)
    {
        $query = str_replace("\n", " ", $query);

        preg_match("/SELECT(.*?)FROM/i", $query, $columns);

        if (!isset($columns[1])) {
            throw new LogicException($this->lang('malformed_query'));
        }

        $query = str_replace($columns[1], " count(*) ", $query);

        $sth = $this->db->prepare($query);
        $sth->execute();

        return (int)$sth->fetchColumn(0);

    }

    /**
     * Fetches all records by a given query, it uses pagination offsets, provided by the api,
     * the default limit must be specified in the config file, returns an array with results array, 
     * row count, query start offset, and end offset
     * 
     * @return array [array, int, int, int]
     */
    public function fetchAll($query)
    {
        $offset     = (int)$this->api['limit_offset'];
        $maxResults = (int)$this->api['global.config']['max_results'];
        $rowCount   = $this->getCountByQuery($query);
        $results    = $this->db->fetchAll("$query LIMIT $offset, $maxResults");

        return $this->results($results, $rowCount, $offset, ($offset + $maxResults));
    }

    /**
     * Save a standard model into the specified table, this uses the standard Doctrine Database abstraction layer
     * library, for more information on save method, please refer to:
     * http://www.doctrine-project.org/projects/dbal.html
     *
     * @param string $modelName table name to save
     * @param array $data data must have an associative array of column names => value
     * @return int
     * @throws \Exception
     */
    public function saveGeneric($modelName, $data)
    {
        $this->db->insert($modelName, $data);
        $id = $this->db->lastInsertId();

        if (!$id) {
            throw new \Exception($this->lang('unable_to_save_record'));
        }

        return $id;
    }


    /**
     * Save a standard model, this takes the current service model name `database table name`
     * and saves its values provided in the array $data, this uses the standard Doctrine Database abstraction layer
     * library, for more information on save method, please refer to:
     * http://www.doctrine-project.org/projects/dbal.html
     *
     * @param array $data data must have an associative array of column names => value
     * @return int
     * @throws \Exception
     */
    public function save($data)
    {
        $this->db->insert($this->modelName, $data);
        $id = $this->db->lastInsertId();

        if (!$id) {
            throw new \Exception($this->lang('unable_to_save_record'));
        }

        return $id;
    }

    /**
     * Standard update method, this uses the standard Doctrine Database abstraction layer
     * library, for more information on save method, please refer to:
     * http://www.doctrine-project.org/projects/dbal.html
     * 
     * @param int   $id
     * @param array $attributes
     * @return mixed
     */
    public function update($id, $attributes)
    {
        return $this->db->update($this->modelName, $attributes, ['id' => $id]);
    }

    /**
     * Standard delete method, this uses the standard Doctrine Database abstraction layer
     * library, for more information on save method, please refer to:
     * http://www.doctrine-project.org/projects/dbal.html
     * 
     * @param int $id
     * @param string|bool $model base table
     * @return mixed
     */
    public function delete($id, $model = false)
    {
        if($model) {
            return $this->db->delete($model, array("id" => $id));
        }

        return $this->db->delete($this->modelName, array("id" => $id));
    }


}
