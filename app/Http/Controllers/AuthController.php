<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;

use Validator;
use App\Models\User;
use App\Mail\EmailVerificationCode;

class AuthController extends Controller
{

  public function signin(Request $request)
  {
    if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
      $authUser = Auth::user();
      $success['token'] =  $authUser->createToken('MyAuthApp')->plainTextToken;
      $success['name'] =  $authUser->name;

      return $this->sendResponse($success, 'User signed in');
    } else {
      return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
    }
  }

  public function signup(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'first_name' => 'required',
      'last_name' => 'required',
      'username' => 'required',
      'email' => 'required|email',
      'password' => 'required|min:8',
      'confirm_password' => 'required|same:password',
    ]);

    if ($validator->fails()) {
      return $this->sendError('Error validation', $validator->errors());
    }

    $input = $request->all();
    $input['password'] = bcrypt($input['password']);
    $input['email_verification_code'] = mt_rand(1000, 9999);

    $user = User::create($input);

    Mail::to($user->email)->send(new EmailVerificationCode($user->email_verification_code));

    $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;
    $success['username'] =  $user->username;


    return $this->sendResponse($success, 'User created successfully.');
  }

  public function forgotPassword(Request $request)
  {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
      $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
      ? $this->sendResponse(['status' => __($status)])
      : $this->sendError("Error", ['email' => [__($status)]]);
  }

  public function resetPassword(Request $request)
  {
    $request->validate([
      'token' => 'required',
      'email' => 'required|email',
      'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
      $request->only('email', 'password', 'password_confirmation', 'token'),
      function ($user, $password) {
        $user->forceFill([
          'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
      }
    );

    return $status === Password::PASSWORD_RESET
      ? $this->sendResponse(['status' => __($status)])
      : $this->sendError("Error", ['email' => [__($status)]]);
  }
}
