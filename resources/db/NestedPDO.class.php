<?php

/**
 * @class NestedPDO
 * ================
 * 
 * Subclasses PDO to enable multi-level transactions.
 * Original idea by Kenny Millington,
 * <http://www.kennynet.co.uk/2008/12/02/php-pdo-nested-transactions>
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class NestedPDO extends PDO {
    /**
     * Database drivers that support SAVEPOINTs.
     * 
     * @var array
     */
    protected static $savepointTransactions = array("pgsql", "mysql");
 
    /**
     * The current transaction level.
     * 
     * @var int
     */
    protected $transLevel = 0;
    
    /**
     * Returns whether the current database driver is nestable.
     * 
     * @return boolean
     */
    protected function nestable() {
        return in_array(
            $this->getAttribute(PDO::ATTR_DRIVER_NAME),
            self::$savepointTransactions
        );
    }
    
    /**
     * Overrides function in PDO. Initiates a new transaction or SAVEPOINT.
     */
    public function beginTransaction() {
        if($this->transLevel == 0 || !$this->nestable()) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL{$this->transLevel}");
        }
 
        $this->transLevel++;
    }
    
    /**
     * Commits the current transaction or releases the SAVEPOINT.
     */
    public function commit() {
        $this->transLevel--;
 
        if($this->transLevel == 0 || !$this->nestable()) {
            parent::commit();
        } else {
            $this->exec("RELEASE SAVEPOINT LEVEL{$this->transLevel}");
        }
    }
    
    /**
     * Rolls back to the most recent transaction or SAVEPOINT.
     */
    public function rollBack() {
        $this->transLevel--;
 
        if($this->transLevel == 0 || !$this->nestable()) {
            parent::rollBack();
        } else {
            $this->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transLevel}");
        }
    }
}