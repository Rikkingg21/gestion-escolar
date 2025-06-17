<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocenteController extends Controller
{
    public function dashboard()
    {
        return view('docente.dashboard');
    }
    public function index()
    {
        return view('docente.index');
    }

    public function perfil()
    {
        return view('docente.perfil');
    }

    public function cursos()
    {
        return view('docente.cursos');
    }

    public function estudiantes()
    {
        return view('docente.estudiantes');
    }

    public function asistencia()
    {
        return view('docente.asistencia');
    }
}
