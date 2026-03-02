<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'thread_id',
        'role',
        'message',
        'file_path',
        'file_type',
        'original_name',
        'type'
    ];
    
    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path
            ? asset('storage/' . $this->file_path)
            : null;
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
