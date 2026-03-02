<?php

namespace App\Models;
use App\Models\Message;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $fillable = ['user_id','title', 'is_pin'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
