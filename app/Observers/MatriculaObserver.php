<?php

namespace App\Observers;

use App\Models\Matricula;
use App\Services\Pension\PensionService;

class MatriculaObserver
{
    public function __construct(private PensionService $pensionService) {}

    public function created(Matricula $matricula): void
    {
        $this->pensionService->generarCuotasParaMatricula($matricula);
    }
}
