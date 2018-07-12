<?php namespace Myciplnew\Olapicmedia\Repositories;

interface CronInterface
{
    public function getCronMedia();
    public function processCronMedia($value);
    public function checkExternalStorage($value);
    public function updateStatus($mediaResult,$value,$file);
}