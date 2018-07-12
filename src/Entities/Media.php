<?php

namespace Myciplnew\Olapicmedia\Entities;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','model_type','model_id', 'name','file_name','collection_name','mime_type'
    ];
}
