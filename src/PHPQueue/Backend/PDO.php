<?php
namespace PHPQueue\Backend;

use PHPQueue\Exception\BackendException;
use PHPQueue\Interfaces\AtomicReadBuffer;
use PHPQueue\Interfaces\FifoQueueStore;

class PDO
    extends Base
    implements AtomicReadBuffer,
        FifoQueueStore
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
        if (!empty($options['queue'])) {
            $this->db_table = $options['queue'];
        }
        if (!empty($options['db_table'])) {
            $this->db_table = $options['db_table'];
        }
        if (!empty($options['pdo_options']) && is_array($options['pdo_options'])) {
        	// Use + operator instead of array_merge to preserve integer keys
            $this->pdo_options = $options['pdo_options'] + $this->pdo_options;
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

    /**
     * @deprecated See push() instead.
     */
    public function add($data = null)
    {
        if (empty($data)) {
            throw new BackendException('No data.');
        }
        $this->push($data);
        return true;
    }

    public function push($data)
    {
        try {
            $success = $this->insert($data);
            if (!$success) {
                throw new BackendException(
                    'Statement failed: ' .
                    implode(' - ', $this->getConnection()->errorInfo())
                );
            }
        } catch (\Exception $ex) {
            // TODO: Log original error and table creation attempt.
            $this->createTable($this->db_table);

            // Try again.
            if (!$this->insert($data)) {
                throw new BackendException('Statement failed: ' .
                    implode(' - ', $this->getConnection()->errorInfo())
                );
            }
        }

        $this->last_job_id = $this->getConnection()->lastInsertId();
        return $this->last_job_id;
    }

    protected function insert($data)
    {
        $sql = sprintf('INSERT INTO `%s` (`data`, `timestamp`) VALUES (?, ?)', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);
        if ($sth === false) {
            throw new \Exception('Could not prepare statement');
        }
        $_tmp = json_encode($data);
        $sth->bindValue(1, $_tmp, \PDO::PARAM_STR);
        $sth->bindValue(2, self::getTimeStamp(), \PDO::PARAM_STR);
        return $sth->execute();
    }

    public function set($id, $data, $properties=array())
    {
        $sql = sprintf('REPLACE INTO `%s` (`id`, `data`, `timestamp`) VALUES (?, ?, ?)', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);
        $_tmp = json_encode($data);
        $sth->bindValue(1, $id, \PDO::PARAM_INT);
        $sth->bindValue(2, $_tmp, \PDO::PARAM_STR);
        $sth->bindValue(3, self::getTimeStamp(), \PDO::PARAM_STR);
        $sth->execute();
    }

    protected static function getTimeStamp()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        return $now->format('Y-m-d\TH:i:s.u');
    }
    /**
     * @return array|null The retrieved record, or null if nothing was found.
     */
    public function get($id=null)
    {
        if (empty($id)) {
            // Deprecated usage.
            return $this->pop();
        }

        $sql = sprintf('SELECT `id`, `data` FROM `%s` WHERE `id` = ?', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);
        $sth->bindValue(1, $id, \PDO::PARAM_INT);
        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $this->last_job_id = $result['id'];
            return json_decode($result['data'], true);
        }

        return null;
    }

    public function pop()
    {
        // Get oldest message.
        $sql = sprintf('SELECT `id`, `data` FROM `%s` WHERE 1 ORDER BY id ASC LIMIT 1', $this->db_table);
        $sth = $this->getConnection()->prepare($sql);

        // This will be false if the table or collection does not exist
        if ( ! $sth ) {
            return null;
        }

        $sth->execute();

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            $this->last_job_id = $result['id'];
            $this->clear($result['id']);
            return json_decode($result['data'], true);
        }
        return null;
    }

    public function popAtomic($callback) {
        try {
            $this->getConnection()->beginTransaction();
            $data = $this->pop();

            if ($data !== null) {
                if (!is_callable($callback)) {
                    throw new \RuntimeException("Bad callback passed to " . __METHOD__);
                }
                call_user_func($callback, $data);
            }

            $this->getConnection()->commit();
            return $data;
        } catch (\Exception $ex) {
            $this->getConnection()->rollBack();
            throw $ex;
        }
    }

    public function clear($id = null)
    {
        if (empty($id)) {
            throw new BackendException('No ID.');
        }
        try {
            $sql = sprintf('DELETE FROM `%s` WHERE `id` = ?', $this->db_table);
            $sth = $this->getConnection()->prepare($sql);
            $sth->bindValue(1, $id, \PDO::PARAM_INT);
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
        switch ($this->getDriverName()) {
        case 'mysql':
            $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
                        `id` mediumint(20) NOT NULL AUTO_INCREMENT,
                        `data` mediumtext NULL,
                        `timestamp` datetime NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", $table_name);
            break;
        case 'sqlite':
            $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
                        `id` INTEGER PRIMARY KEY,
                        `data` text NULL,
                        `timestamp` datetime NOT NULL
                    );", $table_name);
            break;
        default:
            throw new BackendException('Unknown database driver: ' . $this->getDriverName());
        }
        $this->getConnection()->exec($sql);

        // FIXME: we already signal failure using exceptions, should return void.
        return true;
    }

    protected function getDriverName()
    {
        return $this->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
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
