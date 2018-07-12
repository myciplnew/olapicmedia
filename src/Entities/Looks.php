<?php

namespace Myciplnew\Olapicmedia\Entities;

use Illuminate\Database\Eloquent\Model;

class Looks extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','user_id', 'description','products','social_handle'
    ];
}
