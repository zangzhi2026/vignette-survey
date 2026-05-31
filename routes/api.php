<?php
/**
 * API 接口
 */

require_once __DIR__ . '/../index.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_GET['method'] ?? '';

switch ($method) {
    case 'saveSurvey':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title'])) {
            http_response_code(400);
            echo json_encode(['error' => '标题不能为空']);
            exit;
        }
        
        $surveys = get_surveys();
        
        $existIndex = -1;
        foreach ($surveys as $i => $s) {
            if ($s['id'] === $input['id']) {
                $existIndex = $i;
                break;
            }
        }
        
        if ($existIndex >= 0) {
            $surveys[$existIndex] = $input;
        } else {
            $surveys[] = $input;
        }
        
        save_surveys($surveys);
        echo json_encode(['success' => true, 'id' => $input['id']]);
        break;
        
    case 'deleteSurvey':
        $id = $_GET['id'] ?? '';
        $surveys = get_surveys();
        $surveys = array_filter($surveys, fn($s) => $s['id'] !== $id);
        save_surveys(array_values($surveys));
        echo json_encode(['success' => true]);
        break;
        
    case 'submitResponse':
        $input = json_decode(file_get_contents('php://input'), true);
        
        $survey_id = $input['survey_id'] ?? '';
        $data = $input['data'] ?? [];
        
        if (empty($survey_id) || empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => '数据不完整']);
            exit;
        }
        
        $responses = get_responses($survey_id);
        $responses[] = $data;
        save_responses($survey_id, $responses);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'getResponses':
        $id = $_GET['id'] ?? '';
        $responses = get_responses($id);
        echo json_encode($responses);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => '未知方法']);
}