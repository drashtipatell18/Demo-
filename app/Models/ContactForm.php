<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactForm extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'subject',
        'work_email',
        'phone_number',
        'description',
    ];
}
