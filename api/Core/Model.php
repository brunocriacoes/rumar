<?php

namespace Core;

use Core\Env;

class Model
{
    private $db_type;
    private $db_host;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $pdo;
    private $db_port;

    public function __construct()
    {
        $this->db_type = $_ENV['POSTGRES_DB_TYPE'] ?? "";
        $this->db_host = $_ENV['POSTGRES_HOST'] ?? "";
        $this->db_name = $_ENV['POSTGRES_DB'] ?? "";
        $this->db_user = $_ENV['POSTGRES_USER'] ?? "";
        $this->db_pass = $_ENV['POSTGRES_PASSWORD'] ?? "";
        $this->db_port = $_ENV['POSTGRES_PORT'] ?? "";
        $this->connect();
    }

    public function connect()
    {
        try {
            if ($this->db_type == 'mysql') {
                $dsn = "mysql:host={$this->db_host};dbname={$this->db_name}";
                $this->pdo = new \PDO($dsn, $this->db_user, $this->db_pass);
            } elseif ($this->db_type == 'pgsql') {
                $dsn = "pgsql:host={$this->db_host};port={$this->db_port};dbname={$this->db_name}";
                $this->pdo = new \PDO($dsn, $this->db_user, $this->db_pass);
            } elseif ($this->db_type == 'sqlite') {
                $db_file = __DIR__ . '/../' . $this->db_name;
                $dsn = "sqlite:$db_file";
                $this->pdo = new \PDO($dsn);
            } else {
                throw new \Exception("Tipo de banco não suportado: {$this->db_type}");
            }
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Erro ao conectar ao banco: " . $e->getMessage());
        }
    }

    function interpolateQuery($query, $params)
    {
        foreach ($params as $key => $value) {
            $paramKey = strpos($key, ':') === 0 ? $key : ':' . $key;
            if (is_string($value)) {
                $value = "'" . str_replace("'", "\'", $value) . "'";
            } elseif (is_null($value)) {
                $value = "NULL";
            } elseif (is_bool($value)) {
                $value = $value ? "1" : "0";
            }
            $query = str_replace($paramKey, $value, $query);
        }
        return $query;
    }

    public function insert(string $table, array $data)
    {
        try {
            if (empty($data)) {
                throw new \Exception("Os dados estão vazios.");
            }

            $data = array_filter($data);

            $columns = implode(", ", array_keys($data));
            $values = [];

            foreach ($data as $value) {
                if (is_string($value)) {
                    $values[] = "'" . addslashes($value) . "'";
                } elseif (is_null($value)) {
                    $values[] = "NULL";
                } else {
                    $values[] = $value;
                }
            }

            $valuesString = implode(", ", $values);

            $sql = "INSERT INTO $table ($columns) VALUES ($valuesString)";

            $this->pdo->query($sql);

            $lastId = $this->pdo->lastInsertId();
            return $lastId;
        } catch (\Exception $e) {
            echo $sql;
            throw new \Exception("Erro ao inserir dados: " . $e->getMessage());
        }
        return 0;
    }

    public function paginate(string $table, $by = 'id',  $page = 1, $itemsPerPage = 100)
    {
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM {$table} ORDER BY $by DESC LIMIT $itemsPerPage OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function select($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }

    public function find($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }

    public function findLast($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where ";
            }
            $sql .= " ORDER BY id DESC LIMIT 1 ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }

    public function update($table, $data, $where = '', $params = array())
    {
        //var_dump($params); die();
        try {
            if (empty($data)) {
                throw new \Exception("Os dados estão vazios.");
            }
            $set = '';
            foreach ($data as $key => $value) {
                $set .= "$key = :$key, ";
            }
            $set = rtrim($set, ', ');
            $sql = "UPDATE $table SET $set";


            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $params = array_merge($data, $params);
            //var_dump($sql); die();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            echo $this->interpolateQuery($sql, $data);
            throw new \Exception("Erro ao update dados: " . $e->getMessage());
        }
    }

    public function query($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function disconnect() {}

    public function  delete($table, $where = '', $params = array())
    {
        try {
            $sql = "DELETE FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }
}
