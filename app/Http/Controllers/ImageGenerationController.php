<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Http\Request;

class ImageGenerationController extends Controller
{
    public function __construct()
    {
        //
    }

    public function dialog(Request $request)
    {
        $data = $this->validate($request, [
            'background' => 'required|string',
            'character' => 'required|string',
            'text' => 'required|string|max:120',
        ]);
    }
}
