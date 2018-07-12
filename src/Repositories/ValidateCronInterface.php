<?php namespace Myciplnew\Olapicmedia\Repositories;

interface ValidateCronInterface
{
    public function getActiveExternalMedia();
    public function validateMedia($value);
}