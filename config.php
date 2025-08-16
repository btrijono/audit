<?php
// Set header pertama kali untuk memastikan response JSON
header('Content-Type: application/json; charset=utf-8');

// Konfigurasi error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

// Konfigurasi database
define('DB_HOST', 'gis.julongindonesia.com');
define('DB_USER', 'gis');
define('DB_PASS', 'K3bunkit4');
define('DB_NAME', 'land_clearing_db');
define('DB_CHARSET', 'utf8mb4');

// Fungsi untuk mendapatkan koneksi database
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log('DB Connection Error: '.$conn->connect_error);
            throw new RuntimeException('Database connection failed');
        }
        
        $conn->set_charset(DB_CHARSET);
    }
    
    return $conn;
}

// Fungsi untuk membersihkan input
function cleanInput($data, $conn = null) {
    if ($conn === null) {
        $conn = getDBConnection();
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}
?>
