<?php 
namespace Myciplnew\Olapicmedia\Repositories;

use Illuminate\Database\Eloquent\Model;
use Myciplnew\Olapicmedia\Entities\User;
use Myciplnew\Olapicmedia\Entities\Looks;
use Myciplnew\Olapicmedia\Entities\Media;
use Myciplnew\Olapicmedia\Entities\Olapic_user_mapping;
use Myciplnew\Olapicmedia\Entities\Olapic_media_mapping;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use Intervention\Image;
use Session;
use Config;
use Storage;
use Illuminate\Support\Facades\Log;
ini_set('max_execution_time', 3000); //3000 seconds = 50 minutes

class OlapicRepository implements RepositoryInterface
{    
    /**
     * Create a new user record
     * @param  Request $data
     * @return Response
     */
    public function createuser(array $data)
    {
        $data_string = json_encode($data);
        $APIKey = Config::get('olapicmedia.olapic_api_key');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Config::get('olapicmedia.olapic_url').'users?version=v2.2&auth_token=' . $APIKey);
        curl_setopt($ch, CURLOPT_POST, 1); // set post data to true
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);   // post data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($json);

        return $result;
    }

    /**
     * Post media to olapic
     * @param  Request $data
     * @return Response
     */
    public function postmedia(array $data)
    {
        //print_r($data);exit;
        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;
        $APIKey = Config::get('olapicmedia.olapic_api_key');
        $post_data = $this->build_data_files($boundary, $data['fields'], $data['files']);
        try {
            $ch = curl_init();


            curl_setopt_array($ch, array(
                CURLOPT_URL => Config::get('olapicmedia.olapic_url').'users/' . $data['olapicUserid'] . '/media?version=v2.2&auth_token=' . $APIKey,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: multipart/form-data; boundary=" . $delimiter,
                    "Content-Length: " . strlen($post_data)
                ),));

            $json = curl_exec($ch);
            curl_close($ch);
            $mediaResult = json_decode($json);
            $errorArray = array(
                "status" => 'success',
                "data"=>$mediaResult
            );
            }catch (\Exception $e) {
                         $errorArray = array(
                            "status" => 'error',
                            "message" => 'API Error : 413 Request Entity Too Large',
                            "data"=>array()
                        );
            }
            return $errorArray;
    }

    /**
     * Create format for upload data
     * @param  Request $boundary, $fields, $files
     * @return Response
     */
    public function build_data_files($boundary, $fields, $files) {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                    . $content . $eol;
        }


        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
                    //. 'Content-Type: image/png'.$eol
                    . 'Content-Transfer-Encoding: binary' . $eol
            ;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;


        return $data;
    }

    /**
     * Execute Get Curl
     * @param  Request $url
     * @return Response
     */
    public function executeGetCurl($url)
    {
        $ch = curl_init();                
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($json,true);

        return $result;
    }

    /**
     * Get user post list
     * @param  Request $userId
     * @return Response
     */
    public function userPostList($userId)
    {
        try{
            $postList = Looks::leftJoin('media', function($join) {
                    $join->on('looks.id', '=', 'media.model_id');
                })->where('user_id', $userId)->get();    
        }catch (\Exception $e) {
            $postList = array();
            Log::error($e->getMessage());
        }        

        return $postList;
    }

    /**
     * Get the olapic media
     * @param  Request $olapicMediaId
     * @return Response
     */
    public function checkOlapicMedia($olapicMediaId)
    {
        try{
            $result = Olapic_media_mapping::where('olapic_media_id', $olapicMediaId)->first();
        }catch (\Exception $e) {
            $result = array();
            Log::error($e->getMessage());
        }

        return $result;
    }

    /**
     * Get the olapic user
     * @param  Request $olapicUserId
     * @return Response
     */
    public function checkOlapicUser($olapicUserId)
    {
        try{
            $result = Olapic_user_mapping::where('olapic_user_id', $olapicUserId)->first();
        }catch (\Exception $e) {
            $result = array();
            Log::error($e->getMessage());
        }

        return $result;
    }

    /**
     * Get the post info
     * @param  Request $mediaId
     * @return Response
     */
    public function getPostInfo($mediaId)
    {
        try{
            $postInfo = Media::where('media.id', $mediaId)
                 ->leftJoin('looks','looks.id', '=', 'media.model_id')
                 ->leftJoin('users','users.id', '=', 'looks.user_id')
                 ->select('media.*','looks.description','looks.id as next_id','looks.user_id','users.username')
                 ->first();
        }catch (\Exception $e) {
            $postInfo = array();
            Log::error($e->getMessage());
        }       

        return $postInfo;
    }

    /**
     * Create mapping user
     * @param  Request $userId, $olapicUserid
     * @return Response
     */
    public function createMappingUser($userId,$olapicUserid)
    {
        try{
            $userMapId = Olapic_user_mapping::create([
                            'user_id' => $userId,
                            'olapic_user_id' => $olapicUserid
                        ])->id;
        }catch (\Exception $e) {
            $userMapId = 0;
            Log::error($e->getMessage());
        }

        return $userMapId;
    }

    /**
     * Create default user
     * @param  Request $data
     * @return Response
     */
    public function createDefaultUser(array $data)
    {
        try{
            $userId = User::create([
                            'name' => $data['screen_name'],
                            'email' => $data['email'],
                            'username' => $data['username'],
                            'password' => Hash::make($data['password']),
                        ])->id;
        }catch (\Exception $e) {
            $userId = 0;
            Log::error($e->getMessage());
        }

        return $userId;
    }

    /**
     * Process Youtube Post
     * @param  Request $request ,$userId
     */
    public function processYoutubePost($request,$userId)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $request['post_youtube'], $match);
        $youtube_id = $match[1];

        $postId = Looks::create([
                    'user_id' => $userId,
                    'description' => $request['post_description']
                ])->id;

        $mediaId = Media::create([
                    'model_id' => $postId,
                    'model_type' => "App\\Models\\Look",
                    'name' => 'Youtube video',
                    'file_name' => $youtube_id,
                    'collection_name' => $request['post_type'],
                    'mime_type' => "youtube/mp4"
                ])->id;

        Session::flash('message', ' Youtube video posted succesfully ');
        Session::flash('alert-class', 'alert-success');
    }

    /**
     * Process Instagram Post
     * @param  Request $request ,$userId, $validator
     * @return Response
     */
    public function processInstagram($request, $userId,$validator)
    {
        if (!file_exists('storage/app/tempImages/')) {
            mkdir('storage/app/tempImages/', 0777, true);
        }

        $url = $request['post_instagram'];

        //$url = 'https://i.stack.imgur.com/koFpQ.png';
        try {
            $filenames = basename($url);
            $rmFile = explode(".", $filenames);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileName = uniqid() . time() . '.' . $rmFile[1];
            $destinationPath = public_path('..\storage\app\tempImages\\');
            if($rmFile[1] != 'mp4')
            {                
                $download = \Image::make($url)->save($destinationPath. $fileName);    
            }
            else
            {
                copy($url, $destinationPath. $fileName);
            }
            $mime_type = finfo_file($finfo, $destinationPath . $fileName);
            $mime_main_type = explode("/", $mime_type);
            $device_mime_type = "device/".$mime_main_type[1];

            $request['local_media_path'] = $fileName;
            $checkUserInfo = User::where('id', $userId)->first();
            $s3FileUrl = $this->s3UploadFile($request,$checkUserInfo->username,true);
            $fileName = basename($s3FileUrl);
            unlink($destinationPath . $fileName);
        }
        catch (\Exception $e) {
            Session::flash('message', ' Unable to download the file');
            Session::flash('alert-class', 'alert-danger');
            return redirect('/olapicmedia/create/' . $request['post_type'])->withErrors($validator)->withInput();
        }

        $updata = array(
            'userId' => $userId, 
            'mime_main_type' => $mime_main_type,
            'device_mime_type' => $device_mime_type, 
            'post_description' => $request['post_description'], 
            'fileName' => $fileName,
            'post_type' => $request['post_type'],
            'products' => '',
            'social_handle' => '',
            'olapic_media_id' => '0',
            'status' => 'new',
            'type' => 'upload'
        );
        $this->createLookPost($updata);

        Session::flash('message', ' Instagram post uploaded Succesfully ');
        Session::flash('alert-class', 'alert-success');
    }

    /**
     * Process Image/Video Post
     * @param  Request $request ,$userId
     * @return Response
     */
    public function processLooks($request, $userId)
    {
        $postFile = $request->file('post_media_path');
        $fileExt = $postFile->getClientOriginalExtension();
        $imageAllowed = Config::get('olapicmedia.imageAllowed');
        $videoAllowed = Config::get('olapicmedia.videoAllowed');
        if (in_array($fileExt, $imageAllowed) || in_array($fileExt, $videoAllowed)) {
            //Create folder for user id and upload files         
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $postFile->getPathName());
            $mime_main_type = explode("/", $mime_type);
            $device_mime_type = "device/".$mime_main_type[1];

            $checkUserInfo = User::where('id', $userId)->first();
            $s3FileUrl = $this->s3UploadFile($request,$checkUserInfo->username);
            $fileName = basename($s3FileUrl);

            $updata = array(
                'userId' => $userId, 
                'mime_main_type' => $mime_main_type,
                'device_mime_type' => $device_mime_type, 
                'post_description' => $request['post_description'], 
                'fileName' => $fileName,
                'post_type' => $request['post_type'],
                'products' => '',
                'social_handle' => '',
                'olapic_media_id' => '0',
                'status' => 'new',
                'type' => 'upload'
            );
            $this->createLookPost($updata);

        }

    }

    /**
     * S3 Upload File
     * @param  Request $request ,$userId, $external
     * @return Response
     */
    public function s3UploadFile ($request,$userId,$external = false) {
        if($external == true)
        {
            $tempdestinationPath = public_path('..\storage\app\tempImages\\');
            $postFile = $tempdestinationPath.$request['local_media_path'];
            $rmFile = explode(".", $request['local_media_path']);
            $ext = $rmFile[1];
            $fileName = $request['local_media_path'];
        }
        else
        {
            $postFile = $request->file('post_media_path');
            $ext = $postFile->getClientOriginalExtension();
            $fileName = uniqid() . time() . '.' . $ext;
        }
        
        $destinationPath = Config::get('olapicmedia.s3_upload_folder').$userId."/";
        Storage::disk('s3')->put($destinationPath.$fileName, file_get_contents($postFile));
        Storage::disk('s3')->setVisibility($destinationPath.$fileName, 'public');

        $url = Storage::disk('s3')->url($destinationPath.$fileName);
        return $url;
    }

    /**
     * Get S3 File url
     * @param  Request $userId, $fileName
     * @return Response
     */
    public function getS3FileUrl ($userId,$fileName) {
        $destinationPath = Config::get('olapicmedia.s3_upload_folder').$userId."/";
        $url = Storage::disk('s3')->url($destinationPath.$fileName);
        return $url;
    }

    /**
     * Store Post
     * @param  Request $request
     * @return Response
     */
    public function storePost($request)
    {
        $validator = Validator::make($request->all(), [
                    'post_type' => 'required',
        ]);

        if ($validator->fails())
        {
            Session::flash('message', 'Please fill the required fields');
            Session::flash('alert-class', 'alert-danger');
            return redirect('/olapicmedia/create/' . $request['post_type'])->withErrors($validator)->withInput();
        }

        //User Create/Check API
        $userName = Auth::user()->name;
        $userEmail = Auth::user()->email;
        $data = array("email" => $userEmail, "screen_name" => $userName);

        //Check user in olapic user mapping
        $userId = Auth::user()->id;
        $checkUserInfo = Olapic_user_mapping::where('user_id', $userId)->first();
        if(!$checkUserInfo)
        {
            $result = $this->createuser($data);
            $metaCode = $result->metadata->code;
            if ($metaCode == '200' || $metaCode == '201')
            {
                $olapicUserid = $result->data->id;                
                $this->createMappingUser($userId, $olapicUserid);
            }
            else
            {
                Session::flash('message', $result->data->message);
                Session::flash('alert-class', 'alert-danger');
                return redirect('/olapicmedia/create/' . $request['post_type'])->withErrors($validator)->withInput();
            }
        }
        else
        {
            $olapicUserid = $checkUserInfo->olapic_user_id;
        }

        if ($request['post_type'] == 'youtube')
        {
            if (isset($request['post_youtube']) && $request['post_youtube'] != '')
            {
                $this->processYoutubePost($request, $userId);
                return redirect('/olapicmedia/mypost');              
            }
            else
            {
                Session::flash('message', 'Please upload valid video post');
                Session::flash('alert-class', 'alert-danger');
                return redirect('/olapicmedia/create/' . $request['post_type'])->withErrors($validator)->withInput();
            }
        }
        elseif($request['post_type'] == 'instagram')
        {
            $url = $request['post_instagram'];                

            if($url)
            {
                $this->processInstagram($request, $userId,$validator);
                return redirect('/olapicmedia/mypost'); 
            }
            else
            {
                Session::flash('message', ' Failed to parse url');
                Session::flash('alert-class', 'alert-danger');
                return redirect('/olapicmedia/mypost');
            }               
        }
        elseif($request['post_type'] == 'looks')
        {
            //Post file and check type
            $fileName = '';
            $fileType = '0';
            $mime_type = '';

            if ($request->post_media_path)
            {
                $this->processLooks($request, $userId);
                Session::flash('message', 'Post Created Successfully');
                Session::flash('alert-class', 'alert-success');
                return redirect('/olapicmedia/mypost');
            }
            else
            {
                Session::flash('message', 'Please upload valid files');
                Session::flash('alert-class', 'alert-danger');
                return redirect('/olapicmedia/create/' . $request['post_type'])->withErrors($validator)->withInput();
            }

        }
    }

    /**
     * Create looks and relevant table update
     * @param  Request $data
     * @return Response
     */
    public function createLookPost(array $data)
    {
        $postId = Looks::create([
                    'user_id' => $data['userId'],
                    'description' => $data['post_description'],
                    'products' => $data['products'],
                    'social_handle' => $data['social_handle']
                ])->id;

        $rmFile = explode(".", $data['fileName']);

        $mediaId = Media::create([
                    'model_id' => $postId,
                    'model_type' => "App\\Models\\Look",
                    'name' => $rmFile[0],
                    'file_name' => $data['fileName'],
                    'collection_name' => $data['post_type'],
                    'mime_type' => $data['device_mime_type']
                ])->id;

        if($data['mime_main_type'][0] == 'image')
        {
            $Olapic_media_mapping = Olapic_media_mapping::create([
                        'media_id' => $mediaId,
                        'type' => $data['type'],
                        'status' => $data['status'],
                        'message' => '',
                        'olapic_media_id' => $data['olapic_media_id']
            ]);
        }
    }

    /**
     * Test Bulk Post
     * @param  Request $request ,$userId
     * @return Response
     */
    public function testBulkPost($count,$userId)
    {
        //Check user in olapic user mapping
        //$userId = Auth::user()->id;
        $checkUserInfo = Olapic_user_mapping::where('user_id', $userId)->first();
        $olapicUserid = 0;
        if(!$checkUserInfo)
        {
            $userInfo = User::where('id', $userId)->first();
            $userName = $userInfo->name;
            $userEmail = $userInfo->email;
            $data = array("email" => $userEmail, "screen_name" => $userName);
            $result = $this->createuser($data);
            $metaCode = $result->metadata->code;
            if ($metaCode == '200' || $metaCode == '201')
            {
                $olapicUserid = $result->data->id;                
                $this->createMappingUser($userId, $olapicUserid);
            }
        }
        else
        {
            $olapicUserid = $checkUserInfo->olapic_user_id;
        }

        //Load images from directory
        $dir = "../../../../Backup-CIPL0107/xampp/htdocs/ummahstars/assets/categoryImages/*.jpg";
        $images = glob( $dir );
        $i = 1;
        foreach($images as $newimage)
        {
            Log::info('Bulk media started : '.$i);
            //echo $newimage."<br>";
            $postFile = $newimage;
            $fileName = basename($postFile);

            $request['local_media_path'] = $fileName;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $postFile);
            $mime_main_type = explode("/", $mime_type);
            $device_mime_type = "device/".$mime_main_type[1];

            $rmFile = explode(".", $fileName);
            $checkUserInfo = User::where('id', $userId)->first();

            $postFile = $postFile;            
            $fileName = str_replace("+", "", $fileName);
            $destinationPath = Config::get('olapicmedia.s3_upload_folder').$checkUserInfo->username."/";
            Storage::disk('s3')->put($destinationPath.$fileName, file_get_contents($postFile));
            Storage::disk('s3')->setVisibility($destinationPath.$fileName, 'public');
            $s3FileUrl = Storage::disk('s3')->url($destinationPath.$fileName);
            $fileName = basename($s3FileUrl);

            $updata = array(
                'userId' => $userId, 
                'mime_main_type' => $mime_main_type,
                'device_mime_type' => $device_mime_type, 
                'post_description' => "Bulk upload", 
                'fileName' => $fileName,
                'post_type' => "looks",
                'products' => '',
                'social_handle' => '',
                'olapic_media_id' => '0',
                'status' => 'new',
                'type' => 'upload'
            );
            $this->createLookPost($updata);
            Log::info('Bulk media completed : '.$i);

            $i++;
            if($i > $count)
            {
                break;
            }
        }       

    }
    
}