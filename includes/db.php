<?php


const DB_HOST = 'localhost';
const DB_NAME = 'crypt_of_secrets';
const DB_USER = 'root';
const DB_PASS = '';

try {
    //connecting to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //if fails throws big error

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //fetch named keys

            PDO::ATTR_EMULATE_PREPARES => false, // send query and user data seperatly 
        ]
    );

} catch (PDOException $e) {
    if (defined('DEV_MODE') && DEV_MODE) 
        //dev mode shows errors 
    {
        die('Database connection failed: ' . $e->getMessage());
    }
    error_log('DB connection failed: ' . $e->getMessage());
    die('The crypt is sealed. Try again later.');
}
