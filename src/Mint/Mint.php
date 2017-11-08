<?php

namespace TheHiredGun\Mint;

use InvalidArgumentException;
use PDO;

/**
 * Mint: A crisp PDO Abstraction Class
 *
 * @author Nick Wakeman <nick@thehiredgun.tech>
 */
class Mint
{
    /**
     * @var PDO $pdo
     */
    protected $pdo;

    /**
     * @var string $databaseType
     */
    protected $databaseType;

    /**
     * @var array $databaseTimestampFunctions
     */
    protected $databaseTimestampFunctions = [
        'mysql'  => 'NOW()',
        'sqlite' => 'datetime(\'now\')',
    ];

    /**
     * @var bool $manageTimestampedColumns
     */
    protected $manageTimestampedColumns = false;

    /**
     * __construct
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  PDO $pdo
     *
     * @throws InvalidArgumentException
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->databaseType = strtolower($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $methodName = 'setMetaDataFor' . ucwords($this->databaseType);
        if (!method_exists($this, $methodName)
            ||
            !isset($this->databaseTimestampFunctions[$this->databaseType])
        ) {
            Throw new InvalidArgumentException("This class is not yet fully functional for {$this->databaseType} databases");
        } else {
            $this->metaData = new MetaData();
            $this->{$methodName}();
        }
    }

    /**
     * set manage timestamped columns
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  bool $manageTimestampedColumns
     *
     * @return $this
     */
    public function setManageTimestampedColumns(bool $ignoreTimestampedColumns)
    {
        $this->manageTimestampedColumns = $ignoreTimestampedColumns;

        return $this;
    }

    /**
     * set on insert
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  mixed  $column
     *
     * @return PAO    $this
     */
    public function setOnInsert(string $table, $column)
    {
        $this->metaData->setOnInsert($table, $column);

        return $this;
    }

    /**
     * set on update
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  mixed  $column
     *
     * @return PAO    $this
     */
    public function setOnUpdate(string $table, $column)
    {
        $this->metaData->setOnUpdate($table, $column);

        return $this;
    }

    /**
     * set meta data for mysql
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     */
    protected function setMetaDataForMysql()
    {
        $query =
            'SELECT
                TABLE_NAME,
                COLUMN_NAME,
                COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = (
                SELECT DATABASE()
            )'
        ;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if ('PRI' === $column['COLUMN_KEY']) {
                $this->metaData->setPrimaryKey($column['TABLE_NAME'], $column['COLUMN_NAME']);
            } else {
                $this->metaData->setColumn($column['TABLE_NAME'], $column['COLUMN_NAME']);
            }
        }
        $tables = array_unique(array_column($columns, 'TABLE_NAME'));
        foreach ($tables as $table) {
            $this->metaData->setOnInsert($table, null);
            $this->metaData->setOnUpdate($table, null);
        }
    }

    /**
     * set meta data for sqlite
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     */
    protected function setMetaDataForSqlite()
    {
        $query = 'SELECT name FROM sqlite_master WHERE type="table" AND name != "sqlite_sequence"';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($tables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name')) {
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare('PRAGMA table_info(' . $table . ')');
                $stmt->execute();
                if ($columns = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
                    foreach ($columns as $column) {
                        if (1 == $column['pk']) {
                            $this->metaData->setPrimaryKey($table, $column['name']);
                        } else {
                            $this->metaData->setColumn($table, $column['name']);
                        }
                    }
                    $this->metaData->setOnInsert($table, null);
                    $this->metaData->setOnUpdate($table, null);
                    if (!$this->metaData->getPrimaryKey($table)) {
                        $this->metaData->setPrimaryKey($table, 'rowid');
                    }
                }
            }
        }
    }

    /**
     * select
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return array
     */
    public function select(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($this->tokenizeParams($params));

        return $stmt->fetchAll();
    }

    /**
     * select one
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return array
     */
    public function selectOne(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($this->tokenizeParams($params));

        return $stmt->fetch();
    }

    /**
     * update
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return int rowCount
     */
    public function update(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($this->tokenizeParams($params));

        return $stmt->rowCount();
    }

    /**
     * delete
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $query
     * @param  array  $params
     *
     * @return int $stmt->rowCount
     */
    public function delete(string $query, array $params = [])
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($this->tokenizeParams($params));

        return $stmt->rowCount();
    }

    /**
     * select one by id
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  mixed  $id
     *
     * @return array
     */
    public function selectOneById(string $table, $id)
    {
        $primaryKey = $this->metaData->getPrimaryKey($table);
        switch ($this->databaseType) {
            case 'mysql':
                $query = "SELECT * FROM $table WHERE $primaryKey = :$primaryKey";
            break;
            case 'sqlite':
                $query = "SELECT $primaryKey, * FROM $table WHERE $primaryKey = :$primaryKey";
            break;
        }

        return $this->selectOne($query, [":$primaryKey" => $id]);
    }

    /**
     * delete one by id
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $tableName
     * @param  mixed  $id
     *
     * @return int $stmt->rowCount()
     */
    public function deleteOneById(string $table, $id)
    {
        $primaryKey = $this->metaData->getPrimaryKey($table);
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE $primaryKey = :$primaryKey");
        $stmt->execute([":$primaryKey" => $id]);

        return $stmt->rowCount();
    }

    /**
     * insert one
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  array  $data
     *
     * @throws InvalidArgumentException
     *
     * @return int $pdo->lastInsertId()
     */
    public function insertOne(string $table, array $data)
    {
        $tableColumns = $this->metaData->getColumns($table);
        $columns = [];
        $params = [];
        $timestamps = [];
        foreach ($data as $key => $value) {
            $column = trim($key, ':');
            if (in_array($column, $tableColumns)) {
                $columns[] = $column;
                $params[':' . $column] = $value;
            }
        }
        if (0 === count($params)) {
            Throw new InvalidArgumentException("You haven't supplied any valid parameters");
        }
        if ($this->manageTimestampedColumns) {
            if ($column = $this->metaData->getOnInsert($table)) {
                $columns[] = $column;
                $timestamps[] = $this->getTimestampFunction();
            }
            if ($column = $this->metaData->getOnUpdate($table)) {
                $columns[] = $column;
                $timestamps[] = $this->getTimestampFunction();
            }
        }
        $query =
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', array_merge(array_keys($params), $timestamps)) . ')'
        ;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $this->pdo->lastInsertId();
    }

    /**
     * update one
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  array  $data
     * @param  mixed  $id
     *
     * @throws InvalidArgumentException
     *
     * @return int $stmt->rowCount()
     */
    public function updateOne(string $table, array $data, $id)
    {
        $tableColumns = $this->metaData->getColumns($table);
        $columnsAndTokens = [];
        $params = [];
        foreach ($data as $key => $value) {
            $column = trim($key, ':');
            if (in_array($column, $tableColumns)) {
                $columnsAndTokens[] = $column . ' = :' . $column;
                $params[':' . $column] = $value;
            }
        }
        if (0 === count($params)) {
            Throw new InvalidArgumentException("You haven't supplied any valid parameters");
        }
        if ($this->manageTimestampedColumns && $column = $this->metaData->getOnUpdate($table)) {
            $columnsAndTokens = $column . ' = ' . $this->getTimestampFunction();
        }
        $primaryKey = $this->metaData->getPrimaryKey($table);
        $params[':' . $primaryKey] = $id;
        $query =
            'UPDATE ' . $table . ' SET ' . implode(', ', $columnsAndTokens) .
            ' WHERE ' . $this->metaData->getPrimaryKey($table) . ' = :' . $primaryKey
        ;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * tokenize params
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  array $params
     *
     * @return array
     */
    protected function tokenizeParams(array $params = [])
    {
        $tokenizedParams = [];
        if (count($params)) {
            foreach ($params as $column => $value) {
                $tokenizedParams[':' . trim($column, ':')] = $value;
            }
        }

        return $tokenizedParams;
    }

    /**
     * get timestamp function
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @return string
     */
    public function getTimestampFunction()
    {
        return $this->databaseTimestampFunctions[$this->databaseType];
    }
}
