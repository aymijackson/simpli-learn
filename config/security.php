<?php

return [

    /*
    | Workspace owners and platform admins must turn on two-step login before
    | using the back office (App\Http\Middleware\RequireTwoFactor). Learners
    | and members are never forced.
    */
    'require_two_factor' => (bool) env('REQUIRE_TWO_FACTOR', true),

    /*
    | Content-Security-Policy (App\Http\Middleware\SecurityHeaders). Sent as
    | report-only until CSP_ENFORCE=true; violations are logged to
    | storage/logs/csp.log.
    */
    'csp' => [
        'enforce' => (bool) env('CSP_ENFORCE', false),
    ],

];
