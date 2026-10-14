<?php
// Shared bridge: PHP -> the local Python model. No secret API key is required.
function ai_analyze(array $texts): array {
    if (!$texts) return ['available' => true, 'model_id' => null, 'results' => []];
    $base = getenv('AI_API_URL') ?: 'http://127.0.0.1:5050';
    $results = [];
    $modelId = null;
    foreach (array_chunk($texts, 100) as $batch) {
        $body = json_encode(['texts' => array_values($batch)], JSON_UNESCAPED_UNICODE);
        $context = stream_context_create(['http' => [
            'method' => 'POST', 'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $body, 'timeout' => 8, 'ignore_errors' => true
        ]]);
        $raw = @file_get_contents(rtrim($base, '/') . '/predict', false, $context);
        $decoded = $raw === false ? null : json_decode($raw, true);
        $statusLine = $http_response_header[0] ?? '';
        if (!preg_match('/\s200\s/', $statusLine) || !is_array($decoded) ||
            !isset($decoded['results'], $decoded['model_id']) || count($decoded['results']) !== count($batch)) {
            return ['available' => false, 'model_id' => null, 'results' => []];
        }
        foreach ($decoded['results'] as $result) {
            if (!is_array($result) || !isset($result['status'], $result['label_ar']) ||
                !array_key_exists('label', $result) ||
                ($result['label'] !== null && !in_array($result['label'], ['positive','negative','neutral'], true))) {
                return ['available' => false, 'model_id' => null, 'results' => []];
            }
        }
        $results = array_merge($results, $decoded['results']);
        $modelId = $decoded['model_id'];
    }
    return ['available' => true, 'model_id' => $modelId, 'results' => $results];
}

function ai_escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
