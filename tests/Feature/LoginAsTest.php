<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginAsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure essential roles exist
        Role::firstOrCreate(['nombre' => 'admin']);
        Role::firstOrCreate(['nombre' => 'director']);
        Role::firstOrCreate(['nombre' => 'docente']);
        Role::firstOrCreate(['nombre' => 'auxiliar']);
        Role::firstOrCreate(['nombre' => 'estudiante']);
        Role::firstOrCreate(['nombre' => 'apoderado']);
    }

    private function createUserWithRole(string $roleName, array $userData = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => Hash::make('password'),
        ], $userData));
        $user->assignRole($roleName);
        return $user;
    }

    /** @test */
    public function admin_can_login_as_another_user()
    {
        $admin = $this->createUserWithRole('admin', ['nombre_usuario' => 'adminuser']);
        $targetUser = $this->createUserWithRole('director', ['nombre_usuario' => 'targetdirector']);

        $this->actingAs($admin);

        $response = $this->post(route('login.as'), ['user_id' => $targetUser->id]);

        $response->assertRedirect(route('role.selection'));
        $this->assertEquals($targetUser->id, Auth::id());
        $this->assertEquals($admin->id, session('original_user_id'));
    }

    /** @test */
    public function director_can_login_as_a_non_admin_user()
    {
        $director = $this->createUserWithRole('director', ['nombre_usuario' => 'directoruser']);
        $targetUser = $this->createUserWithRole('docente', ['nombre_usuario' => 'targetdocente']);

        $this->actingAs($director);

        $response = $this->post(route('login.as'), ['user_id' => $targetUser->id]);

        $response->assertRedirect(route('role.selection'));
        $this->assertEquals($targetUser->id, Auth::id());
        $this->assertEquals($director->id, session('original_user_id'));
    }

    /** @test */
    public function director_cannot_login_as_an_admin_user()
    {
        $director = $this->createUserWithRole('director', ['nombre_usuario' => 'directoruser']);
        $targetAdmin = $this->createUserWithRole('admin', ['nombre_usuario' => 'targetadmin']);

        $this->actingAs($director);

        $response = $this->post(route('login.as'), ['user_id' => $targetAdmin->id]);

        // Expecting a redirect back with an error, or a forbidden status if not caught by validation
        // The LoginController redirects back with 'error' session flash
        $response->assertRedirect(); // Or use assertStatus(302) if redirecting back
        $response->assertSessionHas('error');
        $this->assertEquals($director->id, Auth::id());
        $this->assertNull(session('original_user_id'));
    }

    /** @test */
    public function non_admin_or_director_cannot_login_as_another_user()
    {
        $docente = $this->createUserWithRole('docente', ['nombre_usuario' => 'docenteuser']);
        $targetUser = $this->createUserWithRole('estudiante', ['nombre_usuario' => 'targetestudiante']);

        $this->actingAs($docente);

        $response = $this->post(route('login.as'), ['user_id' => $targetUser->id]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals($docente->id, Auth::id());
        $this->assertNull(session('original_user_id'));
    }

    /** @test */
    public function user_can_switch_back_to_their_original_account()
    {
        $admin = $this->createUserWithRole('admin', ['nombre_usuario' => 'adminuser']);
        $targetUser = $this->createUserWithRole('docente', ['nombre_usuario' => 'targetdocente']);

        $this->actingAs($admin)
            ->post(route('login.as'), ['user_id' => $targetUser->id]);

        // At this point, Auth::id() is $targetUser->id and session('original_user_id') is $admin->id
        $this->assertEquals($targetUser->id, Auth::id());
        $this->assertEquals($admin->id, session('original_user_id'));

        $response = $this->post(route('login.switchback'));

        $response->assertRedirect(route('role.selection'));
        $this->assertEquals($admin->id, Auth::id());
        $this->assertNull(session('original_user_id'));
        $this->assertNull(session('current_role')); // As per controller logic
        $this->assertNull(session('current_role_id')); // As per controller logic
    }

    /** @test */
    public function switch_back_fails_if_not_logged_in_as_another_user()
    {
        $admin = $this->createUserWithRole('admin', ['nombre_usuario' => 'adminuser']);

        $this->actingAs($admin);

        $response = $this->post(route('login.switchback'));

        // Expecting redirect to home or back with error
        $response->assertRedirect(url('/home')); // As per controller logic
        $response->assertSessionHas('error');
        $this->assertEquals($admin->id, Auth::id());
    }

    /** @test */
    public function user_cannot_login_as_themselves()
    {
        $admin = $this->createUserWithRole('admin', ['nombre_usuario' => 'adminuser']);

        $this->actingAs($admin);

        $response = $this->post(route('login.as'), ['user_id' => $admin->id]);

        $response->assertRedirect(); // Redirects back
        $response->assertSessionHas('error');
        $this->assertEquals($admin->id, Auth::id());
        $this->assertNull(session('original_user_id'));
    }
}
