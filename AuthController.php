<?php

    namespace App\Http\Controllers;

    use Illuminate\Foundation\Auth\ResetsPasswords;
    use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
    use Illuminate\Http\Request;
    use App\User;
    use Illuminate\Support\Facades\Input;
    use Illuminate\Support\Facades\Password;


    class AuthController extends Controller
    {
        use SendsPasswordResetEmails;
        /**
         * Create a new AuthController instance.
         *
         * @return void
         */
        public function __construct()
        {
            $this->middleware('auth:api', ['except' => ['login']]);
        }

        public function register (Request $request)
        {
            $user = User::create(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ]);

            $token = auth()->login($user);

            return $this->respondWithToken($token);
        }

        public function login ()
        {
            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }


            return $this->respondWithToken($token);
        }
        
        public function logout ()
        {
            auth()->logout();

            return response()->json(['message' => 'Successfully logged out']);
        }

        protected function respondWithToken ($token)
        {
            return response()->json(
                [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth()->factory()->getTTL() * 60
                ]);
        }

        /**
         * Send a reset link to the given user.
         *
         * @param \Illuminate\Http\Request $request
         *
         * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
         */
        public function sendResetLinkEmail (Request $request)
        {
            $response = [];

            // Get the value from the form
            $input['email'] = Input::get('email');

            // Must not already exist in the `email` column of `users` table
            $rules = array('email' => 'required|email');

            $validator = \Illuminate\Support\Facades\Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = $validator->errors()->messages();
                $response = [
                    'status' => false,
                    'message' => $errors['email'][0]
                ];
            }
            else {
                // Register the new user or whatever.
                $user = User::where('email', $input['email'])->get();
                if($user->count()) {
                    // We will send the password reset link to this user. Once we have attempted
                    // to send the link, we will examine the response then see the message we
                    // need to show to the user. Finally, we'll send out a proper response.
                    $response = $this->broker()->sendResetLink(
                        $this->credentials($request)

                    );
                    
                    if ($response == Password::RESET_LINK_SENT) {
                        $this->sendResetLinkResponse($request, $response);
                        $response = [
                            'status' => true,
                            'message' => 'Email sent, Please check your email'
                        ]; 
                    } else {
                        $response = [
                            'status' => false,
                            'message' => 'fail'
                        ];
                    }
                    
                } else {
                    $response = [
                        'status' => false,
                        'message' => 'This email doesn\'t belong to our system!'
                    ];
                }
            }

            return response()->json($response);
        }
    }

