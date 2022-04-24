<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:10',
        ]);

        // Return errors if validation error occur.
        if ($validator->fails()):
            $errors = $validator->errors();
            return response()->json([
                'error' => $errors
            ], 400);
        elseif ($validator->passes()): // Check if validation pass then create user and auth token. Return the auth token
            $user = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password'))
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        else:
            return response()->json([
                'error' => "Unknown Error"
            ], 500);
        endif;
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required',
        ]);
        if ($validator->fails()):
            $errors = $validator->errors();
            return response()->json([
                'error' => $errors
            ], 400);
        elseif ($validator->passes()): // Check if validation pass
            if (!Auth::attempt($request->only('email', 'password'))): //verify if email and password are correct
                return response()->json([
                    'message' => 'Invalid login details'
                ], 401);
            endif;

            $user = User::where('email', $request->get('email'))->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        else:
            return response()->json([
                'error' => "Unknown Error"
            ], 500);
        endif;
    }
}
