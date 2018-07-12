<?php namespace Myciplnew\Olapicmedia\Repositories;

use Illuminate\Database\Eloquent\Model;
use Myciplnew\Olapicmedia\Entities\User;
use Myciplnew\Olapicmedia\Entities\Looks;
use Myciplnew\Olapicmedia\Entities\Media;
use Myciplnew\Olapicmedia\Entities\Olapic_media_mapping;
use Myciplnew\Olapicmedia\Repositories\OlapicRepository;
use Intervention\Image;
use Config;
use Storage;
use Illuminate\Support\Facades\Log;

class StreamRepository implements StreamInterface
{
    /**
     * @var OlapicRepository
     */
    private $olapicrep;

    /**
     * Class constructor
     *
     * @param OlapicRepository $olapicrep
     */
    public function __construct(OlapicRepository $olapicrep)
    {
        $this->olapicrep = $olapicrep;
    }

    /**
     * Get Stream
     * @param  Request $streamid
     * @return Response
     */
    public function getStream($streamid)
    {
        $APIKey = Config::get('olapicmedia.olapic_api_key');

        $result = $ch =  $this->olapicrep->executeGetCurl(Config::get('olapicmedia.olapic_url').'streams/'.$streamid.'/media/recent?version=v2.2&auth_token='.$APIKey.'&rights_given=0&include_tagged_galleries=1');

        return $result;
    }

    /**
     * Execute Stream
     * @return Response
     */
    public function executeStream()
    {
        //$streamResult = $this->getStream($request->stream_id);
        $streamId = Config::get('olapicmedia.olapic_stream_id');
        $streamResult = $this->getStream($streamId);

        //echo  json_encode($streamResult['data']['_embedded']['media']); exit;

        if($streamResult['metadata']['code'] == '200')
        {
            Log::info('Stream process started...');
            if(isset($streamResult['data']['_embedded']['media']) && count($streamResult['data']['_embedded']['media'])>0)
            {
                foreach ($streamResult['data']['_embedded']['media'] as $key => $mediavalue)
                {
                   
                   $checkMediaInfo = $this->olapicrep->checkOlapicMedia($mediavalue['id']);
                   $checkUserInfo = $this->olapicrep->checkOlapicUser($mediavalue['_embedded']['uploader']['id']);
                   if(!$checkMediaInfo)
                   {
                        Log::info('Processing media : '.$mediavalue['id']); 
                        if($checkUserInfo)
                        {
                            $media = $this->processExternalMedia($checkUserInfo->user_id,$mediavalue);
                        }
                        else
                        {
                            $checkDefaultUserInfo = User::where('email', Config::get('olapicmedia.default_user_email'))->first();
                            $data = array(
                                "email" => Config::get('olapicmedia.default_user_email'), 
                                "screen_name" => Config::get('olapicmedia.default_full_name'),
                                "username" => Config::get('olapicmedia.default_user_name'),
                                "password" => Config::get('olapicmedia.default_user_password')
                            );
                            if(!$checkDefaultUserInfo)
                            {
                                
                                $defaultUserid = $this->olapicrep->createDefaultUser($data);                                
                                $this->createMappingUser($defaultUserid, $mediavalue['_embedded']['uploader']['id']);
                                $checkDefaultUserInfo = User::where('email', Config::get('olapicmedia.default_user_email'))->first();
                            }
                            else
                            {                                
                                $this->olapicrep->createMappingUser($checkDefaultUserInfo->id, $mediavalue['_embedded']['uploader']['id']);
                            }
                            $media = $this->processExternalMedia($checkDefaultUserInfo->id,$mediavalue);
                        }
                        Log::info('Completed media : '.$mediavalue['id']);
                   }                     
                   //exit; 
                }   
            }
            Log::info('Stream process Completed');
            $messageArray = array(
                "status" => 'success',
                "message" => 'Stream downloaded Successfully'
            );
            return json_encode($messageArray,true);
        }
        
    }

    /**
     * Process external media
     * @param  Request $userId, $mediavalue
     * @return Response
     */
    public function processExternalMedia($userId,$mediavalue)
    {
        if (!file_exists('storage/app/tempImages/')) {
            mkdir('storage/app/tempImages/', 0777, true);
        }

        //Check stream key and load into looks table
        $streamKeys = array();
        if(isset($mediavalue['_embedded']['streams:all']['_embedded']['stream']) && count($mediavalue['_embedded']['streams:all']['_embedded']['stream'])>0)
        {
            foreach($mediavalue['_embedded']['streams:all']['_embedded']['stream'] as $newStream)
            {
                //Skip looks key
                if($newStream['tag_based_key'] != Config::get('olapicmedia.olapic_skip_stream_key'))
                {
                    $streamKeys[] = $newStream['tag_based_key'];    
                }                
            }
        }
        $streamKeys = implode(",", $streamKeys);

        //Check scoial connections with instagram for social handle
        $stream_social_handle = '';
        if(isset($mediavalue['_embedded']['uploader']['social_connections']['instagram']) && count($mediavalue['_embedded']['uploader']['social_connections']['instagram'])>0)
        {
            $stream_social_handle = $mediavalue['_embedded']['uploader']['social_connections']['instagram']['username'];
        }

        try {
            //echo "<pre>"; print_r($mediavalue['images']); exit;
            $url = $mediavalue['images']['normal'];
            $filenames = basename($url);
            $rmFile = explode(".", $filenames);
            $fileName = uniqid() . time() . '.' . $rmFile[1];
            $destinationPath = public_path('..\storage\app\tempImages\\');
            $download = \Image::make($url)->save($destinationPath. $fileName);
            $request['local_media_path'] = $fileName;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $destinationPath . $fileName);
            $mime_main_type = explode("/", $mime_type);
            $device_mime_type = "device/".$mime_main_type[1];

            $rmFile = explode(".", $fileName);
            $checkUserInfo = User::where('id', $userId)->first();
            $s3FileUrl = $this->olapicrep->s3UploadFile($request,$checkUserInfo->username,true);
            $fileName = basename($s3FileUrl);
            unlink($destinationPath . $fileName);
        }
        catch (\Exception $e) {
            //continue;
            return;
        }

        $updata = array(
            'userId' => $userId, 
            'mime_main_type' => $mime_main_type,
            'device_mime_type' => $device_mime_type, 
            'post_description' => $mediavalue['caption'], 
            'fileName' => $fileName,
            'post_type' => 'looks',
            'products' => $streamKeys,
            'social_handle' => $stream_social_handle,
            'olapic_media_id' => $mediavalue['id'],
            'status' => 'success',
            'type' => 'download'
        );
        $this->olapicrep->createLookPost($updata);

    }
    
}