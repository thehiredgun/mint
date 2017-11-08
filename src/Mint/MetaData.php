<?php

namespace TheHiredGun\Mint;

use InvalidArgumentException;

class MetaData
{
    /**
     * @var array $tables
     */
    protected $tables = [];

    /**
     * set column
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  string $column
     */
    public function setColumn(string $table, string $column)
    {
        $this->tables[$table]['columns'][] = $column;
    }

    /**
     * set primary key
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $primaryKey
     * @param  string $column
     */
    public function setPrimaryKey(string $table, string $primaryKey)
    {
        $this->tables[$table]['primaryKey'] = $primaryKey;
    }

    /**
     * set on insert
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  mixed  $onInsert
     */
    public function setOnInsert(string $table, $onInsert)
    {
        $this->setTimestamp('onInsert', $table, $onInsert);
    }

    /**
     * set on update
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     * @param  mixed  $onUpdate
     */
    public function setOnUpdate(string $table, $onUpdate)
    {
        $this->setTimestamp('onUpdate', $table, $onUpdate);
    }

    /**
     * set timestamp
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $type
     * @param  string $table
     * @param  mixed  $value
     *
     * @throws InvalidArgumentException
     */
    protected function setTimestamp(string $type, string $table, $column)
    {
        if (!isset($this->tables[$table]) && '*' !== $table) {
            Throw new InvalidArgumentException("Table '$table' does not exist");
        }
        if ('*' === $table) {
            foreach ($this->tables as $tableName => $tableData) {
                if (in_array($column, $tableData['columns'])) {
                    $this->tables[$tableName]['columns'] = array_diff($this->tables[$tableName]['columns'], [$column]);
                    $this->tables[$tableName][$type] = $column;
                }
            }
        } else {
            if ($column) {
                if (!in_array($column, $this->tables[$table]['columns'])) {
                    Throw new InvalidArgumentException("Table '$table' does not have Column '$column'");
                }
                $this->tables[$table]['columns'] = array_diff($this->tables[$table]['columns'], [$column]);
                $this->tables[$table][$type] = $column;
            } else {
                $this->tables[$table][$type] = null;
            }
        }
    }

    /**
     * get columns
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public function getColumns(string $table)
    {
        if (!isset($this->tables[$table])) {
            Throw new InvalidArgumentException("Table '$table' does not exist");
        }

        return $this->tables[$table]['columns'];
    }

    /**
     * get primary key
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getPrimaryKey(string $table)
    {
        if (!isset($this->tables[$table])) {
            Throw new InvalidArgumentException("Table '$table' does not exist");
        }

        return $this->tables[$table]['primaryKey'];
    }

    /**
     * get on insert
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function getOnInsert(string $table)
    {
        if (!isset($this->tables[$table])) {
            Throw new InvalidArgumentException("Table '$table' does not exist");
        }

        return $this->tables[$table]['onInsert'];
    }

    /**
     * get on update
     *
     * @author Nick Wakeman <nick@thehiredgun.tech>
     *
     * @param  string $table
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function getOnUpdate(string $table)
    {
        if (!isset($this->tables[$table])) {
            Throw new InvalidArgumentException("Table '$table' does not exist");
        }

        return $this->tables[$table]['onUpdate'];
    }
}
