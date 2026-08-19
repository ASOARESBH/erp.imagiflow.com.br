<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Models\ColaboradorLocalizacao;

class MobileLocationController extends MobileController
{
    public function store(): void
    {
        $input = $this->input();
        $latitude = filter_var($input['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($input['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
        $contexto = $this->cleanString($input['contexto'] ?? '', 40);

        if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->error('Coordenadas de localização inválidas.', [
                'latitude' => ['Informe uma latitude válida.'],
                'longitude' => ['Informe uma longitude válida.'],
            ], 422);
        }

        $locationId = (new ColaboradorLocalizacao())->create([
            'user_id' => $this->currentUserId(),
            'colaborador_id' => !empty($input['colaborador_id']) ? (int) $input['colaborador_id'] : null,
            'latitude' => number_format((float) $latitude, 7, '.', ''),
            'longitude' => number_format((float) $longitude, 7, '.', ''),
            'accuracy_meters' => isset($input['accuracy_meters']) ? min(9999.99, max(0, (float) $input['accuracy_meters'])) : null,
            'contexto' => $contexto,
            'referencia_tabela' => $this->cleanString($input['referencia_tabela'] ?? '', 60) ?: null,
            'referencia_id' => !empty($input['referencia_id']) ? (int) $input['referencia_id'] : null,
            'captured_at' => $this->normalizeCaptureDate($input['captured_at'] ?? null),
        ]);
        if (!$locationId) {
            $this->error('Não foi possível registrar a localização.', [], 500);
        }

        $this->audit('mobile_location_created', [
            'location_id' => $locationId,
            'contexto' => $contexto,
            'referencia_tabela' => $input['referencia_tabela'] ?? null,
            'referencia_id' => $input['referencia_id'] ?? null,
        ]);
        $this->success(['id' => $locationId], 'Localização registrada.', 201);
    }

    public function team(): void
    {
        $this->requirePermission('view_team_locations');
        $userId = (int) $this->query('user_id', 0);
        $days = (int) $this->query('days', 1);
        $locations = (new ColaboradorLocalizacao())->latestByTeam($userId > 0 ? $userId : null, $days);
        $this->success(['items' => $locations]);
    }

    private function normalizeCaptureDate(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return date('Y-m-d H:i:s');
        }
        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp > time() + 300 || $timestamp < strtotime('-24 hours')) {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}
