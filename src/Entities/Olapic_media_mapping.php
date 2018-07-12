<?php

namespace Myciplnew\Olapicmedia\Entities;

use Illuminate\Database\Eloquent\Model;

class Olapic_media_mapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','media_id', 'olapic_media_id' ,'type','status','message'
    ];
}
