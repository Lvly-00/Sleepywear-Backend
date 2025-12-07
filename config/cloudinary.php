<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | The CLOUDINARY_URL is the main setting that contains your API Key,
    | Secret, and Cloud Name.
    |
    */

    'cloud_url' => env('CLOUDINARY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Upload Preset
    |--------------------------------------------------------------------------
    |
    | Optional: If you use unsigned uploads.
    |
    */

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

    /*
    |--------------------------------------------------------------------------
    | Notification URL
    |--------------------------------------------------------------------------
    |
    | Optional: For webhooks.
    |
    */

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

];
