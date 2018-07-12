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

class ValidateCronRepository implements ValidateCronInterface
{
    /**
     * @var OlapicRepository
     */
    private $olapicrep;
    CONST MEDIA_LINK = 'https://www.instagram.com/p/{code}';

    /**
     * Class constructor
     *
     * @param OlapicRepository $olapicrep
     */
    public function __construct(OlapicRepository $olapicrep)
    {
        $this->olapicrep = $olapicrep;
        // $instagram = new Instagram(array(
        //     'apiKey'      => 'db720a11e9774ba2b68125d2f0284d19',
        //     'apiSecret'   => '3786b9d0c3324d1a9c3dae262f5ada6e',
        //     'apiCallback' => ''
        // ));
    }

    /**
     * Get Instagram/youtube media
     * @return Response
     */
    public function getActiveExternalMedia()
    {
        try
        {
            $supportedMimetypeList = Config::get('olapicmedia.validateCronMimeTypes');
            $getDetails = Media::whereIn('media.mime_type', $supportedMimetypeList)->get();       
        }catch (\Exception $e) {
            $getDetails = array();
            Log::error($e->getMessage());
        }    

        return $getDetails;    
    }

    /**
     * Process each media for verify
     * @param  Request $value
     * @return Response
     */
    public function validateMedia($value)
    {
        //Check if youtube type of media
        if($value['mime_type'] == 'youtube/mp4')
        {
            $videoId = $value['file_name'];
            $headers = get_headers('http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v='.$videoId);
            if (!strpos($headers[0], '200')) {
                Media::where('id',$value->id)
                ->update([
                    'manipulations' => '1'
                ]);
            }
        }
        else
        {   
            if($value['mime_type'] == 'instagram/jpeg')
            {                
                if(isset($value['custom_properties']))
                {
                    $properties = unserialize($value['custom_properties']);
                    if(count($properties)>0 && $properties['instagram_shortcode'] != '')
                    {
                        $media = $this->validateInstagramMediaById($properties['instagram_shortcode']); 
                        if(count($media)<=0)
                        {
                            Media::where('id',$value->id)
                            ->update([
                                'manipulations' => '1'
                            ]);
                        }
                    }
                }
            }   
        }
    }

    /**
     * Validate Instagram Media ID
     * @param  Request $code
     * @return Response
     */
    public static function validateInstagramMediaById($code)
    {
        //$code = self::getCodeFromId($id);
        $mediaLink = self::getMediaPageLink($code);
        return self::getMediaByUrl($mediaLink);
    }

    /**
     * Get Code From ID
     * @param  Request $id
     * @return Response
     */
    public static function getCodeFromId($id)
    {
        $parts = explode('_', $id);
        $id = $parts[0];
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $shortenedId = '';
        while ($id > 0) {
            $remainder = $id % 64;
            $id = ($id - $remainder) / 64;
            $shortenedId = $alphabet{$remainder} . $shortenedId;
        };
        return $shortenedId;
    }

    /**
     * Get Media Page Link
     * @param  Request $code
     * @return Response
     */
    public static function getMediaPageLink($code)
    {
        return str_replace('{code}', $code, self::MEDIA_LINK);
    }

    /**
     * Get Media By URL
     * @param  Request $mediaUrl
     * @return Response
     */
    public static function getMediaByUrl($mediaUrl)
    {
        $info = [];
        if (filter_var($mediaUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Malformed media url');
        }
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get(rtrim($mediaUrl, '/') . '/?__a=1');
            //dd($response);
            if ($response->getStatusCode() === 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                if (isset($response['graphql']['shortcode_media'])) {
                    $media = $response['graphql']['shortcode_media'];
                    $info['id'] = $media['id'];
                    $info['shortcode'] = $media['shortcode'];
                    if (isset($media['is_video']) && $media['is_video']) {
                        $info['url'] = $media['video_url'];
                    } else {
                        $info['url'] = $media['display_url'];
                    }
                }
            }
        } catch (\Exception $e) {
            //dd($e);
        }
        return $info;
    }

    
}