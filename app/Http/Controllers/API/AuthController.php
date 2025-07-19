<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = DB::select('EXEC stp_users_load @UserName = ?', [
            $request->username,
        ]);
        // Check if the result is empty
        if (empty($result)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $userFromDB = $result[0];

        if (!Hash::check($request->password, $userFromDB->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::find($userFromDB->user_id);

        // Generate Passport token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'message' => 'Authenticated successfully',
            'token' => $token,
            'user' => $userFromDB
        ]);
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }
}
