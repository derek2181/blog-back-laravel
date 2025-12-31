<?php

return [
    'secret' => env('JWT_SECRET', 'changeme'),
    'expires_in' => env('JWT_EXPIRES_IN', '3600s'),
];
