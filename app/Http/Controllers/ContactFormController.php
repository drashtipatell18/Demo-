<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        // Optional: Save to DB
        // ContactForm::create($validator->validated());

        // Optional: Send email
        // Mail::to('admin@example.com')->send(new ContactFormMail($validator->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Form submitted successfully.',
            'data'    => $validator->validated(),
        ], 201);
    }
}
