<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Messages — English
|--------------------------------------------------------------------------
|
| Used by success responses that surface a `message_key` for the client to
| look up in its own translations bundle. We still send a translated
| `message` alongside, but the contract guarantees `message_key` is stable
| so SDKs can pin against it instead of fragile English copy.
|
*/

return [
    'auth' => [
        'reset_link_sent' => 'If an account exists for that email, a password-reset link has been sent.',
        'password_reset_success' => 'Your password has been reset successfully.',
        'email_verification_sent' => 'A verification link has been sent to your email address.',
        'email_already_verified' => 'Your email address is already verified.',
        'email_verified' => 'Your email address has been verified.',
    ],
];
