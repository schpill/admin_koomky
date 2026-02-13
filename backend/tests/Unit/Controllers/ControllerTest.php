<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;

it('has successResponse helper', function () {
    $controller = new class extends Controller
    {
        public function testSuccess(): \Illuminate\Http\JsonResponse
        {
            return $this->successResponse(['key' => 'value'], 'OK', 200);
        }
    };

    $response = $controller->testSuccess();
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($data['data']['key'])->toBe('value');
    expect($data['meta']['message'])->toBe('OK');
});

it('has errorResponse helper', function () {
    $controller = new class extends Controller
    {
        public function testError(): \Illuminate\Http\JsonResponse
        {
            return $this->errorResponse('Not found', 404, ['id' => 'Invalid']);
        }
    };

    $response = $controller->testError();
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(404);
    expect($data['error']['message'])->toBe('Not found');
    expect($data['error']['status'])->toBe(404);
    expect($data['error']['errors']['id'])->toBe('Invalid');
});

it('errorResponse omits errors when empty', function () {
    $controller = new class extends Controller
    {
        public function testError(): \Illuminate\Http\JsonResponse
        {
            return $this->errorResponse('Server error', 500);
        }
    };

    $response = $controller->testError();
    $data = $response->getData(true);

    expect($data['error'])->not->toHaveKey('errors');
});
