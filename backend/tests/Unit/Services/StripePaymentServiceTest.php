<?php

use App\Models\PaymentIntent as LocalPaymentIntent;
use App\Models\PortalSettings;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->settings = PortalSettings::factory()->create([
        'user_id' => $this->user->id,
        'stripe_publishable_key' => 'pk_test_valid',
        'stripe_secret_key' => 'sk_test_valid',
    ]);

    $this->localPaymentIntent = LocalPaymentIntent::factory()->create([
        'amount' => 150.75,
        'currency' => 'EUR',
    ]);

    $this->stripeClientMock = $this->mock(StripeClient::class, function (MockInterface $mock) {
        $mock->paymentIntents = Mockery::mock();
        $mock->refunds = Mockery::mock();
    });

    $this->partialMock(StripePaymentService::class, function (MockInterface $mock) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('getStripeClient')->andReturn($this->stripeClientMock);
    });

    $this->service = app(StripePaymentService::class);
});

test('createPaymentIntent calls stripe api with amount in cents', function () {
    $stripePI = new StripePaymentIntent('pi_123');
    $stripePI->client_secret = 'pi_123_secret';

    $this->stripeClientMock->paymentIntents
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn ($arg) => $arg['amount'] === 15075))
        ->andReturn($stripePI);

    $this->service->createPaymentIntent($this->localPaymentIntent, $this->settings);
});

test('createPaymentIntent updates an existing stripe payment intent', function () {
    $this->localPaymentIntent->stripe_payment_intent_id = 'pi_existing';
    $this->localPaymentIntent->save();

    $stripePI = new StripePaymentIntent('pi_existing');
    $stripePI->client_secret = 'pi_existing_secret';

    $this->stripeClientMock->paymentIntents
        ->shouldReceive('update')
        ->once()
        ->with('pi_existing', Mockery::on(fn ($arg) => $arg['amount'] === 15075))
        ->andReturn($stripePI);

    $this->service->createPaymentIntent($this->localPaymentIntent, $this->settings);
});

test('refundPayment calls stripe api with amount in cents', function () {
    $this->localPaymentIntent->stripe_payment_intent_id = 'pi_to_refund';
    $this->localPaymentIntent->save();

    $stripeRefund = new Refund('re_123');

    $this->stripeClientMock->refunds
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn ($arg) => $arg['payment_intent'] === 'pi_to_refund' && $arg['amount'] === 5025))
        ->andReturn($stripeRefund);

    $this->service->refundPayment($this->localPaymentIntent, 50.25, $this->settings);
});

test('service propagates stripe api exceptions', function () {
    $this->stripeClientMock->paymentIntents
        ->shouldReceive('create')
        ->andThrow(InvalidRequestException::factory('Invalid API Key.'));

    $this->expectException(ApiErrorException::class);

    $this->service->createPaymentIntent($this->localPaymentIntent, $this->settings);
});
