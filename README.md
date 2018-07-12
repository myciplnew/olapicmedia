# Olapicmedia package

1) Add provider *Myciplnew\Olapicmedia\OlapicmediaServiceProvider::class* in config/app.php. 
2) Run *php artisan vendor:publish --provider="Myciplnew\Olapicmedia\OlapicmediaServiceProvider"*
3) Execute migrate command for update database tables (Skip if already tables are available). *php artisan migrate*

# Required composer installs
1) composer require intervention/image
2) composer remove league/flysystem-aws-s3-v2 (#2 and #3 not need if already filesystem running with S3-V3)
3) composer require league/flysystem-aws-s3-v3:~1.0

# Commands to execute package
1) For upload cron files run *php artisan execute:CronMedia*
2) For download files run *php artisan execute:StreamMedia*
3) For Validate youtube and instagram files *php artisan execute:ValidateMedia*