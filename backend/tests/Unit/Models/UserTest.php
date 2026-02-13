<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $expected = [
            'name',
            'email',
            'password',
            'business_name',
            'business_address',
            'siret',
            'ape_code',
            'vat_number',
            'default_payment_terms',
            'invoice_footer',
            'avatar_path',
        ];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(Hash::check('plaintext-password', $user->password));
    }

    public function test_two_factor_secret_is_cast_to_encrypted(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $casts = $user->getCasts();

        $this->assertArrayHasKey('two_factor_secret', $casts);
        $this->assertEquals('encrypted', $casts['two_factor_secret']);
    }

    public function test_bank_details_is_cast_to_encrypted(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $casts = $user->getCasts();

        $this->assertArrayHasKey('bank_details', $casts);
        $this->assertEquals('encrypted', $casts['bank_details']);
    }

    public function test_two_factor_secret_is_hidden(): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $hidden = $user->getHidden();

        $this->assertContains('two_factor_secret', $hidden);
        $this->assertContains('bank_details', $hidden);
    }

    public function test_has_two_factor_enabled_method_returns_true_when_secret_exists(): void
    {
        $userWith2FA = User::factory()->create([
            'two_factor_secret' => 'encrypted-secret',
        ]);

        $this->assertTrue($userWith2FA->hasTwoFactorEnabled());
    }

    public function test_has_two_factor_enabled_method_returns_false_when_secret_is_null(): void
    {
        $userWithout2FA = User::factory()->create([
            'two_factor_secret' => null,
        ]);

        $this->assertFalse($userWithout2FA->hasTwoFactorEnabled());
    }
}
