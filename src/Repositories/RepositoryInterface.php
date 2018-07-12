<?php namespace Myciplnew\Olapicmedia\Repositories;

interface RepositoryInterface
{

    public function createuser(array $data);
    public function postmedia(array $data);
    public function userPostList($userId);
    public function checkOlapicMedia($olapicMediaId);
    public function checkOlapicUser($olapicUserId);
    public function getPostInfo($mediaId);
    public function createMappingUser($userId,$olapicUserid);
    public function createDefaultUser(array $data);
    public function processYoutubePost($request,$userId);
    public function processInstagram($request, $userId,$validator);
    public function processLooks($request, $userId);
    public function s3UploadFile($request,$userId,$external = false);
    public function getS3FileUrl($userId,$fileName);
    public function storePost($request);    
    public function executeGetCurl($url);
    public function createLookPost(array $data);
}