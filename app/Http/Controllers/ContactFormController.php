<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ContactForm;

class ContactFormController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'subject'      => 'required|string|max:255',
            'work_email'   => 'required|email|max:255',
            'phone_number' => 'nullable|digits:10',
            'description'  => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ✅ Save to DB
        $contact = ContactForm::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Form submitted successfully.',
            'data'    => $contact,
        ], 201);
    }
    public function GetContact()
    {
        $contactform = ContactForm::all();
        return response()->json([
            'success' => true,
            'message' => 'Contact Form Get successfully.',
            'data'    => $contactform,
        ], 201);
    }
}
