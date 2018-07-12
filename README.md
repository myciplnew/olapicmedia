# Olapicmedia package

1) Add provider *Myciplnew\Olapicmedia\OlapicmediaServiceProvider::class,* in config/app.php. 
2) Run *php artisan vendor:publish --provider="Myciplnew\Olapicmedia\OlapicmediaServiceProvider"*
3) Run *php artisan make:auth* (Skip if user tables already exists)
4) Execute migrate command for update database tables (Skip if already tables are available). *php artisan migrate*
5) Copy env.exmaple data to project .env file with valid details

# Required composer installs
1) composer require intervention/image
2) composer remove league/flysystem-aws-s3-v2 (#2 and #3 not need if already filesystem running with S3-V3)
3) composer require league/flysystem-aws-s3-v3:~1.0

# Commands to execute package
1) Register user if already not exists. http://localhost:8000/register
2) Skip #2 and #3 points if already we have data in media tables.
3) For upload sample files run the following url. Before that change config/olapicmedia.php file *olapic_bulk_up_dir* path
4) Parameters (#1 parameter no of images, #2 user id) : http://localhost:8000/blukUpload/2/1 
5) For upload cron files run *php artisan execute:CronMedia*
6) For download files run *php artisan execute:StreamMedia*
7) For Validate youtube and instagram files *php artisan execute:ValidateMedia*