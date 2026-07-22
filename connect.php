<?php
    define('DB_DSN','mysql:host=localhost;dbname=lovestoryplanner;charset=utf8');
    define('DB_USER','lovestoryplanner');
    define('DB_PASS','gorgonzola7!');

    try{
        $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        print "Connection failed: " . $e->getMessage();
        die();
    }
?>