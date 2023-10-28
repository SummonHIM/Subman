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
    public function getRowbyName(string $table, array $searchArray, ?bool $fetchAll = false): array
    {
        // 建立并循环并合并数组
        $conditions = [];
        foreach ($searchArray as $column => &$value) {
            $conditions[] = "$column = :$column";
        }
        $fullSearchCommand = implode(' AND ', $conditions);

        try {
            $stmt = $this->dbh->prepare("SELECT * FROM $table WHERE $fullSearchCommand");

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
        $queryRow = implode(', ', array_keys($insertArray));

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
            $updateConditions[] = "$column = :update_$column";
        }
        $fullupdateCommand = implode(', ', $updateConditions);

        $searchConditions = [];
        foreach ($searchArray as $column => &$value) {
            $searchConditions[] = "$column = :search_$column";
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
}
