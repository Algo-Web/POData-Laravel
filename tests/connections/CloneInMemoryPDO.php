<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 8/03/20
 * Time: 12:29 PM
 */

namespace AlgoWeb\PODataLaravel\Tests\Connections;

use PDO;
use SQLite3;

class CloneInMemoryPDO
{
    private static function pdoQuery(PDO $con, $query, $values = array())
    {
        if ($values) {
            $stmt = $con->prepare($query);
            $stmt->execute($values);
        } else {
            $stmt = $con->query($query);
        }
        return $stmt;
    }

    public static function clone(PDO $from, PDO $to)
    {
        $sql = self::cloneStructureToString($from);
        $to->exec($sql);
    }

    public static function cloneStructureToString(PDO $from)
    {
        $tables = $from->query("SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';");
        $sql = '';
        while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
            $tableName = $table['name'];
            $sql .= self::pdoQuery($from, "SELECT sql FROM sqlite_master WHERE name = '{$tableName}'")->fetchColumn() . ";\n\n";
            $rows = $from->query("SELECT * FROM {$tableName}");
            $sql .= "INSERT INTO {$tableName} (";
            $columns = $from->query("PRAGMA table_info({$tableName})");
            $fieldnames = [];
            while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                $fieldnames[] = $column["name"];
            }
            $sql .= implode(",", $fieldnames) . ") VALUES";
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $k => $v) {
                    $row[$k] = "'" . SQLite3::escapeString($v) . "'";
                }
                $sql .= "\n(" . implode(",", $row) . "),";
            }
            return rtrim($sql, ",") . ";\n\n";
        }
    }
}
