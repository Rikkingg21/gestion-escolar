<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuxiliarController extends Controller
{
    public function dashboard()
    {
        return view('auxiliar.dashboard');
    }
    public function index()
    {
        return view('auxiliar.index');
    }
}
