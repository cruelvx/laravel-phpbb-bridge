<?php

namespace Cruelvx\LaravelPhpbbBridge\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    public function doLogin(Request $request)
    {
        $appkey = $request->input('appkey');
        $username = $request->input('username');
        $email = $request->input('email');
        $password = $request->input('password');
        if ($appkey !== config('laravel-phpbb-bridge.appkey')) {
            return response()->json(['code' => '400', 'msg' => 'Invalid API Key', 'data' => []]);
        }
        if ($data = $this->_validateCredentials($email, $password)) {
            return response()->json(['code' => '200', 'msg' => 'success', 'data' => $data]);
        }

        return response()->json(['code' => '400', 'msg' => 'Invalid username or password', 'data' => []]);
    }

    public function getSession()
    {
        if (config('laravel-phpbb-bridge.client_auth') && Auth::client()->check()) {
            $result = [
                'id' => Auth::client()->user()['id'],
                'username' => Auth::client()->user()[config('laravel-phpbb-bridge.user_model.username_column')],
                'email' => Auth::client()->user()[config('laravel-phpbb-bridge.user_model.email_column')]
            ];
        } elseif (!config('laravel-phpbb-bridge.client_auth') && Auth::check()) {
            $userName = Auth::user()[config('laravel-phpbb-bridge.user_model.username_column')];
            $userId = Auth::user()->id;
            $userName = $userName ?? 'user_' . $userId;
            $result = [
                'id' => $userId,
                'username' => $userName,
                'email' => Auth::user()[config('laravel-phpbb-bridge.user_model.email_column')]
            ];
        } else {
            $result = [];
        }
        return response()->json(['code' => '200', 'data' => $result]);
    }

    public function doLogout()
    {
        if (config('laravel-phpbb-bridge.client_auth') && Auth::client()->check()) {
            Auth::client()->logout();
        } elseif (!config('laravel-phpbb-bridge.client_auth') && Auth::check()) {
            Auth::logout();
        }
    }

    private function _validateCredentials($email, $password)
    {
        $email = trim($email);
        $password = trim($password);
        if (config('laravel-phpbb-bridge.client_auth') && Auth::client()->attempt(
                [
                    config('laravel-phpbb-bridge.user_model.email_column') => $email,
                    config('laravel-phpbb-bridge.user_model.password_column') => $password
                ]
            )
            || (!config('laravel-phpbb-bridge.client_auth') && Auth::attempt(
                    [
                        config('laravel-phpbb-bridge.user_model.email_column') => $email,
                        config('laravel-phpbb-bridge.user_model.password_column') => $password
                    ]
                ))
        ) {
            return (config('laravel-phpbb-bridge.client_auth')) ? Auth::client()->user() : Auth::user();
        }

        return false;
    }
}
