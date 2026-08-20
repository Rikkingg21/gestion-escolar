<?php

namespace App\Services\Pagos;

use App\Models\Colegio;
use Illuminate\Support\Facades\Http;

class CulqiService
{
    protected Colegio $colegio;

    public function __construct(?Colegio $colegio = null)
    {
        $this->colegio = $colegio ?? Colegio::configuracion();
    }

    public function habilitada(): bool
    {
        return $this->colegio->pasarelaHabilitada();
    }

    public function modoPrueba(): bool
    {
        return $this->colegio->culqiEnModoPrueba();
    }

    public function publicKey(): ?string
    {
        return $this->colegio->culqi_public_key;
    }

    public function secretKey(): ?string
    {
        return $this->colegio->culqi_secret_key;
    }

    public function baseUrl(): string
    {
        return 'https://api.culqi.com/v2';
    }

    /**
     * Crea un cargo (charge) contra la API v2 de Culqi.
     *
     * @param  int  $montoCentavos  Monto en céntimos (S/ 1.00 = 100). Se envía tal cual.
     * @return array Datos de la respuesta de Culqi
     *
     * @throws \Illuminate\Http\Client\RequestException Si Culqi rechaza el cargo
     */
    public function crearCargo(int $montoCentavos, string $sourceId, string $email, string $descripcion): array
    {
        if ($montoCentavos < 100) {
            throw new \InvalidArgumentException('El monto mínimo para cobrar es S/ 1.00 (100 céntimos).');
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->secretKey(),
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl().'/charges', [
            'amount' => (string) $montoCentavos,
            'currency_code' => 'PEN',
            'email' => $email,
            'description' => $descripcion,
            'source_id' => $sourceId,
        ])->throw()->json();
    }

    /**
     * Consulta el estado de un cargo en Culqi.
     */
    public function consultarCargo(string $cargoId): array
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->secretKey(),
        ])->get($this->baseUrl().'/charges/'.$cargoId)->throw()->json();
    }
}
