<?php
Class Day {
    public $db;
    function setDB($db) {
        $this->db = $db;
    }
    function getDay($row, $afterDay) {
        $offset = $afterDay - 1;
        $sql = "select close from day where symbol=\"{$row['symbol']}\" and time >= \"{$row['buy_time']}\" order by time limit {$offset},1";
        $tp_res = $this->db->query($sql);
        return $tp_res->fetch_assoc();
    }
}

