<?php

namespace Tests\Unit;

use App\Rules\ApplicationPassword;
use PHPUnit\Framework\TestCase;

class ApplicationPasswordTest extends TestCase
{
    public function test_private_passphrases_within_the_hash_limit_are_accepted(): void
    {
        foreach (['A-private-password-123', str_repeat('x', 72), str_repeat('界', 12)] as $password) {
            $errors = [];
            (new ApplicationPassword)->validate('password', $password, function ($message) use (&$errors) {
                $errors[] = $message;
            });
            $this->assertSame([], $errors);
        }
    }

    public function test_short_unicode_and_oversized_passwords_are_rejected_without_truncation(): void
    {
        foreach (['admin', str_repeat('界', 5), str_repeat('x', 73), str_repeat('界', 25)] as $password) {
            $errors = [];
            (new ApplicationPassword)->validate('password', $password, function ($message) use (&$errors) {
                $errors[] = $message;
            });
            $this->assertNotEmpty($errors);
        }
    }
}
