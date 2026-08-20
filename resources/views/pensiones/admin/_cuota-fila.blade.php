@php
    $concepto = $valores['concepto'] ?? ($cuota->concepto ?? '');
    $mes = $valores['mes'] ?? ($cuota->mes ?? '');
    $fechaVencimiento = $valores['fecha_vencimiento'] ?? ($cuota && $cuota->fecha_vencimiento ? $cuota->fecha_vencimiento->format('Y-m-d') : '');
    $monto = $valores['monto'] ?? ($cuota ? number_format($cuota->monto / 100, 2, '.', '') : '');
    $fechaMin = $fechaMin ?? '';
    $fechaMax = $fechaMax ?? '';
@endphp
<tr>
    <td>
        <input type="text" name="cuotas[{{ $index }}][concepto]" class="form-control form-control-sm"
               placeholder="Ej: Pensión marzo" value="{{ old('cuotas.'.$index.'.concepto', $concepto) }}" required>
    </td>
    <td>
        <select name="cuotas[{{ $index }}][mes]" class="form-select form-select-sm">
            <option value="">--</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected((string) $mes === (string) $m)>{{ $m }}</option>
            @endfor
        </select>
    </td>
    <td>
        <input type="date" name="cuotas[{{ $index }}][fecha_vencimiento]" class="form-control form-control-sm"
               value="{{ old('cuotas.'.$index.'.fecha_vencimiento', $fechaVencimiento) }}" min="{{ $fechaMin }}" max="{{ $fechaMax }}" required>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">S/</span>
            <input type="number" step="0.01" min="0.01" name="cuotas[{{ $index }}][monto]" class="form-control"
                   value="{{ old('cuotas.'.$index.'.monto', $monto) }}" required>
        </div>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>