<?php

namespace Tests\Feature;

use Tests\TestCase;

class DisplayPreferencesTest extends TestCase
{
    public function test_login_defaults_to_english_dark_and_shows_both_controls(): void
    {
        $this->get('/login')->assertOk()->assertSee('lang="en"', false)->assertSee('data-theme="dark"', false)
            ->assertSee('Switch to light mode')->assertSee('Tukar bahasa kepada Bahasa Melayu');
    }

    public function test_preferences_persist_in_cookies_and_render_malay_light_login(): void
    {
        $this->post('/display-preferences', ['locale' => 'ms', 'theme' => 'light', 'return_to' => '/login'])
            ->assertRedirect('/login')->assertCookie('locale', 'ms')->assertCookie('theme', 'light');
        $this->withCookie('locale', 'ms')->withCookie('theme', 'light')->get('/login')->assertOk()
            ->assertSee('lang="ms"', false)->assertSee('data-theme="light"', false)->assertSee('Selamat kembali.')
            ->assertSee('Kata laluan')->assertSee('Log masuk')->assertSee('Tukar kepada mod gelap');
    }

    public function test_malay_validation_and_switching_back_to_english(): void
    {
        $this->withCookie('locale', 'ms')->post('/login', [])->assertSessionHasErrors(['login', 'password']);
        $this->assertStringContainsString('diperlukan', session('errors')->first('password'));
        $this->post('/display-preferences', ['locale' => 'en', 'theme' => 'dark', 'return_to' => '/login'])->assertCookie('locale', 'en')->assertCookie('theme', 'dark');
        $this->withCookie('locale', 'en')->withCookie('theme', 'dark')->get('/login')->assertOk()->assertSee('Welcome back.')->assertSee('data-theme="dark"', false);
    }

    public function test_preferences_reject_unknown_values_and_external_return_addresses(): void
    {
        $this->post('/display-preferences', ['locale' => 'other', 'theme' => 'other', 'return_to' => '//example.com'])
            ->assertSessionHasErrors(['locale', 'theme', 'return_to'])->assertCookieMissing('locale')->assertCookieMissing('theme');
        $this->post('/display-preferences', ['return_to' => '/\\example.com'])->assertSessionHasErrors('return_to');
        $this->withCookie('locale', 'invalid')->withCookie('theme', 'invalid')->get('/login')->assertOk()->assertSee('lang="en"', false)->assertSee('data-theme="dark"', false);
    }
}
