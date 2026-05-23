<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authentication Language Lines
|--------------------------------------------------------------------------
|
| Keys mirror the ErrorCode::messageKey() output (errors.<lowercased.dotted.case>)
| plus a few cross-cutting validation / not_found keys used by the global
| exception handler.
|
*/

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Used by Laravel's Password broker; mapped to status codes returned by
    // Password::reset() — must match the constant strings on Password::*.
    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed your password reset link.',
    'token' => 'This password reset token is invalid.',
    'user' => 'We can\'t find a user with that email address.',

    'otp' => [
        'sms' => [
            'body' => 'Your QBazaar verification code is :code. It expires in :minutes minutes.',
        ],
        'mail' => [
            'subject' => 'Your QBazaar verification code',
            'greeting' => 'Hello!',
            'line_code' => 'Your verification code is: :code',
            'line_expires' => 'This code will expire in :minutes minutes.',
            'line_ignore' => 'If you did not request this code, you can ignore this email.',
        ],
    ],

    'password_reset' => [
        'mail' => [
            'subject' => 'Reset your QBazaar password',
            'greeting' => 'Hello!',
            'line_intro' => 'You are receiving this email because we received a password reset request for your account.',
            'action' => 'Reset Password',
            'line_expires' => 'This password reset link will expire in :minutes minutes.',
            'line_ignore' => 'If you did not request a password reset, no further action is required.',
        ],
    ],

    'email_verification' => [
        'mail' => [
            'subject' => 'Verify your QBazaar email address',
            'greeting' => 'Hello!',
            'line_intro' => 'Please click the button below to verify your email address.',
            'action' => 'Verify Email Address',
            'line_ignore' => 'If you did not create an account, no further action is required.',
        ],
    ],
];
