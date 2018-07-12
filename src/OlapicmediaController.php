<?php

namespace Myciplnew\Olapicmedia;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Olapicmedia\Entities\User;
use Olapicmedia\Entities\Looks;
use Olapicmedia\Entities\Media;
use Olapicmedia\Entities\Olapic_user_mapping;
use Olapicmedia\Entities\Olapic_media_mapping;
use Myciplnew\Olapicmedia\Repositories\OlapicRepository;
use Myciplnew\Olapicmedia\Repositories\StreamRepository;
use Myciplnew\Olapicmedia\Repositories\CronRepository;
use Myciplnew\Olapicmedia\Repositories\ValidateCronRepository;
use Auth;
use Validator;
use Session;
use Intervention\Image;
use Config;
use Illuminate\Support\Facades\Log;

class OlapicmediaController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $olapicrep;    

    public function __construct(OlapicRepository $olapicrep) {
        $this->olapicrep = $olapicrep;
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function blukUpload(Request $request)
    {
        return $this->olapicrep->testBulkPost($request->count,$request->user_id);        
    }

}
