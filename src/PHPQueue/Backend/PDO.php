<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;

class PDO extends Base
{
    private $connection_string;
    private $db_user;
    private $db_password;
    private $db_table;
    private $pdo_options = array();

    public function __construct($options=array())
    {
        parent::__construct();
        if (!empty($options['connection_string'])) {
            $this->connection_string = $options['connection_string'];
        }
        if (!empty($options['db_user'])) {
            $this->db_user = $options['db_user'];
        }
        if (!empty($options['db_password'])) {
            $this->db_password = $options['db_password'];
        }
        if (!empty($options['db_table'])) {
            $this->db_table = $options['db_table'];
        }
        if (!empty($options['pdo_options']) && is_array($options['pdo_options'])) {
            $this->pdo_options = array_merge($this->pdo_options, $options['pdo_options']);
        }
    }

    public function setTable($table_name=null)
    {
        if (empty($table_name)) {
            throw new BackendException('Invalid table name.');
        }
        $this->db_table = $table_name;

        return true;
    }

    public function connect()
    {
        $this->connection = new \PDO($this->connection_string, $this->db_user, $this->db_password, $this->pdo_options);
    }

    public function add($data = null)
    {
        if (empty($data)) {
            throw new BackendException('No data.');
        }
        $sql = sprintf('INSERT INTO `%s` (`data`) VALUES (?)', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);
        $sth->bindParam(1, json_encode($data), \PDO::PARAM_STR);
        $sth->execute();
        $this->last_job_id = $this->getConnection()->lastInsertId();

        return true;
    }

    public function get($id=null)
    {
        if (empty($id)) {
            throw new BackendException('No ID.');
        }
        $sql = sprintf('SELECT `data` FROM `%s` WHERE `id` = ?', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);
        $sth->bindParam(1, $id, \PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result)) {
            return json_decode($result['data'], true);
        }

        return null;
    }

    public function clear($id = null)
    {
        if (empty($id)) {
            throw new BackendException('No ID.');
        }
        try {
            $sql = sprintf('DELETE FROM `%s` WHERE `id` = ?', $this->db_table);
            $sth = $this->getConnection()->prepare($sql);
            $sth->bindParam(1, $id, \PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $ex) {
            throw new BackendException('Invalid ID.');
        }

        return true;
    }

    public function clearAll()
    {
        if (empty($this->db_table)) {
            throw new BackendException('Invalid table name.');
        }
        $sql = sprintf('TRUNCATE `%s`', $this->db_table);
        $this->getConnection()->exec($sql);

        return true;
    }

    public function createTable($table_name)
    {
        if (empty($table_name)) {
            throw new BackendException('Invalid table name.');
        }
        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
                    `id` mediumint(20) NOT NULL AUTO_INCREMENT,
                    `data` mediumtext NULL DEFAULT '',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;", $table_name);
        $this->getConnection()->exec($sql);

        return true;
    }

    public function deleteTable($table_name)
    {
        if (empty($table_name)) {
            throw new BackendException('Invalid table name.');
        }
        $sql = sprintf("DROP TABLE IF EXISTS `%s` ;", $table_name);
        $this->getConnection()->exec($sql);

        return true;
    }

    /**
     * @return \PDO
     */
    public function getConnection()
    {
        return parent::getConnection();
    }
}
