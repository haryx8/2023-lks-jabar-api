# 2023-lks-jabar-api
2023 LKS JABAR

## Install Laravel & Create Project
>composer create-project laravel/laravel yukpilih

## Move to Laravel Project
>cd yukpilih

## Download & Extract database migration files
[Download](https://github.com/haryx8/2023-lks-jabar-api/files/11402879/migrations.tgz)

>cd database/migration

>curl -O https://pages.github.com/database-migration.zip

>unzip database-migration.zip

## Change database credential

>vi .env

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yukpilih
DB_USERNAME=root
DB_PASSWORD=
```
>php artisan migrate

## Install & Configure JWT

>composer require php-open-source-saver/jwt-auth

>php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"

>php artisan jwt:secret

>vi config/auth.php

```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],

],
```
## Configure User Model

>vi app/Models/User.php

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'division_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];
    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```
## Create & Configure Auth Controller

>php artisan make:controller AuthController

>vi app/Http/Controllers/AuthController.php

```php
<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('username', 'password');

        $token = auth()->setTTL(1440)->attempt($credentials);
        if (!$token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        return response()->json([
            'message' => 'success',
            'access_token' => $token,
            'type_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL(),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => auth()->user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json([
            'message' => 'success',
        ]);
    }

    public function reset_password(Request $request)
    {
        $id = auth()->payload()->get('sub');
        $user = User::find($id);
        $match = "old password did not match";
        $http = 422;
        if (Hash::check($request->old_password, $user->password)) {
            $match = "reset success, user logged out";
            $http = 200;
            $User = User::find($id);
            $User->password = Hash::make($request->new_password);
            $User->save();
            auth()->logout();
        }
        return response()->json([
            'message' => $match,
        ], $http);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'string|min:1',
            'division_id' => 'string|min:1',
        ]);
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => request('role', 0),
            'division_id' => request('division_id', 1),
        ]);
        return response()->json([
            'message' => 'success',
            'user' => $user,
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'message' => 'success',
            'token' => auth()->setTTL(1440)->refresh(),
            'type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL(),
        ]);
    }
}
```

## Configure Route

>vi routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login');
    Route::post('auth/me', 'me');
    Route::post('auth/logout', 'logout');
    Route::post('auth/reset_password', 'reset_password');

    Route::post('register', 'register');
    Route::post('refresh', 'refresh');
});

use App\Http\Controllers\PollsController;
Route::controller(PollsController::class)->group(function () {
    Route::post('poll', 'poll');
    Route::get('poll', 'poll_get');
    Route::get('poll/{poll_id}', 'poll_get');
    Route::delete('poll/', 'poll_delete');
    Route::delete('poll/{poll_id}', 'poll_delete');
    Route::post('poll/{poll_id}/vote/{choice_id}', 'poll_vote');
});
```
## Run the application

>php artisan serve --host 0.0.0.0

## Download Postman Collection
[Download](https://github.com/haryx8/2023-lks-jabar-api/files/11402903/LKS.JABAR.postman_collection.json.zip)

## API Testing

### Request Header Configuration
- Accept: application/json
- Authorization: Bearer {{bearer}}

### Flow Auth
- Register
- Login
- Me
- Refresh
- Logout
- Reset Password

### Flow Poll
- Create Poll (POST) - Admin Only
- Get All Poll (GET)
- Get One Poll (GET)
- Delete Poll (Delete) - Admin Only
- Vote - User Only

## Reference
- https://laravel.com/docs/10.x/eloquent
- https://laravel.com/docs/10.x/hashing
- https://laravel.com/docs/10.x/schema
- https://laravel.com/docs/10.x/validation
- https://laravel.com/docs/10.x/queries
- https://laravel-jwt-auth.readthedocs.io/en/latest/auth-guard/
- https://blog.logrocket.com/implementing-jwt-authentication-laravel-9/#create-todo-model-controller-migration

