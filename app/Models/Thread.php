<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $fillable = ['user_id','title'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
