<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Uas/Decimal.php';
require_once __DIR__ . '/../src/Uas/Api.php';

use Uas\Api;

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI === 'cli-server' && $requestedPath !== '/' && is_file(__DIR__ . $requestedPath)) {
    return false;
}

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $body = [];
    if ($method === 'POST') {
        $decoded = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
        if (!$decoded instanceof stdClass) {
            throw new InvalidArgumentException('request body must be a JSON object');
        }
        $body = (array) $decoded;
    }

    $result = match ($method . ' ' . $requestedPath) {
        'GET /v1' => [
            'ok' => true,
            'api' => 'Universal Amount Standard API',
            'version' => Api::VERSION,
            'capabilities' => ['amounts', 'arithmetic', 'currencies', 'rate-explicit-conversion'],
        ],
        'GET /v1/currencies' => ['ok' => true, 'currencies' => Api::currencies()],
        'POST /v1/amounts/normalize' => ['ok' => true, 'data' => Api::normalize($body)],
        'POST /v1/amounts/add' => ['ok' => true, 'data' => Api::arithmetic($body)],
        'POST /v1/amounts/subtract' => ['ok' => true, 'data' => Api::arithmetic($body, 'subtract')],
        'POST /v1/amounts/multiply' => ['ok' => true, 'data' => Api::arithmetic($body, 'multiply')],
        'POST /v1/amounts/divide' => ['ok' => true, 'data' => Api::arithmetic($body, 'divide')],
        'POST /v1/conversions' => ['ok' => true, 'data' => Api::convert($body)],
        default => null,
    };
    if ($result === null) {
        http_response_code(404);
        $result = ['ok' => false, 'error' => 'not_found'];
    }
} catch (JsonException $exception) {
    http_response_code(400);
    $result = ['ok' => false, 'error' => 'invalid_json', 'message' => $exception->getMessage()];
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $result = ['ok' => false, 'error' => 'invalid_request', 'message' => $exception->getMessage()];
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $result = ['ok' => false, 'error' => 'internal_error'];
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
