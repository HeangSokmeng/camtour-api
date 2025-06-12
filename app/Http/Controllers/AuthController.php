<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoginResource;
use App\Mail\ForgotPassMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $req): JsonResponse
    {
        // validation
        $req->validate([
            'email' => 'required|email|max:250',
            'password' => 'required|string'
        ]);
        $email = $req->input('email');
        $user = User::where('email', $email)->with('roles')->first();
        $user = User::where('email', $email)->with('roles')->first();
        if (!$user) return res_fail('Incorrect email or password');
        if ($user->is_lock == 'lock') {
            return res_fail('You do not have permission to access this resource.', [], 1, 403);
        }
        if (!$user) return res_fail('Incorrect email or password');
        if (!Hash::check($req->input('password'), $user->password)) return res_fail('Incorrect email or password');
        $token = $user->createToken($user->email);
        $user->token = $token->plainTextToken;
        return res_success('Login successful.', new LoginResource($user));
    }

    public function me(Request $req): JsonResponse
    {
        $loginUser = $req->user('sanctum');
        $user = User::where('id', $loginUser->id)->with('roles')->first();
        return res_success('Get me successful.', new LoginResource($user));
    }

    public function logout(Request $req): JsonResponse
    {
        $req->user('sanctum')->currentAccessToken()->delete();
        return res_success('Logout successful.');
    }

    public function forgotPass(Request $req): JsonResponse
    {
        $req->validate([
            'email' => 'required|email|max:250'
        ]);
        $email = $req->input('email');
        $user = User::where('email', $email)->first('id');
        if (!$user) return res_fail('If  you enter your account\'s email correctly, we\'ll send OTP to your email.');
        $token = strtoupper(Str::random(6));
        $reset = PasswordResetToken::where('email', $email)->first('email');
        if (!$reset) {
            $reset = new PasswordResetToken();
            $reset->email = $email;
        }
        $reset->token = Hash::make($token);
        $reset->save();
        Mail::to($email)->queue(new ForgotPassMail($token));
        return res_success('Forgot password! We sent OTP to your email successfully.');
    }

    public function verifyForgotPassOTP(Request $req): JsonResponse
    {
        // validation
        $req->validate([
            'otp' => 'required|min:6|max:6',
            'email' => 'required|email|max:250'
        ]);
        $email = $req->input('email');
        $reset = PasswordResetToken::where('email', $email)->first(['token', 'updated_at']);
        if (!$reset) return res_fail('This email did not send otp.');
        if (!Hash::check($req->input('otp'), $reset->token)) return res_fail('This OTP did not match or is incorrect.');
        if (Carbon::parse($reset->updated_at)->addMinutes(30)->isPast()) return res_fail('This OTP is expired! Please request again.', [], 2);
        return res_success('This OTP is correct.');
    }

    public function resetPass(Request $req): JsonResponse
    {
        // validation
        $req->validate([
            'email' => 'required|email|max:250',
            'otp' => 'required|string|min:6|max:6',
            'new_pass' => 'required|string|max:250|confirmed',
            'new_pass_confirmation' => 'required|string|max:250',
        ]);
        $email = $req->input('email');
        $reset = PasswordResetToken::where('email', $email)->first(['token', 'updated_at']);
        if (!$reset) return res_fail('This email did not send otp.');
        if (!Hash::check($req->input('otp'), $reset->token)) return res_fail('This OTP did not match or is incorrect.');
        if (Carbon::parse($reset->updated_at)->addMinutes(30)->isPast()) return res_fail('This OTP is expired! Please request again.', [], 2);
        $user = User::where('email', $email)->first('id');
        $user->password = $req->input('new_pass');
        $user->save();
        $reset->delete();
        return res_success('Reset password successful! Now you can login with your new password.');
    }
}
