<?php

namespace Subman;

class Database
{
    public $cfg;
    private $dbh;

    public function __construct()
    {
        $this->cfg = new Config();
        try {
            $this->dbh = $this->connectDatabase();
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    public function __destruct()
    {
        try {
            $this->dbh = null;
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 连接数据库
     * @return 返回数据库连接
     */
    private function connectDatabase()
    {
        $dsn = $this->cfg->getValue('Database', 'Type') . ":host=" . $this->cfg->getValue('Database', 'Host') . ";dbname=" . $this->cfg->getValue('Database', 'Name') . ";charset=" . $this->cfg->getValue('Database', 'Charset');
        $dbh = new \PDO($dsn, $this->cfg->getValue('Database', 'Username'), $this->cfg->getValue('Database', 'Password'));
        return $dbh;
    }

    /**
     * 根据某列值获取整行
     * @param string $table 数据库表名
     * @param array $searchArray 欲搜索的键值，其中键名称作为列名，键值作为列值
     * @param bool $fetchAll 若启用，则使用fetchAll返回多结果数组，否则仅返回首结果
     * @return 返回整行数据
     */
    public function getRowbyName(string $table, string $select, array $searchArray, ?bool $fetchAll = false): array
    {
        // 建立并循环并合并数组
        $conditions = [];
        foreach ($searchArray as $column => &$value) {
            $conditions[] = "`$column` = :$column";
        }
        $fullSearchCommand = implode(' AND ', $conditions);

        try {
            $stmt = $this->dbh->prepare("SELECT $select FROM $table WHERE $fullSearchCommand");

            foreach ($searchArray as $column => &$value) {
                $stmt->bindParam(":$column", $value, \PDO::PARAM_STR);
            }

            $stmt->execute();

            if ($fetchAll) {
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($result === false)
                    $result = [];
            }

            return $result;
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 根据某列值获取整行
     * @param string $table 数据库表名
     * @param array $searchArray 欲搜索的键值，其中键名称作为列名，键值作为列值
     * @param array $order 欲排序的键值，其中键名称作为排序名，键值指定正向反向
     * @param bool $fetchAll 若启用，则使用fetchAll返回多结果数组，否则仅返回首结果
     * @return 返回整行数据
     */
    public function getRowbyNameOrder(string $table, string $select, array $searchArray, array $orderArray, ?bool $fetchAll = false): array
    {
        // 建立并循环并合并数组
        $searchCommands = [];
        foreach ($searchArray as $column => &$value) {
            $searchCommands[] = "`$column` = :$column";
        }
        $fullSearchCommand = implode(' AND ', $searchCommands);

        $orderCommands = [];
        foreach ($orderArray as $column => &$value) {
            $orderCommands[] = "`$column` $value";
        }
        $fullOrderCommands = implode(', ', $orderCommands);

        try {
            $stmt = $this->dbh->prepare("SELECT $select FROM $table WHERE $fullSearchCommand ORDER BY $fullOrderCommands");

            foreach ($searchArray as $column => &$value) {
                $stmt->bindParam(":$column", $value, \PDO::PARAM_STR);
            }

            $stmt->execute();

            if ($fetchAll) {
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($result === false)
                    $result = [];
            }

            return $result;
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 向数据库插入数据
     * @param string $table 数据库表名
     * @param array $insertArray 欲插入的键值，其中键名称作为列名，键值作为列值
     */
    public function insertNewRow(string $table, array $insertArray): void
    {
        $wrappedKeys = array_combine(array_map(function ($key) {
            return "`$key`";
        }, array_keys($insertArray)), $insertArray);
        $queryRow = implode(', ', array_keys($wrappedKeys));

        foreach ($insertArray as $key => &$value) {
            $queryColArray[] = ':' . $key;
        }
        $queryCol = implode(', ', $queryColArray);

        try {
            $stmt = $this->dbh->prepare("INSERT INTO $table ($queryRow) VALUES ($queryCol)");

            foreach ($insertArray as $column => &$value) {
                $stmt->bindParam(":$column", $value, \PDO::PARAM_STR);
            }

            $stmt->execute();
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 向原有数据更新值
     * @param string $table 数据库表名
     * @param array $updateArray 欲更新的键值，其中键名称作为列名，键值作为列值
     * @param array $searchArray 欲搜索的键值，其中键名称作为列名，键值作为列值
     */
    public function updateRow(string $table, array $updateArray, array $searchArray): void
    {
        $updateConditions = [];
        foreach ($updateArray as $column => &$value) {
            $updateConditions[] = "`$column` = :update_$column";
        }
        $fullupdateCommand = implode(', ', $updateConditions);

        $searchConditions = [];
        foreach ($searchArray as $column => &$value) {
            $searchConditions[] = "`$column` = :search_$column";
        }
        $fullSearchCommand = implode(' AND ', $searchConditions);

        try {
            $stmt = $this->dbh->prepare("UPDATE $table SET $fullupdateCommand WHERE $fullSearchCommand");
            foreach ($updateArray as $column => &$value) {
                $stmt->bindParam(":update_$column", $value, \PDO::PARAM_STR);
            }

            foreach ($searchArray as $column => &$value) {
                $stmt->bindParam(":search_$column", $value, \PDO::PARAM_STR);
            }

            $stmt->execute();
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 返回整个表
     * @param string $table 数据库表名
     * @param ?array $limit LIMIT语句
     * @return 返回整个表
     */
    public function getTable(string $table, string $select, ?array $limit = []): array
    {
        $limitClause = '';
        if (!empty($limit) && count($limit) === 2) {
            $offset = (int)$limit[0];
            $rowCount = (int)$limit[1];
            $limitClause = "LIMIT $offset, $rowCount";
        }

        try {
            $stmt = $this->dbh->prepare("SELECT $select FROM $table $limitClause");
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 获取表的行数量
     * @param string $table
     * @return int 行数量
     */
    public function getTableCount(string $table): int
    {
        try {
            $stmt = $this->dbh->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 根据某列值删除整行
     * @param string $table 数据库表名
     * @param array $searchArray 欲搜索的键值，其中键名称作为列名，键值作为列值
     * @return 返回整行数据
     */
    public function deleteRow(string $table, array $searchArray)
    {
        // 建立并循环并合并数组
        $conditions = [];
        foreach ($searchArray as $column => &$value) {
            $conditions[] = "$column = :$column";
        }
        $fullSearchCommand = implode(' AND ', $conditions);

        try {
            $stmt = $this->dbh->prepare("DELETE FROM $table WHERE $fullSearchCommand");

            foreach ($searchArray as $column => &$value) {
                $stmt->bindParam(":$column", $value, \PDO::PARAM_STR);
            }

            $stmt->execute();
        } catch (\PDOException $e) {
            if ($this->cfg->getValue('WebSite', 'Debug')) {
                throw new \Exception($e->getMessage());
            } else {
                throw new \Exception("服务器内部错误！");
            }
        }
    }

    /**
     * 检查内容是否有重复
     * @param string $table 数据库表名
     * @param string $colName 列名
     * @param string $checkString 需检查的内容
     * @return bool 若查出来了则 True，没查出来则 False
     */
    public function checkDuplicate(string $table, string $colName, string $checkString): bool
    {
        $checkResult = $this->getRowbyName($table, $colName, array($colName => $checkString));
        if (empty($checkResult))
            return false;
        else
            return true;
    }
}
