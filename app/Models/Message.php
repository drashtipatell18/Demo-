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
        if (!$this->file_path) {
            return null;
        }

        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }

        // Otherwise treat as local storage file
        return asset('storage/' . $this->file_path);
    }

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
