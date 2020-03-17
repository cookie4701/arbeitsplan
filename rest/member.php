<?php

require_once __DIR__ . '/../config.php';

function dbConnection() {
    try {
        $db = new mysqli(CConfig::$dbhost,
            CConfig::$dbuser,
            CConfig::$dbpass,
            CConfig::$dbname
        );

        return $db;
    } catch ( Exception $e) {
        return $e;
    }
}

function isModerator($user) {
    try {

        $db = dbConnection();
        $stmt = $db->stmt_init();
        $sql = "SELECT idWatcher FROM aplan_watchers WHERE user=?";

        $stmt->prepare($sql);

        $stmt->bind_param("i", $user);
        $stmt->execute();

        if ( $stmt->fetch() ) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }



    } catch (Exception $e) {
        $arr = array();
        $arr['msg'] = $e;
        echo json_encode($arr);
    }

    return false;

}

function isModeratorOf($user, $code) {
    try {

        $db = dbConnection();
        $stmt = $db->stmt_init();
        $sql = "SELECT idWatcher FROM aplan_watchers WHERE user=? AND orgacode=?";

        $stmt->prepare($sql);

        $stmt->bind_param("is", $user, $code);
        $stmt->execute();

        if ( $stmt->fetch() ) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }



    } catch (Exception $e) {
        return false;
    }

}

function moderatesUsers($user, $code) {
    $users = array();

    if ( ! isModeratorOf($user, $code)) {
        return $users;
    }

    try {
        $db = dbConnection();
        $stmt = $db->stmt_init();
        $sql = "SELECT A.iduserwatch, B.dname FROM aplan_userwatchlist AS A LEFT JOIN aplan_users AS B ON A.iduserwatch = B.id WHERE orgacode=?";
        $sql .= " ORDER BY B.dname";
        $stmt->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->bind_result($id, $uname);
        $index = 0;
        while ($stmt->fetch()) {
            $users[] = array();
            $users[$index]['id'] = $id;
            $users[$index]['displayname'] = $uname;
            $index++;
        }
        $stmt->close();
        return $users;
    } catch (Exception $excp) {
        echo $excp;
    }

    return $users;
}
