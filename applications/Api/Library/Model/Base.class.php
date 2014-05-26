<?php
/**
 * Class Library_Model_Base
 *
 * @author PhpGame
 */

class Library_Model_Base
{
    protected static $Instances;
    public $dbGamecity;

    /**
     * 设置数据库连接
     */
    public function __construct()
    {
        $this->dbGamecity = new DbMysqlReadWriteSplit();
        $this->dbGamecity->addMaster(DbMysql::GetConnection(DATABASE_GAMECITY));
        $this->dbGamecity->addSlave(DbMysql::GetConnection(DATABASE_GAMECITY_READ));
    }

    /**
     * 
     * @return Library_Base
     */
    static public function Instance()
    {
        return self::InstanceInternal(__CLASS__);
    }
    
    /**
     *
     * @return Mixed
     */
    static protected function InstanceInternal($className)
    {
        if (!isset(self::$Instances[$className]))
            self::$Instances[$className] = new $className();
        return self::$Instances[$className];
    }
}
