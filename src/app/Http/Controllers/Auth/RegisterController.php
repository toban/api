<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Jobs\UserCreateJob;
use Illuminate\Http\Request;
use App\Jobs\InvitationDeleteJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Jobs\UesrVerificationTokenCreateAndSendJob;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        // WORK
        $user = ( new UserCreateJob(
        $request->input('email'),
        $request->input('password')
      ))->handle();
        if($request->input('invite')) {
          ( new InvitationDeleteJob($request->input('invite')) )->handle();
        }
        ( new UesrVerificationTokenCreateAndSendJob($user) )->handle();

        event(new Registered($user));

        Auth::guard()->login($user);

        // HTTP Response
        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $this->convertUserForOutput($user);

        return response($res);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validation = [
          //'name' => ['required', 'string', 'max:255'],
          'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
          // TODO production set password length limit..
          'password' => ['required', 'string'/*'min:8'*/],
          // SHIFT we confirm this in JS.. do don't do it here?
          //'password' => ['required', 'string', 'min:8', 'confirmed'],
          'recaptcha' => 'required|captcha',
      ];

        // XXX: for phpunit dont validate captcha when requested....
        // TODO this should be mocked in the test instead
        if (getenv('PHPUNIT_RECAPTCHA_CHECK') == '0') {
            unset($validation['recaptcha']);
        }

        // If this is the first user then do not require an invitation or captcha
        if (User::count() === 0) {
            $inviteRequired = false;
            unset($validation['recaptcha']);
        } else {
            $inviteRequired = true;
            $validation['invite'] = 'required|exists:invitations,code';
        }

        return Validator::make($data, $validation);
    }

    // TODO why is this needed?
    // TODO the model used by the frontend stuff should just not have the password...
    protected function convertUserForOutput(User $user)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'verified' => $user->verified,
        ];
    }
}