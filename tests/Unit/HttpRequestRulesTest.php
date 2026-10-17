<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\AttachmentRequest;
use App\Http\Requests\SettingsRequest;
use App\Http\Requests\SignatureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class HttpRequestRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_request_exposes_authorization_rules_and_custom_messages(): void
    {
        $request = new AttachmentRequest;

        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('attachment', $request->rules());
        $this->assertContains('required', $request->rules()['attachment']);
        $this->assertStringContainsString('attachment type is not allowed', $request->messages()['attachment.mimes']);
        $this->assertStringContainsString('maximum size', $request->messages()['attachment.max']);
    }

    public function test_settings_request_has_profile_rules_and_ignores_email_owned_by_the_same_user(): void
    {
        $user = User::factory()->create();
        $request = SettingsRequest::create('/settings/profile', 'POST', [
            'tab' => 'profile',
            'name' => 'Changed Name',
            'email' => $user->email,
        ]);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => null);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), $rules);

        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_settings_request_applies_mail_tab_rules_and_rejects_invalid_options(): void
    {
        $user = User::factory()->create();
        $request = SettingsRequest::create('/settings/mail', 'POST', [
            'tab' => 'mail',
            'page_size' => 15,
            'default_folder' => '',
            'reply_behavior' => 'reply_anyone',
        ]);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => null);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), $rules);

        $this->assertArrayHasKey('page_size', $rules);
        $this->assertArrayHasKey('default_folder', $rules);
        $this->assertArrayHasKey('reply_behavior', $rules);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('page_size'));
        $this->assertTrue($validator->errors()->has('default_folder'));
        $this->assertTrue($validator->errors()->has('reply_behavior'));
    }

    public function test_settings_request_has_no_rules_when_no_relevant_tab_is_selected(): void
    {
        $request = SettingsRequest::create('/settings', 'POST');
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->setRouteResolver(fn () => null);

        $this->assertSame([], $request->rules());
    }

    public function test_signature_request_requires_an_authenticated_user_and_valid_fields(): void
    {
        auth()->logout();
        $request = new SignatureRequest;
        $this->assertFalse($request->authorize());

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue($request->authorize());

        $rules = $request->rules();
        $validator = Validator::make(['name' => '', 'content_json' => 'invalid', 'is_default' => 'not-boolean'], $rules);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('content_json'));
        $this->assertTrue($validator->errors()->has('is_default'));
    }
}
