<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsDomain;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    use SeedsDomain;

    public function test_teacher_can_login_with_correct_password(): void
    {
        $teacher = $this->makeTeacher();

        $response = $this->post(route('login.post'), [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($teacher);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $student = $this->makeStudent();

        $response = $this->from(route('login'))->post(route('login.post'), [
            'email' => $student->email,
            'password' => 'sai-mat-khau',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_protected_area(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_password_is_stored_in_hashpassword_column(): void
    {
        $student = $this->makeStudent();

        // Mật khẩu KHÔNG lưu plaintext, và Auth dùng cột hashpassword
        $this->assertNotSame('password', $student->hashpassword);
        $this->assertTrue(password_verify('password', $student->hashpassword));
    }
}
