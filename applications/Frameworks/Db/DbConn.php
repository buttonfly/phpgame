<?php
/**
 * Class DbConn
 *
 * @author PhpGame
 */

class DbConn
{
    const ErrorCode_Success = '00000';
    const ERROR_CODE_SUCCESS = '00000';  //deprecated

    static protected $sqlConnections = array();
    private $showError = false;
    private $showSql = false;

    /**
     * @var PDO
     */
    protected $pdo;
    protected $name;
    protected $lastSql;
    /**
     * @var PDOStatement
     */
    protected $lastStmt;
    protected $cachedStmts;
    protected $allowRealExec = true;
    protected $allowSaveToNonExistingPk = false;
    protected $allowGuessConditionOperator = null;
    protected $autoCloseLastStatement = null;

    static public $SqlMonitorCallbackGlobal;
    protected $sqlMonitorCallback;

    static protected function GetConnectionByClassName($name, $className)
    {
        if (!isset(self::$sqlConnections[$name])) {
            self::$sqlConnections[$name] = new $className();
            self::$sqlConnections[$name]->name = $name;
        }
        return self::$sqlConnections[$name];
    }

    static public function CloseConnection($name = null)
    {
        if ($name) {
            if (isset(self::$sqlConnections[$name])) {
                self::$sqlConnections[$name]->close();
                unset(self::$sqlConnections[$name]);
            }
        } else {
            foreach (self::$sqlConnections as $conn)
                $conn->close();
            self::$sqlConnections = array();
        }
    }

    public function __construct()
    {
        if (defined('MS_DEBUG') && MS_DEBUG)
            $this->enableShowError (true);

        $this->sqlMonitorCallback = self::$SqlMonitorCallbackGlobal;
    }

    protected function getPdoDsn()
    {
        $config = Registry::get('serverConfig');
        return isset($config['Database'][$this->name]['dsn']) ? $config['Database'][$this->name]['dsn'] : '';
    }

    protected function getPdoOptions()
    {
        $config = Registry::get('serverConfig');
        $options = isset($config['Database'][$this->name]['options']) ? $config['Database'][$this->name]['options'] : array();
        return $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPdo()
    {
        $this->connect();
        return $this->pdo;
    }

    public function connect($name = null)
    {
        if (!empty($name))
            $this->name = $name;
        $connected = false;
        if ($this->pdo) {
            try {
                $re = $this->pdo->query('SELECT 1');
                if ($re) $re = $re->fetchColumn();
                $connected = $re;
            } catch(Exception $e) {
                $connected = false;
            }
        }
        if ($connected) return true;
        $config = Registry::get('serverConfig');
        $name = $this->name;
        if (empty($config['Database'][$name]))
            throw new Exception("can not find database '$name' in global config");

        //'username' or 'user' in config
        $username = isset($config['Database'][$name]['username']) ? $config['Database'][$name]['username'] : $config['Database'][$name]['user'];
        $options = $this->getPdoOptions();
        try {
            $this->pdo = new PDO($this->getPdoDsn(), $username, $config['Database'][$name]['password'], $options);
        } catch (Exception $ex) {
            if (empty($options[PDO::ATTR_PERSISTENT]))
                throw $ex;

            //try again without pconnect .... would this help ?
            unset($options[PDO::ATTR_PERSISTENT]);
            $this->pdo = new PDO($this->getPdoDsn(), $username, $config['Database'][$name]['password'], $options);
        }
        if (isset($config['Database'][$name]['params']['autoCloseLastStatement'])) {
            $this->autoCloseLastStatement = $config['Database'][$name]['params']['autoCloseLastStatement'];
        }
        if ($this->isDriver(array('dblib', 'sqlsrv')) && is_null($this->autoCloseLastStatement))
            $this->autoCloseLastStatement = true;

        return true;
    }

    public function close()
    {
        //no real close in PDO
        $this->pdo = null;

        //for dblib bug
        unset($this->cachedStmts);
    }

    public function quote($s)
    {
        if (is_array($s) || is_object($s))
            throw new LogicException('value to quote can not be array or object');

        if ($this->connect())
            return $this->pdo->quote("$s");
        return "'" . str_replace("'", "''", $s) . "'";
    }

    public function quoteSqlName($s)
    {
        throw new Exception('not implemented');
    }

    /**
     * @param $s
     * @return string
     */
    public function quoteColumnName($s)
    {
        return $this->quoteSqlName($s);
    }

    /**
     * @param $s
     * @return string
     */
    public function quoteTableName($s)
    {
        return $this->quoteSqlName($s);
    }

    public function enableShowSql($v)
    {
        $last = $this->showSql;
        $this->showSql = $v;
        return $last;
    }

    public function enableShowError($v)
    {
        $last = $this->showError;
        $this->showError = $v;
        return $last;
    }

    public function buildWhere($condition = array(), $logic = 'AND')
    {
        $s = $this->buildCondition($condition, $logic);
        if ($s) $s = ' WHERE ' . $s;
        return $s;
    }

    protected  function quoteSqlConditionValue($v)
    {
        return $this->quote($v);
    }

    public function buildCondition($condition = array(), $logic = 'AND')
    {
        if (!is_array($condition)) {
            if (is_string($condition)) {
                //forbid to use a CONSTANT as condition
                if(strpos($condition, '>') === false
                        && strpos($condition, '<') === false
                        && strpos($condition, '=') === false
                        && strpos($condition, ' ') === false) {
                    throw new LogicException('bad sql condition: must be a valid sql condition');
                }
                return $condition;
            }
            throw new LogicException('bad sql condition: ' . gettype($condition));
        }
        $logic = strtoupper($logic);
        $content = null;
        foreach ($condition as $k => $v) {
            $v_str = null;
            $v_connect = '';

            if (is_int($k)) {
                //default logic is always 'AND'
                if ($content)
                    $content .= $logic . ' (' . $this->buildCondition($v) . ') ';
                else
                  $content = '(' . $this->buildCondition($v) . ') ';
                continue;
            }

            $k = trim($k);

            $maybe_logic = strtoupper($k);
            if (in_array($maybe_logic, array('AND', 'OR'))) {
                if ($content)
                    $content .= $logic . ' (' . $this->buildCondition($v, $maybe_logic) . ') ';
                else
                  $content = '(' . $this->buildCondition($v, $maybe_logic) . ') ';
                continue;
            }

            $k_upper = strtoupper($k);
            //the order is important, longer fist, to make the first break correct.
            $maybe_connectors = array('>=', '<=', '<>', '!=', '>', '<', '=',
                    ' NOT BETWEEN', ' BETWEEN', 'NOT LIKE', ' LIKE', ' IS NOT', ' NOT IN', ' IS', ' IN');
            foreach ($maybe_connectors as $maybe_connector) {
                $l = strlen($maybe_connector);
                if (substr($k_upper, -$l) == $maybe_connector) {
                    $k = trim(substr($k, 0, -$l));
                    $v_connect = $maybe_connector;
                    break;
                }
            }
            if (is_null($v)) {
                $v_str = ' NULL';
                if ($v_connect == '') {
                    $v_connect = 'IS';
                }
            } else if (is_array($v)) {
                if ($v_connect == ' BETWEEN') {
                    $v_str = $this->quoteSqlConditionValue($v[0]) . ' AND ' . $this->quoteSqlConditionValue($v[1]);
                } else if (is_array($v) && ! empty($v)) {
                    $v_str = null;
                    foreach ($v AS $one) {
                        if (is_array($one)) {
                            $sub_items = '';
                            foreach ($one as $sub_value) {
                                $sub_items .= ',' . $this->quoteSqlConditionValue($sub_value);
                            }
                            $v_str .= ',(' . substr($sub_items, 1) . ')' ;
                        } else {
                            $v_str .= ',' . $this->quoteSqlConditionValue($one);
                        }
                    }
                    $v_str = '(' . substr($v_str, 1) . ')';
                    if (empty($v_connect)) {
                        if ($this->allowGuessConditionOperator === null || $this->allowGuessConditionOperator === true) {
                            if ($this->allowGuessConditionOperator === null)
                                SystemErrorLog(E_WARNING, 'sql', "guessing condition operator is not allowed: use '$k IN'=>array(...)");
                            $v_connect = 'IN';
                        } else
                         throw new LogicException("guessing condition operator is not allowed: use '$k IN'=>array(...)");
                    }
                } else if (empty($v)) {
                    $v_str = $k;
                    $v_connect = '<>';
                }
            } else {
                $v_str = $this->quoteSqlConditionValue($v);
            }

            if (empty($v_connect))
                $v_connect = '=';

            $quoted_k = $this->quoteColumnName($k);
            if ($content)
                $content .= " $logic ( $quoted_k $v_connect $v_str ) ";
            else
              $content = " ($quoted_k $v_connect $v_str) ";
        }
        return $content;
    }

    protected function buildSql($sql)
    {
        $realSql = '';
        if (is_string($sql))
            return $sql;
        if (is_array($sql)) {
            $realSql = '';
            foreach ($sql as $k => $v) {
                if (is_int($k))
                    $realSql .= $v . " ";
                else if ($k == 'where' || $k == 'WHERE')
                    $realSql .= " WHERE " . $this->buildCondition($v) . " ";
                else
                  SystemErrorLog(MS_LOG_ERR, 'sql', "unknown key in sql.");

            }
        }
        return $realSql;
    }

    protected $lastErrorCode;
    protected $lastErrorInfo;
    protected $lastErrorMessage;

    public function getLastErrorMessage()
    {
        return $this->lastErrorMessage;
    }

    //FIXME: can not get correct mssql error message
    //for mssql: HY000,547,General SQL Server error: Check messages from the SQL Server [547] (severity 16) [],0,16
    //          select * from sys.messages where message_id = 547
    protected function processError($isExecSucceeded, $function, $sql, $errorCode, $errorInfo)
    {
        if (!$isExecSucceeded && $errorCode === self::ErrorCode_Success) {
            $errorCode = 'ERROR';
            $errorInfo = array($errorCode, '99999', "bad {$function} result. incorrect sql or connection broken");
        }
        $this->lastErrorCode = $errorCode;
        $this->lastErrorInfo = $errorInfo;
        $this->lastErrorMessage = join(',' , $errorInfo);

        $shouldIgnore = false;

        $ignoreStates = array(self::ErrorCode_Success, '23000');

        if (in_array($this->lastErrorCode, $ignoreStates))
            $shouldIgnore = true;

        if ($this->isDriver('dblib') && in_array($this->lastErrorInfo[1], array('2601')))
            $shouldIgnore = true;

        if (!$shouldIgnore) {
            if ($this->showError) {
                echo "SqlState: " . $this->lastErrorCode . ": " . htmlspecialchars($this->lastErrorMessage) . ". SQL: " . htmlspecialchars($sql) . "<br />";
            }
            SystemErrorLog(MS_LOG_ERR, 'sql', $this->lastErrorMessage, $sql);

            if (!$isExecSucceeded)
                throw new DbException($this->lastErrorMessage);
        }
    }

    public function setAllowRealExec($v)
    {
        $this->allowRealExec = $v;
    }

    //只有在主键不是自增id的时候，调用saveWithoutNull的时候才需要allowSaveToNonExistingPk
    public function setAllowSaveToNonExistingPk($v)
    {
        $this->allowSaveToNonExistingPk = $v;
    }

    /**
     * 是否允许条件构造的时候，自动推导操作符。例如：是否允许 'a'=>array(1,2) 推导为  a IN (1,2)
     * 如果允许，则对输入数据进行过滤，确保需要提交一个数据的地方，不要被提交上一个数组。
     *
     * @param $v   null: allow but log a warning.      false: not allowed and throw exception.     true: allowed
     */
    public function setAllowGuessConditionOperator($v)
    {
        $this->allowGuessConditionOperator = $v;
    }

    public function setSqlMonitorCallback($cb)
    {
        $oldCb = $this->sqlMonitorCallback;
        $this->sqlMonitorCallback = $cb;
        return $oldCb;
    }

    protected function doSqlMonitorCallback($func, $stage, $affectedRows = null)
    {
        if ($this->sqlMonitorCallback) {
            call_user_func($this->sqlMonitorCallback, $this, $func, $stage, $affectedRows);
        }
    }


    static public function SqlMonitorCallback_MonologDebug($dbConn, $func, $stage, $affectedRows)
    {
        static $lastBeginTime;
        if ($stage == 'begin') {
            monolog_debug("SQL/{$dbConn->getName()}", "-- $stage $func: [" . date('Y-m-d H:i:s') . "] --\n{$dbConn->getLastSql()}\n");
            $lastBeginTime = microtime(true);
        }
        if ($stage == 'end') {
            $timeDelta = microtime(true) - $lastBeginTime;
            monolog_debug("SQL/{$dbConn->getName()}", "-- $stage $func: time: $timeDelta, affected: " . var_export($affectedRows, true) . "\n\n");
        }
    }

    public function closeLastStatement()
    {
        if (is_object($this->lastStmt)) {
            try {
                $this->lastStmt->closeCursor();
            } catch (Exception $e) {
                
            }
        }
    }

    /**
     * mssql 的存储过程请用query调用。用exec容易在某些pdo下导致游标未关闭
     * @param $sql
     * @return bool|int
     * @throws Exception
     */
    public function exec($sql)
    {
        if (!$this->connect())
            return false;

        if ($this->autoCloseLastStatement)
            $this->closeLastStatement();

        $this->lastSql = trim($this->buildSql($sql));
        if ($this->showSql)
            echo htmlspecialchars($this->lastSql) . "<br />\n";

        $this->doSqlMonitorCallback('exec', 'begin');

        $sqlCmd = strtoupper(substr($this->lastSql, 0, 6));
        if (in_array($sqlCmd, array('UPDATE', 'DELETE')) && stripos($this->lastSql, 'where') === false) {
            $this->doSqlMonitorCallback('exec', 'end', 'denied');
            throw new Exception('no WHERE condition in SQL to be executed');
        }
        if ($this->allowRealExec) {
            $result = $this->pdo->exec($this->lastSql);
            $this->doSqlMonitorCallback('exec', 'end', $result);
            $this->processError($result !== false, 'exec', $this->lastSql, $this->pdo->errorCode(), $this->pdo->errorInfo());
        } else {
            $result = true; // dry run, fake result value
            $this->doSqlMonitorCallback('exec', 'end', 'dryrun');
            $this->processError($result !== false, 'exec', $this->lastSql, self::ErrorCode_Success, array());
        }

        return $result;
    }

    /**
     * @throws Exception
     * @param string $sql
     * @return PDOStatement
     */
    public function query($sql = null)
    {
        if (!$this->connect())
            return false;
        
        if ($this->autoCloseLastStatement)
            $this->closeLastStatement();
        if (empty($sql))
            $this->lastSql = $this->getSelectSql();  //不需要trim，拼接函数保证以SELECT开头
        else
           $this->lastSql = trim($this->buildSql($sql));
        if ($this->showSql)
            echo htmlspecialchars($this->lastSql) . "<br />\n";

        $this->doSqlMonitorCallback('query', 'begin');
        $sqlCmd = strtoupper(substr($this->lastSql, 0, 6));
        if (in_array($sqlCmd, array('UPDATE', 'DELETE')) && stripos($this->lastSql, 'where') === false) {
            $this->doSqlMonitorCallback('query', 'end', 'denied');
            throw new Exception('no WHERE condition in SQL to be executed');
        }

        if ($this->allowRealExec || $sqlCmd == 'SELECT') {
            $this->lastStmt = $this->pdo->query($this->lastSql);

            if ($this->lastStmt !== false) {
                $rowCount = $this->lastStmt->rowCount();
                $this->doSqlMonitorCallback('query', 'end', $rowCount);
            } else {
                $this->doSqlMonitorCallback('query', 'end', false);
            }
            //php5.4 dblib bug: 如果一个stmt因不被使用而回收，会导致相关的资源被释放，之后其他的query也都会失败
            if ($this->isDriver('dblib'))
                $this->cachedStmts[] = $this->lastStmt;

            $this->processError($this->lastStmt !== false, 'query', $this->lastSql, $this->pdo->errorCode(), $this->pdo->errorInfo());
        } else {
            $this->lastStmt = true;
            $this->doSqlMonitorCallback('query', 'end', 'dryrun');
            $this->processError($this->lastStmt !== false, 'query', $this->lastSql, self::ErrorCode_Success, array());
        }
        return $this->lastStmt;
    }

    /**
     * @param string $sql
     * @param array $params
     * @param array $driverOptions
     * @return bool
     * @throws Exception
     */
    public function preparedExec($sql, $params, $driverOptions = array())
    {
        if (!$this->connect())
            return false;

        if ($this->autoCloseLastStatement)
            $this->closeLastStatement();

        $this->lastSql = trim($sql);

        if ($this->showSql)
            echo htmlspecialchars($this->lastSql) . "<br />\n";

        $this->doSqlMonitorCallback('preparedExec', 'begin');

        $sqlCmd = strtoupper(substr($this->lastSql, 0, 6));
        if (in_array($sqlCmd, array('UPDATE', 'DELETE')) && stripos($this->lastSql, 'where') === false) {
            $this->doSqlMonitorCallback('preparedExec', 'end', 'denied');
            throw new Exception('no WHERE condition in SQL to be executed');
        }

        if ($this->allowRealExec || $sqlCmd == 'SELECT') {
            $this->lastStmt = $this->pdo->prepare($this->lastSql, $driverOptions);
            $ret = $this->lastStmt->execute($params);
            if ($ret === true) {
                $rowCount = $this->lastStmt->rowCount();
                $this->doSqlMonitorCallback('preparedExec', 'end', $rowCount);
            } else {
                $this->doSqlMonitorCallback('preparedExec', 'end', false);
            }
            if ($this->isDriver('dblib'))
                $this->cachedStmts[] = $this->lastStmt;

            $this->processError($this->lastStmt !== false, 'preparedExec', $this->lastSql, $this->lastStmt->errorCode(), $this->lastStmt->errorInfo());
            return $ret;
        } else {
            $this->lastStmt = true;
            $this->doSqlMonitorCallback('preparedExec', 'end', 'dryrun');
            $this->processError($this->lastStmt !== false, 'preparedExec', $this->lastSql, self::ErrorCode_Success, array());
            return true;
        }
    }

    public function getLastSql()
    {
        return $this->lastSql;
    }

    public function insert($table, $params)
    {
        $columns = '';
        $values = '';
        foreach ($params as $column => $value) {
            $columns .= $this->quoteColumnName($column) . ',';
            $values .= is_null($value) ? "NULL," : ($this->quote($value) . ',');
        }

        $columns = substr($columns, 0, strlen($columns) - 1);
        $values = substr($values, 0, strlen($values) - 1);

        $table = $this->quoteTableName($table);
        $sql = "INSERT INTO {$table} ($columns) VALUES ($values)";
        $ret = $this->exec($sql);

        if ($ret === false)
            return false;

        $id = @$this->pdo->lastInsertId();
        if ($id)
            return $id;

        return ! ! $ret;
    }

    public function update($table, $params, $cond)
    {
        if (empty($params))
            return false;

        if (is_string($params)) {
            $update_str = $params;
        } else {
            $update_str = '';

            foreach ($params as $column => $value) {
                if (is_int($column)) {
                    $update_str .= "$value,";
                } else {
                    $column = $this->quoteColumnName($column);
                    $value = is_null($value) ? 'NULL' : $this->quote($value);
                    $update_str .= "$column=$value,";
                }
            }
            $update_str = substr($update_str, 0, strlen($update_str) - 1);
        }

        $table = $this->quoteTableName($table);
        if (is_numeric($cond))
            $cond = $this->quoteColumnName('id') . "='$cond'";
        else
           $cond = $this->buildCondition($cond);
        $sql = "UPDATE {$table} SET $update_str WHERE $cond";
        $ret = $this->exec($sql);
        return $ret;
    }

    public function delete($table, $cond)
    {
        $table = $this->quoteTableName($table);
        $cond = $this->buildCondition($cond);
        $sql = "DELETE FROM {$table} WHERE $cond";
        $ret = $this->exec($sql);
        return $ret;
    }

    public function transBegin()
    {
        if (!$this->connect())
            return false;

        return $this->pdo->beginTransaction();
    }

    public function transCommit()
    {
        return $this->pdo->commit();
    }

    public function transRollback()
    {
        return $this->pdo->rollBack();
    }

    protected $select_sql_top;
    protected $select_sql_columns;
    protected $select_sql_from_where;
    protected $select_sql_group_having;
    protected $select_sql_order_limit;

    public function getSelectSql()
    {
        return "SELECT {$this->select_sql_top} {$this->select_sql_columns} {$this->select_sql_from_where} {$this->select_sql_group_having} {$this->select_sql_order_limit}";
    }

    /**
     * @param string $columns
     * @return DbConn
     */
    public function select($columns = '*')
    {
        $this->select_sql_top = '';
        $this->select_sql_columns = $columns;
        $this->select_sql_from_where = '';
        $this->select_sql_group_having = '';
        $this->select_sql_order_limit = '';
        return $this;
    }

    /**
     * @param $n
     * @return DbConn
     */
    public function top($n)
    {
        $n = intval($n);
        $this->select_sql_top = "TOP $n";
    }

    /**
     * @param $table
     * @return DbConn
     */
    public function from($table)
    {
        $table = $this->quoteTableName($table);
        $this->select_sql_from_where .= " FROM $table ";
        return $this;
    }

    protected function joinInternal($join, $table, $cond)
    {
        $table = $this->quoteTableName($table);
        $this->select_sql_from_where .= " $join $table ";
        if (is_string($cond)
                && (strpos($cond, '=') === false && strpos($cond, '<') === false && strpos($cond, '>') === false)
        ) {
            $column = $this->quoteColumnName($cond);
            $this->select_sql_from_where .= " USING ($column) ";
        } else {
            $cond = $this->buildCondition($cond);
            $this->select_sql_from_where .= " ON $cond ";
        }
        return $this;
    }

    /**
     * @param $table
     * @param $cond
     * @return DbConn
     */
    public function join($table, $cond)
    {
        return $this->joinInternal('JOIN', $table, $cond);
    }

    /**
     * @param $table
     * @param $cond
     * @return DbConn
     */
    public function leftJoin($table, $cond)
    {
        return $this->joinInternal('LEFT JOIN', $table, $cond);
    }

    /**
     * @param $table
     * @param $cond
     * @return DbConn
     */
    public function rightJoin($table, $cond)
    {
        return $this->joinInternal('RIGHT JOIN', $table, $cond);
    }

    /**
     * @param $cond
     * @return DbConn
     */
    public function where($cond)
    {
        $cond = $this->buildCondition($cond);
        $this->select_sql_from_where .= " WHERE $cond ";
        return $this;
    }

    /**
     * @param $group
     * @return DbConn
     */
    public function group($group)
    {
        $this->select_sql_group_having .= " GROUP BY $group ";
        return $this;
    }

    /**
     * @param $having
     * @return DbConn
     */
    public function having($cond)
    {
        $cond = $this->buildCondition($cond);
        $this->select_sql_group_having .= " HAVING $cond ";
        return $this;
    }

    /**
     * @param $order
     * @return DbConn
     */
    public function order($order)
    {
        $this->select_sql_order_limit .= " ORDER BY $order ";
        return $this;
    }

    public function isDriver($name)
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (is_array($name))
            return in_array($driver, $name);
        return $driver == $name;
    }

    public function queryScalar($sql = null, $default = null)
    {
        $stmt = $this->query($sql);
        $v = $stmt->fetchColumn(0);
        if ($v !== false)
            return $v;
        return $default;
    }

    public function getOne($sql = null, $default = null)
    {
        return $this->queryScalar($sql, $default);
    }

    /**
     * @param string|null $sql
     * @return array
     */
    public function getRow($sql = null)
    {
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string|null $sql
     * @return array
     */
    public function getColumn($sql = null)
    {
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param string|null $sql
     * @param string $key
     * @return array
     */
    public function queryAllAssocKey($sql, $key)
    {
        $rows = array();
        $stmt = $this->query($sql);
        if ($stmt)
        {
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
                $rows[$row[$key]] = $row;
        }
        return $rows;
    }

    /**
     * @param string|null $sql
     * @param string $key
     * @return array
     */
    public function getAll($sql = null, $key = '')
    {
        if ($key)
            return $this->queryAllAssocKey($sql, $key);

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($table, $cond, $order = '')
    {
        if (is_numeric($cond))
            $cond = array('id'=>"$cond");
        $table = $this->quoteTableName($table);
        $where = $this->buildWhere($cond);

        if ($order && strncasecmp($order, 'ORDER BY', 8) != 0)
            $order = 'ORDER BY ' . $order;
        $sql = "SELECT * FROM $table $where $order";
        return $this->queryRow($sql);
    }

    public function findAll($table, $cond, $order = '')
    {
        $table = $this->quoteTableName($table);
        $where = $this->buildWhere($cond);
        if ($order && strncasecmp($order, 'ORDER BY', 8) != 0)
            $order = 'ORDER BY ' . $order;
        $sql = "SELECT * FROM $table $where $order";
        return $this->queryAll($sql);
    }

    public function count($table, $cond, $columns = '*')
    {
        $table = $this->quoteTableName($table);
        $where = $this->buildWhere($cond);
        $sql = "SELECT COUNT($columns) FROM $table $where";
        return $this->querySimple($sql);
    }

    //general implemention
    public function exists($table, $cond)
    {
        $table = $this->quoteTableName($table);
        $where = $this->buildWhere($cond);
        $sql = "SELECT 1 FROM $table $where";
        return ! ! $this->querySimple($sql);
    }
}

class DbMysql extends DbConn
{
    /**
     * @static
     * @param string $name
     * @return DbMysql
     */
    static public function GetConnection($name = 'default')
    {
        return parent::GetConnectionByClassName($name, __CLASS__);
    }

    protected function getPdoDsn()
    {
        $dsn = parent::getPdoDsn();
        if (empty($dsn)) {
            $config = Registry::get('serverConfig');
            $dsn = "mysql:host={$config['Database'][$this->name]['host']};port={$config['Database'][$this->name]['port']};dbname={$config['Database'][$this->name]['dbname']}";
        }
        return $dsn;
    }

    protected function getPdoOptions()
    {
        $config = Registry::get('serverConfig');
        $options = parent::getPdoOptions();
        if (isset($config['Database'][$this->name]['charset'])) {
            $charset = $config['Database'][$this->name]['charset'];
            if ($charset == 'utf-8')
                $charset = 'utf8';
            $sqlCmdSetNames = "SET NAMES $charset";
            if (!empty($options[PDO::MYSQL_ATTR_INIT_COMMAND])) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = trim($options[PDO::MYSQL_ATTR_INIT_COMMAND]);
                if ($options[PDO::MYSQL_ATTR_INIT_COMMAND] && substr($options[PDO::MYSQL_ATTR_INIT_COMMAND], -1) != ';')
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] .= ';';
            } else {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = '';
            }
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] .= $sqlCmdSetNames . ';';
        }
        return $options;
    }

    public function quoteSqlName($s)
    {
        if (strpos($s, '`') === false && strpos($s, ' ') === false && strpos($s, '.') === false && $s[0] != '(') {
            return "`{$s}`";
        }
        return $s;
    }

    const InsertOnDuplicate_Update = 'ondup_update';
    const InsertOnDuplicate_UpdateExclude = 'ondup_exclude';
    const InsertOnDuplicate_Ignore = 'ondup_ignore';

    const INSERT_ON_DUPLICATE_UPDATE = 'ondup_update'; //@depreacted
    const INSERT_ON_DUPLICATE_UPDATE_BUT_SKIP = 'ondup_exclude'; //@depreacted
    const INSERT_ON_DUPLICATE_IGNORE = 'ondup_ignore'; //@depreacted
    public function insert($table, $params, $onDup = null)
    {
        $columns = '';
        $values = '';
        foreach ($params as $column => $value) {
            $columns .= $this->quoteColumnName($column) . ',';
            $values .= is_null($value) ? "NULL," : ($this->quote($value) . ',');
        }

        $columns = substr($columns, 0, strlen($columns) - 1);
        $values = substr($values, 0, strlen($values) - 1);

        $sql_part_ignore = '';
        $sql_part_on_dup = '';

        if (empty($onDup)) {
            //do nothing, use the default behavior
        } else if ($onDup == self::InsertOnDuplicate_Ignore) {
            $sql_part_ignore = 'IGNORE';
        } else if ($onDup == self::InsertOnDuplicate_Update) {
            if (func_num_args() >= 4)
                $update_params = func_get_arg(3);
            else
              $update_params = $params;

            $updates = array();
            foreach ($update_params as $column => $value) {
                if (is_int($column))
                   $updates[] = "$value";
                else
                 $updates[] = $this->quoteColumnName($column) . "=" . (is_null($value) ? "null" : $this->quote($value));
            }
            if ($updates)
                $sql_part_on_dup = 'ON DUPLICATE KEY UPDATE ' . join(",", $updates);
        } else if ($onDup == self::InsertOnDuplicate_UpdateExclude) {
            $noUpdateColumnNames = func_get_arg(3);
            if (!is_array($noUpdateColumnNames))
                throw new Exception('invalid InsertOnDuplicate_UpdateExclude argument');

            $updates = array();
            foreach ($params as $column => $value) {
                if (!in_array($column, $noUpdateColumnNames)) {
                    $column = $this->quoteColumnName($column);
                    $updates[] = "$column=" . (is_null($value) ? "null" : $this->quote($value));
                }
            }
            $sql_part_on_dup = 'ON DUPLICATE KEY UPDATE ' . join(",", $updates);
        }

        $table = $this->quoteTableName($table);
        $sql = "INSERT $sql_part_ignore INTO $table ($columns) VALUES ($values) $sql_part_on_dup";
        $ret = $this->exec($sql);

        if ($ret === false)
            return false;

        $id = $this->pdo->lastInsertId();
        if ($id)
            return $id;

        return ! ! $ret;
    }

    public function replace($table, $params)
    {
        $columns = '';
        $values = '';
        foreach ($params as $column => $value) {
            $columns .= $this->quoteColumnName($column) . ',';
            $values .= is_null($value) ? "NULL," : ($this->quote($value) . ',');
        }

        $columns = substr($columns, 0, strlen($columns) - 1);
        $values = substr($values, 0, strlen($values) - 1);

        $table = $this->quoteTableName($table);
        $sql = "REPLACE INTO $table ($columns) VALUES ($values)";
        $ret = $this->exec($sql);

        if ($ret === false)
            return false;

        $id = $this->pdo->lastInsertId();
        if ($id)
            return $id;

        return $ret;
    }

    const UPDATE_NORMAL = 0;
    const UPDATE_IGNORE = 1;
    public function update($table, $params, $cond, $options = 0, $order_by_limit = '')
    {
        if (empty($params))
            return false;

        if(is_string($params)) {
            $update_str = $params;
        } else {
            $update_str = '';

            foreach ($params as $column => $value)
            {
                if (is_int($column)) {
                    $update_str .= "$value,";
                } else {
                    $column = $this->quoteColumnName($column);
                    $value = is_null($value) ? 'NULL' : $this->quote($value);
                    $update_str .= "$column=$value,";
                }
            }
            $update_str = substr($update_str, 0, strlen($update_str) - 1);
        }

        $table = $this->quoteTableName($table);
        if(is_numeric($cond))
            $cond = $this->quoteColumnName('id') . "='$cond'";
        else
            $cond = $this->buildCondition($cond);
        $sql = "UPDATE ";
        if ($options == self::UPDATE_IGNORE)
            $sql .= " IGNORE ";
        $sql .= " $table SET $update_str WHERE $cond $order_by_limit";
        $ret = $this->exec($sql);
        return $ret;
    }

    /**
     * @param string $columns
     * @return DbMysql
     */
    public function select($columns = '*')
    {
        return parent::select($columns);
    }

    /**
     * @param $table
     * @return DbMysql
     */
    public function from($table)
    {
        return parent::from($table);
    }

    /**
     * @param $table
     * @param $cond
     * @return DbMysql
     */
    public function join($table, $cond)
    {
        return parent::join($table, $cond);
    }
    /**
     * @param $table
     * @param $cond
     * @return DbMysql
     */
    public function leftJoin($table, $cond)
    {
        return parent::leftJoin($table, $cond);
    }

    /**
     * @param $table
     * @param $cond
     * @return DbMysql
     */
    public function rightJoin($table, $cond)
    {
        return parent::rightJoin($table, $cond);
    }

    /**
     * @param $cond
     * @return DbMysql
     */
    public function where($cond)
    {
        return parent::where($cond);
    }

    /**
     * @param $group
     * @return DbMysql
     */
    public function group($group)
    {
        return parent::group($group);
    }

    /**
     * @param $having
     * @return DbMysql
     */
    public function having($having)
    {
        return parent::having($having);
    }

    /**
     * @param $order
     * @return DbMysql
     */
    public function order($order)
    {
        return parent::order($order);
    }

    /**
     * @param $a
     * @param null $b
     * @return DbMysql
     */
    public function limit($a, $b = null)
    {
        if (is_null($b)) {
            $a = intval($a);
            $this->select_sql_order_limit .= " LIMIT $a ";
        } else {
            $a = intval($a);
            $b = intval($b);
            $this->select_sql_order_limit .= " LIMIT $a, $b ";
        }
        return $this;
    }

    public function exists($table, $cond)
    {
        $table = $this->quoteTableName($table);
        $where = $this->buildWhere($cond);
        $sql = "SELECT 1 FROM $table $where LIMIT 1";
        return ! ! $this->querySimple($sql);
    }
    
}

class DbReadWriteSplit
{
    protected $masters = array();
    protected $slaves = array();
    protected $beginReadFromMasterStack = array();

    public function addMaster($db)
    {
        $this->masters[$db->getName()] = $db;
    }

    public function addSlave($db)
    {
        $this->slaves[$db->getName()] = $db;
    }

    protected function readByMaster($name)
    {
        if(empty($name) || ! isset($this->masters[$name])) {
            reset($this->masters);
            return current($this->masters);
        }
        return $this->masters[$name];
    }

    public function isReadingFromMaster()
    {
        return !empty($this->beginReadFromMasterStack);
    }
    /**
     * 从slave或master分配连接（所以这个函数不叫slave）
     * @var $name
     * @return DbConn
     */
    public function read($name)
    {
        //read from master (if no such name, use default master)
        if ($this->isReadingFromMaster() || empty($this->slaves) || ($name && ! isset($this->slaves[$name])))
            return $this->readByMaster($name);

        //read from slave
        if(empty($name)) {
            reset($this->slaves);
            return current($this->slaves);
        }
        return $this->slaves[$name];
    }

    /**
     * 只从master中分配数据库连接
     * @var $name
     * @return DbConn
     */
    public function master($name)
    {
        if (empty($name)) {
            reset($this->masters);
            return current($this->masters);
        }
        return $this->masters[$name];
    }

    /**
     * 只从slave中分配数据库连接
     * @var $name
     * @return DbConn
     */
    public function slave($name)
    {
        if (empty($name)) {
            reset($this->slaves);
            return current($this->slaves);
        }
        return $this->slaves[$name];
    }

    public function beginReadFromMaster()
    {
        $this->beginReadFromMasterStack[] = true;
    }

    public function endReadFromMaster()
    {
        array_pop($this->beginReadFromMasterStack);
    }
}

class DbMysqlReadWriteSplit extends DbReadWriteSplit
{
    /**
     * @param null|string $name
     * @return DbMysql
     */
    public function read($name = null)
    {
        return parent::read($name);
    }

    /**
     * @param null|string $name
     * @return DbMysql
     */
    public function master($name = null)
    {
        return parent::master($name);
    }

    /**
     * @param null|string $name
     * @return DbMysql
     */
    public function slave($name = null)
    {
        return parent::slave($name);
    }
}

class DbMssqlReadWriteSplit extends DbReadWriteSplit
{
    /**
     * @param null|string $name
     * @return DbMssql
     */
    public function read($name = null)
    {
        return parent::read($name);
    }
    /**
     * @param null|string $name
     * @return DbMssql
     */
    public function master($name = null)
    {
        return parent::master($name);
    }

    /**
     * @param null|string $name
     * @return DbMssql
     */
    public function slave($name = null)
    {
        return parent::slave($name);
    }
}

class DbException extends Exception {}

