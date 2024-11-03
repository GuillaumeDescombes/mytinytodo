<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


// FIXME: experimental, subject to change

class DBCore
{
    /** @var Database_Abstract $db */
    protected $db;

    /** @var DBCore $defaultdb */
    protected static $defaultInstance;

    /**
     *
     * @param Database_Abstract $db Value of DBConnection::instance() or similar
     * @return void
     */
    public function __construct(Database_Abstract $db) {
        $this->db = $db;
    }

    /**
     *
     * @return Database_Abstract
     * @throws Exception
     */
    public function connection()
    {
        if (!isset($this->db)) {
            throw new Exception("DBConnection is not set");
        }
        return $this->db;
    }

    /**
     *
     * @return DBCore
     * @throws Exception
     */
    public static function default() : DBCore
    {
        if (!isset(self::$defaultInstance)) {
            throw new Exception("DBCore defaultInstance is not initialized");
        }
        return self::$defaultInstance;
    }

    /**
     *
     * @param DBCore $instance
     * @return void
     */
    public static function setDefaultInstance(DBCore $instance)
    {
        self::$defaultInstance = $instance;
    }

    /**
     *
     * @param int $id
     * @return int
     */
    public function getListIdByTaskId(int $id, string $login): int
    {
        $db = $this->db;
        $sql = "SELECT {$db->prefix}todolist.list_id FROM {$db->prefix}todolist, {$db->prefix}lists ".
               "WHERE {$db->prefix}todolist.id=? AND {$db->prefix}lists.id={$db->prefix}todolist.list_id AND {$db->prefix}lists.login=?";
        $listId = (int)$db->sq($sql, array($id, $login));
        return $listId;
    }


    public function getListById(int $id, string $login): ?array
    {
        $db = $this->db;
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=? AND login=?", array($id, $login));
        return $r;
    }


    public function taskExists(int $id, string $login): bool
    {
        $db = $this->db;
        $sql = "SELECT COUNT({$db->prefix}todolist.id) FROM {$db->prefix}todolist, {$db->prefix}lists ".
               "WHERE {$db->prefix}todolist.id=? AND {$db->prefix}lists.id={$db->prefix}todolist.list_id AND {$db->prefix}lists.login=?";
        $count = (int) $db->sq($sql, array($id, $login));
        return ($count > 0) ? true : false;
    }


    public function getTaskById(int $id, string $login): ?array
    {
        $db = $this->db;
        $groupConcat = '';
        if ($db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
            $groupConcat =  "array_to_string(array_agg(tags.id), ',') AS tags_ids, string_agg(tags.name, ',') AS tags";
        }
        else {
            $groupConcat = "GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags";
        }
        $sql = "SELECT todo.*, $groupConcat FROM {$db->prefix}todolist AS todo, {$db->prefix}lists AS l ".
               "LEFT JOIN {$db->prefix}tag2task AS t2t ON todo.id = t2t.task_id ".
               "LEFT JOIN {$db->prefix}tags AS tags ON t2t.tag_id = tags.id ".
               "WHERE todo.id = ? AND l.id=todo.list_id AND l.login=? ".
               "GROUP BY todo.id";
        $r = $db->sqa($sql, array($id, $login));
        return $r;
    }

    /**
     *
     * @param int $listId
     * @param string $sqlWhere
     * @param int|string $sort
     * @param null|int $limit
     * @return array
     */
    public function getTasksByListId(int $listId, string $login, string $sqlWhere, /* int|string */ $sort, ?int $limit = null): array
    {
        $db = $this->db;

        if ($sqlWhere != '') {
            $sqlWhere = "AND $sqlWhere";
        }

        $sqlSort = '';
        if (is_int($sort)) {
            $sqlSort = "ORDER BY compl ASC, ";
            if ($sort == 0) $sqlSort .= "ow ASC";                                           // byHand
            elseif ($sort == 100) $sqlSort .= "ow DESC";                                    // byHand (reverse)
            elseif ($sort == 1) $sqlSort .= "prio DESC, ddn ASC, duedate ASC, ow ASC";      // byPrio
            elseif ($sort == 101) $sqlSort .= "prio ASC, ddn DESC, duedate DESC, ow DESC";  // byPrio (reverse)
            elseif ($sort == 2) $sqlSort .= "ddn ASC, duedate ASC, prio DESC, ow ASC";      // byDueDate
            elseif ($sort == 102) $sqlSort .= "ddn DESC, duedate DESC, prio ASC, ow DESC";  // byDueDate (reverse)
            elseif ($sort == 3) $sqlSort .= "d_created ASC, prio DESC, ow ASC";             // byDateCreated
            elseif ($sort == 103) $sqlSort .= "d_created DESC, prio ASC, ow DESC";          // byDateCreated (reverse)
            elseif ($sort == 4) $sqlSort .= "d_edited ASC, prio DESC, ow ASC";              // byDateModified
            elseif ($sort == 104) $sqlSort .= "d_edited DESC, prio ASC, ow DESC";           // byDateModified (reverse)
            elseif ($sort == 5) $sqlSort .= "title ASC, prio DESC, ow ASC";                 // byTitle
            elseif ($sort == 105) $sqlSort .= "title DESC, prio ASC, ow DESC";              // byTitle (reverse)
            else $sqlSort .= "ow ASC";
        }
        else if ($sort != '') {
            $sqlSort = "ORDER BY $sort";
        }

        $sqlLimit = '';
        if (!is_null($limit)) {
            $sqlLimit = "LIMIT $limit";
        }

        $sql = "SELECT todo.*, todo.duedate IS NULL AS ddn, GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags ".
               "FROM {$db->prefix}todolist AS todo, {$db->prefix}lists AS l ".
               "LEFT JOIN {$db->prefix}tag2task AS t2t ON todo.id = t2t.task_id ".
               "LEFT JOIN {$db->prefix}tags AS tags ON t2t.tag_id = tags.id ".
               "WHERE todo.list_id = ? AND l.id=todo.list_id AND l.login=? $sqlWhere ".
               "GROUP BY todo.id ".
               $sqlSort." ".
               $sqlLimit;
        $q = $db->dq($sql, array($listId, $login));

        $data = array();
        while ($r = $q->fetchAssoc()) {
            $data[] = $r;
        }
        return $data;
    }

    function createListWithName(string $name, string $login): ?int
    {
        $db = DBConnection::instance();
        $name = str_replace( ['"',"'",'<','>','&'], '', trim($name) );
        if ($name == '') {
            return null;
        }
        $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}lists WHERE login=?", array($login));
        $time = time();
        $db->dq("INSERT INTO {$db->prefix}lists (uuid,name,ow,d_created,d_edited,taskview,login) VALUES (?,?,?,?,?,?,?)",
                    array(generateUUID(), $name, $ow, $time, $time, 1, $login) );
        $id = $db->lastInsertId();
        return (int)$id;
    }

    /**
     * Finds all variations of tag by its "normalized" name. Return array of id.
     * @param string $name
     * @return int[]
     * @throws Exception
     */
    function getTagIdsByName(string $name): array
    {
        $db = DBConnection::instance();
        $q = $db->dq("SELECT id FROM {$db->prefix}tags WHERE ". $db->ciEquals('name', $name));
        $a = [];
        while ($r = $q->fetchAssoc()) {
            $a[] = (int) $r['id'];
        }
        return $a;
    }
}

