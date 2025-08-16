<?php
require 'config.php';

try {
    // Pastikan hanya menerima POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Only POST method is allowed');
    }

    // Baca input JSON
    $json_input = file_get_contents('php://input');
    if (empty($json_input)) {
        throw new RuntimeException('No data received');
    }

    $input = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
    }

    // Validasi field wajib
    $required_fields = ['project', 'date', 'line', 'latitude', 'longitude', 'scores', 'totalScore', 'grade'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new RuntimeException("Missing required field: {$field}");
        }
    }

    // Dapatkan koneksi database
    $conn = getDBConnection();

    // Sanitasi input
    $project = cleanInput($input['project'], $conn);
    $date = cleanInput($input['date'], $conn);
    $line = cleanInput($input['line'], $conn);
    $latitude = (float)$input['latitude'];
    $longitude = (float)$input['longitude'];
    $total_score = (int)$input['totalScore'];
    $grade = cleanInput($input['grade'], $conn);

    // Validasi data
    if (empty($project)) {
        throw new RuntimeException('Project name cannot be empty');
    }

    if (!strtotime($date)) {
        throw new RuntimeException('Invalid date format');
    }

    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        throw new RuntimeException('Coordinates must be numeric');
    }

    if ($total_score < 0 || $total_score > 35) {
        throw new RuntimeException('Total score must be between 0-35');
    }

    if (!in_array($grade, ['A', 'B', 'C', 'D', 'E'])) {
        throw new RuntimeException('Invalid grade value');
    }

    // Proses foto
    $photos = [];
    if (!empty($input['photos']) && is_array($input['photos'])) {
        foreach ($input['photos'] as $photo) {
            if (strpos($photo, 'data:image') === 0) {
                $photos[] = cleanInput($photo, $conn);
            }
        }
    }
    $photos_json = json_encode($photos, JSON_THROW_ON_ERROR);

    // Proses scores
    $scores = [];
    foreach ($input['scores'] as $key => $value) {
        $clean_key = cleanInput($key, $conn);
        $scores[$clean_key] = (int)$value;
    }
    $scores_json = json_encode($scores, JSON_THROW_ON_ERROR);

    // Query dengan prepared statement
    $sql = "INSERT INTO land_clearing_assessments (
        project_name, 
        assessment_date, 
        line_number, 
        latitude, 
        longitude, 
        scores, 
        total_score, 
        grade, 
        photos,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        project_name = VALUES(project_name),
        assessment_date = VALUES(assessment_date),
        latitude = VALUES(latitude),
        longitude = VALUES(longitude),
        scores = VALUES(scores),
        total_score = VALUES(total_score),
        grade = VALUES(grade),
        photos = VALUES(photos),
        updated_at = NOW()";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: '.$conn->error);
    }

    $stmt->bind_param(
        "ssssssiss",
        $project,
        $date,
        $line,
        $latitude,
        $longitude,
        $scores_json,
        $total_score,
        $grade,
        $photos_json
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: '.$stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Data saved successfully',
        'data' => [
            'id' => $stmt->insert_id,
            'line' => $line
        ]
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
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
