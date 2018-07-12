<?php

namespace Myciplnew\Olapicmedia\Entities;

use Illuminate\Database\Eloquent\Model;

class Olapic_user_mapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','user_id', 'olapic_user_id'
    ];
}
