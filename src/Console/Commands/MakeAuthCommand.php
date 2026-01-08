<?php

namespace Velocix\Console\Commands;

use Velocix\Console\Command;

class MakeAuthCommand extends Command
{
    protected $signature = 'make:auth';
    protected $description = 'Scaffold basic authentication';

    public function handle($args = [])
    {
        $this->info('Scaffolding authentication...');
        $this->line('');

        // Create User model
        $this->createUserModel();
        
        // Create Auth controllers
        $this->createAuthControllers();
        
        // Create users migration
        $this->createUsersMigration();
        
        // Create auth views
        $this->createAuthViews();
        
        // Create auth routes
        $this->createAuthRoutes();
        
        // Create auth middleware
        $this->createAuthMiddleware();

        $this->line('');
        $this->info('✓ Authentication scaffolding completed!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. Run: php velocix migrate');
        $this->line('  2. Include routes/auth.php in routes/web.php');
        $this->line('  3. Visit /login to test');
        $this->line('');
    }

    protected function createUserModel()
    {
        $filename = 'app/Models/User.php';
        
        if (file_exists($filename)) {
            $this->warn('User model already exists, skipping...');
            return;
        }

        $stub = <<<'PHP'
<?php

namespace App\Models;

use Velocix\Database\Model;
use Velocix\Auth\Hash;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public static function create($attributes)
    {
        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        return parent::create($attributes);
    }
}
PHP;

        if (!is_dir('app/Models')) {
            mkdir('app/Models', 0755, true);
        }

        file_put_contents($filename, $stub);
        $this->info('✓ Created User model');
    }

    protected function createAuthControllers()
    {
        $loginController = 'app/Controllers/Auth/LoginController.php';
        $registerController = 'app/Controllers/Auth/RegisterController.php';

        if (!is_dir('app/Controllers/Auth')) {
            mkdir('app/Controllers/Auth', 0755, true);
        }

        // LoginController
        if (!file_exists($loginController)) {
            $stub = <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use Velocix\Http\Controller;
use Velocix\Http\Request;
use Velocix\Auth\Auth;
use Velocix\Validation\Validator;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return $this->view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (Auth::attempt($request->only(['email', 'password']))) {
            return $this->json([
                'message' => 'Login successful',
                'redirect' => '/dashboard'
            ]);
        }

        return $this->json([
            'error' => 'Invalid credentials'
        ], 401);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
PHP;
            file_put_contents($loginController, $stub);
            $this->info('✓ Created LoginController');
        }

        // RegisterController
        if (!file_exists($registerController)) {
            $stub = <<<'PHP'
<?php

namespace App\Http\Controllers\Auth;

use Velocix\Http\Controller;
use Velocix\Http\Request;
use Velocix\Auth\Auth;
use Velocix\Validation\Validator;
use App\Models\User;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return $this->view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create($request->only(['name', 'email', 'password']));

        Auth::login($user->toArray());

        return $this->json([
            'message' => 'Registration successful',
            'redirect' => '/dashboard'
        ], 201);
    }
}
PHP;
            file_put_contents($registerController, $stub);
            $this->info('✓ Created RegisterController');
        }
    }

    protected function createUsersMigration()
    {
        $timestamp = date('Y_m_d_His');
        $filename = "database/migrations/{$timestamp}_create_users_table.php";

        if (!is_dir('database/migrations')) {
            mkdir('database/migrations', 0755, true);
        }

        $stub = <<<'PHP'
<?php

use Velocix\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->createTable('users', function($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
PHP;

        file_put_contents($filename, $stub);
        $this->info('✓ Created users migration');
    }

    protected function createAuthViews()
    {
        if (!is_dir('resources/views/auth')) {
            mkdir('resources/views/auth', 0755, true);
        }

        // Login view
        $loginView = file_exists('resources/views/auth/login.vlx.php');
        if (!$loginView) {
            $stub = <<<'HTML'
@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

        <form action="/login" method="POST" data-velocix-form>
            {!! csrf_field() !!}

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                Register
            </button>

            <p class="mt-4 text-center text-gray-600">
                Already have an account? 
                <a href="/login" data-velocix-link class="text-blue-600 hover:underline">
                    Login
                </a>
            </p>
        </form>
    </div>
</div>
@endsection
HTML;
            file_put_contents('resources/views/auth/register.vlx.php', $stub);
            $this->info('✓ Created register view');
        }
    }

    protected function createAuthRoutes()
    {
        $filename = 'routes/auth.php';

        if (file_exists($filename)) {
            $this->warn('Auth routes already exist, skipping...');
            return;
        }

        $stub = <<<'PHP'
<?php

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;

$router = app('router');

// Guest routes (only for non-authenticated users)
$router->group(['middleware' => GuestMiddleware::class], function($router) {
    $router->get('/login', 'App\Http\Controllers\Auth\LoginController@showLoginForm');
    $router->post('/login', 'App\Http\Controllers\Auth\LoginController@login');
    
    $router->get('/register', 'App\Http\Controllers\Auth\RegisterController@showRegistrationForm');
    $router->post('/register', 'App\Http\Controllers\Auth\RegisterController@register');
});

// Protected routes (requires authentication)
$router->group(['middleware' => AuthMiddleware::class], function($router) {
    $router->post('/logout', 'App\Http\Controllers\Auth\LoginController@logout');
});
PHP;

        file_put_contents($filename, $stub);
        $this->info('✓ Created auth routes');
    }

    protected function createAuthMiddleware()
    {
        $authMiddleware = 'app/Http/Middleware/AuthMiddleware.php';
        $guestMiddleware = 'app/Http/Middleware/GuestMiddleware.php';

        if (!is_dir('app/Http/Middleware')) {
            mkdir('app/Http/Middleware', 0755, true);
        }

        // AuthMiddleware
        if (!file_exists($authMiddleware)) {
            $stub = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Velocix\Http\Middleware;
use Velocix\Http\Request;
use Velocix\Auth\Auth;

class AuthMiddleware extends Middleware
{
    public function handle(Request $request, $next)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return json(['error' => 'Unauthenticated'], 401);
            }

            return redirect('/login');
        }

        return $next($request);
    }
}
PHP;
            file_put_contents($authMiddleware, $stub);
            $this->info('✓ Created AuthMiddleware');
        }

        // GuestMiddleware
        if (!file_exists($guestMiddleware)) {
            $stub = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Velocix\Http\Middleware;
use Velocix\Http\Request;
use Velocix\Auth\Auth;

class GuestMiddleware extends Middleware
{
    public function handle(Request $request, $next)
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
PHP;
            file_put_contents($guestMiddleware, $stub);
            $this->info('✓ Created GuestMiddleware');
        }
    }
}