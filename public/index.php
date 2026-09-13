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
$path = $requestedPath;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = json_decode(file_get_contents('php://input'), true);
$body = is_array($body) ? $body : [];

try {
    if ($method === 'GET' && $path === '/v1') $result=['ok'=>true,'api'=>'Universal Amount Standard API','version'=>'0.0.1','capabilities'=>['amounts','arithmetic','currencies','rate-explicit-conversion']];
    elseif ($method === 'GET' && $path === '/v1/currencies') $result=['ok'=>true,'currencies'=>Api::currencies()];
    elseif ($method === 'POST' && $path === '/v1/amounts/normalize') $result=['ok'=>true,'data'=>Api::normalize($body)];
    elseif ($method === 'POST' && $path === '/v1/amounts/add') $result=['ok'=>true,'data'=>Api::arithmetic($body)];
    elseif ($method === 'POST' && $path === '/v1/amounts/subtract') $result=['ok'=>true,'data'=>Api::arithmetic($body, 'subtract')];
    elseif ($method === 'POST' && $path === '/v1/amounts/multiply') $result=['ok'=>true,'data'=>Api::arithmetic($body, 'multiply')];
    elseif ($method === 'POST' && $path === '/v1/amounts/divide') $result=['ok'=>true,'data'=>Api::arithmetic($body, 'divide')];
    elseif ($method === 'POST' && $path === '/v1/conversions') $result=['ok'=>true,'data'=>Api::convert($body)];
    else { http_response_code(404); $result=['ok'=>false,'error'=>'not_found']; }
} catch (Throwable $e) { http_response_code(400); $result=['ok'=>false,'error'=>'invalid_request','message'=>$e->getMessage()]; }
echo json_encode($result, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
