<?php
require 'config.php';

try {
    // Pastikan hanya menerima GET request
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new RuntimeException('Only GET method is allowed');
    }

    // Dapatkan koneksi database
    $conn = getDBConnection();

    // Query untuk mendapatkan semua assessment
    $sql = "SELECT * FROM land_clearing_assessments ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new RuntimeException('Query failed: '.$conn->error);
    }

    $assessments = [];
    while ($row = $result->fetch_assoc()) {
        $assessments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $assessments
    ]);

} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'System error occurred'
    ]);
} finally {
    if (isset($result)) $result->free();
    if (isset($conn)) $conn->close();
}
?>
