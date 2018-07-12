<?php namespace Myciplnew\Olapicmedia\Repositories;

use Illuminate\Database\Eloquent\Model;
use Myciplnew\Olapicmedia\Entities\User;
use Myciplnew\Olapicmedia\Entities\Looks;
use Myciplnew\Olapicmedia\Entities\Media;
use Myciplnew\Olapicmedia\Entities\Olapic_user_mapping;
use Myciplnew\Olapicmedia\Entities\Olapic_media_mapping;
use Myciplnew\Olapicmedia\Repositories\OlapicRepository;
use Intervention\Image;
use Config;
use Storage;
use Illuminate\Support\Facades\Log;

class CronRepository implements CronInterface
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
     * Get Stream Key of Product
     * @param  Request $productId
     * @return Response
     */
    public function getStreamKeyProduct($productId)
    {
        $APIKey = Config::get('olapicmedia.olapic_api_key');
        $CustomerId = Config::get('olapicmedia.olapic_customer_id');

        $result = $ch = $this->olapicrep->executeGetCurl(Config::get('olapicmedia.olapic_url')."customers/".$CustomerId."/streams/search?version=v2.2&auth_token=".$APIKey."&tag_key=".$productId);

        return $result;
    }

    /**
     * Get Cron Media Results
     * @return Response
     */
    public function getCronMedia()
    {        
        try
        {   
            $supportedMimetypeList = Config::get('olapicmedia.supportedMimetypeList');
            $getDetails = Media::join('olapic_media_mappings','olapic_media_mappings.media_id','=','media.id')
                ->join('looks','looks.id', '=', 'media.model_id')
                ->where('olapic_media_mappings.status','=','new')
                ->where('olapic_media_mappings.type','=','upload')
                ->whereNull('media.manipulations')
                ->whereIn('media.mime_type', $supportedMimetypeList)
                ->select(
                    'olapic_media_mappings.id as mappingid',
                    'media.*',
                    'looks.id as folder_id',
                    'looks.user_id',
                    'looks.description',
                    'looks.products')
                ->get();    
        }catch (\Exception $e) {
            $getDetails = array();
            Log::error($e->getMessage());
        }

        return $getDetails;    
    }

    /**
     * Process each cron media for download
     * @param  Request $value
     */
    public function processCronMedia($value)
    {
        Olapic_media_mapping::where('id',$value->id)
            ->update([
                'status' => 'inprogress'
            ]);
        //Media Create/Check API
        //Get stream id using product id
        $productIds = explode(",",$value->products);
        $streamId = array();
        if(count($productIds)>0)
        {
            foreach ($productIds as $productKey => $productId) {
                try
                {
                    $mediaStreamKeyResult = $this->getStreamKeyProduct($productId);
                    $metaKeyCode = $mediaStreamKeyResult['metadata']['code'];
                    if ($metaKeyCode == '200')
                    {
                        $streamId[] = "/streams/".$mediaStreamKeyResult['data']['id'];
                    }
                }
                catch (\Exception $e) {
                }                   
            }            
        }
        
        //Check External storage    
        $filenames = $this->checkExternalStorage($value);    

        $files = array();
        $fields = array('caption' => $value->description);
        if(count($streamId)>0)
        {
            $i = 0;
            foreach($streamId as $newstreamid)
            {
                $fields['stream_uri['.$i.']'] = $newstreamid;    
                $i++;
            }            
        }

        foreach ($filenames as $f)
        {
            $files['file'] = file_get_contents($f);
        }

        $checkUserInfo = Olapic_user_mapping::where('user_id', $value->user_id)->first();
        $data = array('olapicUserid'=>$checkUserInfo->olapic_user_id ,'fields'=>$fields,'files'=>$files);

        $mediaResult = $this->olapicrep->postmedia($data);
        
        //Update Status
        $file = $filenames[0];
        $this->updateStatus($mediaResult,$value,$file);
    }

    /**
     * Check External Storage
     * @param  Request $value
     * @return Response
     */
    public function checkExternalStorage($value)
    {
        if(Config::get('olapicmedia.upload_type') == 2 || Config::get('olapicmedia.upload_type') == 3)
        {
            if (!file_exists('storage/app/tempImages')) {
                mkdir('storage/app/tempImages', 0777, true);
            }

            try {
                $url = $value->file_name;
                $s3url = false;
                $checkUserInfo = User::where('id', $value->user_id)->first();
                //If S3 storage
                if(Config::get('olapicmedia.upload_type') == 3 && !filter_var($url, FILTER_VALIDATE_URL) === true)
                {
                    $url = $this->olapicrep->getS3FileUrl($checkUserInfo->username,$value->file_name);
                    $s3url = true;                                        
                }
                $filenames = basename($url);
                $rmFile = explode(".", $filenames);
                $fileName = uniqid() . time() . '.' . $rmFile[1];
                $destinationPath = public_path('..\storage\app\tempImages\\');
                $download = \Image::make($url)->save($destinationPath . $fileName);

                if($s3url === false)
                {
                    $rmFile = explode(".", $fileName);
                    $request['local_media_path'] = $fileName;   
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $destinationPath . $fileName);
                    $mime_main_type = explode("/", $mime_type);
                    $device_mime_type = "device/".$mime_main_type[1];                 
                    $s3FileUrl = $this->olapicrep->s3UploadFile($request,$checkUserInfo->username,true);
                    Media::where('id', $value->id)->update([
                        'file_name' => $fileName,
                        'name' => $rmFile[0],
                        'mime_type' => $device_mime_type
                    ]);
                    $fileName = basename($s3FileUrl);
                }

                $filenames = array($destinationPath . $fileName);
            }
            catch (\Exception $e) {
                $errorArray = array(
                    "code" => 404,
                    "message" => "Unable to init from given url ",
                );
                Olapic_media_mapping::where('id', $value->mappingid)->update([
                    'status' => 'error',
                    'message' => json_encode($errorArray)
                ]);
            }
        }
        else
        {                
            $fileName = $value->file_name;
            $destinationPath = public_path('..\storage\app\\'.$value->folder_id);

            $filenames = array($destinationPath . '\\' . $fileName);
        }

        return $filenames;
    }

    /**
     * Update status
     * @param  Request $mediaResult, $value
     */
    public function updateStatus($mediaResult,$value,$file)
    {
        if($mediaResult['status']=='success')
        {
            $metaCode = $mediaResult['data']->metadata->code;
            if ($metaCode == '200' || $metaCode == '201')
            {
                Olapic_media_mapping::where('id',  $value->mappingid)->update([
                    'media_id' =>  $value->id,
                    'status' => 'success',
                    'message' => json_encode($mediaResult['data']->metadata),
                    'olapic_media_id' =>$mediaResult['data']->data->id
                ]);
                
                if(Config::get('olapicmedia.upload_type') == 2 || Config::get('olapicmedia.upload_type') == 3)
                {
                    unlink($file);
                }

            }
            else
            {
                Olapic_media_mapping::where('id', $value->mappingid)->update([
                    'status' => 'error',
                    'message' => json_encode($mediaResult['data']->metadata)
                ]);

            }
        }
        else
        {
            $errmsg = 'API Error : 413 Request Entity Too Large';
            $errorArray = array(
                "code" => 413,
                "message" => $errmsg,
            );
            Olapic_media_mapping::where('id', $value->mappingid)->update([
                'status' => 'error',
                'message' => json_encode($errorArray)
            ]);
        }
    }

    
}