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

        // $token = Auth::attempt($credentials);
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
