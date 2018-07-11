<?php

namespace Myciplnew\Olapicmedia;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class OlapicmediaController extends Controller
{

    public function index($timezone)
    {
        $current_time = ($timezone)
            ? Carbon::now(str_replace('-', '/', $timezone))
            : Carbon::now();
        return view('myciplnew.olapicmedia.time', compact('current_time'));
    }

}
