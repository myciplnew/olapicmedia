<?php namespace Myciplnew\Olapicmedia\Repositories;

interface StreamInterface
{
    public function processExternalMedia($userId,$mediavalue);
    public function executeStream();
    public function getStream($streamid);
}