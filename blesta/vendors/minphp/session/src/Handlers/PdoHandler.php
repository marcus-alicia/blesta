<?php
namespace Minphp\Session\Handlers;

use SessionHandlerInterface;
use PDO;

/**
 * PDO Database Session Handler
 */
class PdoHandler implements SessionHandlerInterface
{
    protected $db;
    protected $options;

    public function __construct(PDO $db, array $options = [])
    {
        $this->options = array_merge(
            [
                'tbl' => 'sessions',
                'tbl_id' => 'id',
                'tbl_exp' => 'expire',
                'tbl_val' => 'value',
                'ttl' => 1800 // 30 mins
            ],
            $options
        );
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        $query = "DELETE FROM {$this->options['tbl']} WHERE {$this->options['tbl_id']} = :id";
        $this->db->prepare($query)
            ->execute([':id' => $sessionId]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        $query = "DELETE FROM {$this->options['tbl']} WHERE {$this->options['tbl_exp']} < :expire";
        $this->db->prepare($query)
            ->execute([':expire' => date('Y-m-d H:i:s', time() - $maxlifetime)]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        $query = "SELECT {$this->options['tbl_val']} FROM {$this->options['tbl']} "
            . "WHERE {$this->options['tbl_id']} = :id AND {$this->options['tbl_exp']} >= :expire";

        $row = $this->db->prepare($query);
        $row->setFetchMode(PDO::FETCH_OBJ);
        $row->execute([':id' => $sessionId, ':expire' => date('Y-m-d H:i:s')]);

        if (($data = $row->fetch())) {
            return (string)$data->{$this->options['tbl_val']};
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        $session = [
            ':value' => $data,
            ':id' => $sessionId,
            ':expire' => date('Y-m-d H:i:s', time() + (int)$this->options['ttl'])
        ];

        $updateQuery = "UPDATE {$this->options['tbl']} SET {$this->options['tbl_val']} = :value, "
            . "{$this->options['tbl_exp']} = :expire "
            . "WHERE {$this->options['tbl_id']} = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute($session);

        if (!$updateStmt->rowCount()) {
            // Session does not exist, so create it
            $insertQuery = "INSERT INTO {$this->options['tbl']} "
                . "({$this->options['tbl_id']}, {$this->options['tbl_val']}, {$this->options['tbl_exp']}) "
                . "VALUES (:id, :value, :expire) "
                . "ON DUPLICATE KEY UPDATE {$this->options['tbl_val']} = :new_value, "
                . "{$this->options['tbl_exp']} = :new_expire";

            $this->db->prepare($insertQuery)
                ->execute(
                    array_merge($session, [':new_value' => $session[':value'], ':new_expire' => $session[':expire']])
                );
        }
        return true;
    }
}
