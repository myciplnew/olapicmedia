<?php

return [
    'name' => 'Olapicmedia',
    'upload_type' => '3', //1 - Internal storage, 2 - External storage, 3 - S3 storage
    's3_upload_folder' => '',
    'supportedMimetypeList' => array("device/jpeg","device/jpg","device/png","device/gif","instagram/jpg","instagram/jpeg"),
    'imageAllowed' => array('gif', 'png', 'jpg','jpeg'),
    'videoAllowed' => array('mp4'),
    'validateCronMimeTypes' => array("instagram/jpeg","instagram/jpg","instagram/png","instagram/gif","instagram/mp4","youtube/mp4"),
    'olapic_stream_id' => env('OLAPIC_STREAM_ID'),
    'olapic_url' => env('OLAPIC_URL'),
    'olapic_api_key' => env('OLAPIC_API_KEY'),
    'olapic_customer_id' => env('OLAPIC_CUSTOMER_ID'),
    'default_full_name' => env('default_full_name'),
    'default_user_name' => env('default_user_name'),
    'default_user_email' => env('default_user_email'),
    'default_user_password' => env('default_user_password'),
    'olapic_skip_stream_key' => env('OLAPIC_SKIP_STREAM_KEY'),
];
