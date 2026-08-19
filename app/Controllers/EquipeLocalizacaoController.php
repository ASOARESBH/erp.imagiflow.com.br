<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\ColaboradorLocalizacao;

class EquipeLocalizacaoController extends Controller
{
    public function index(): void
    {
        if (!Auth::can('view_team_locations')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        $days = max(1, min(365, (int) ($_GET['days'] ?? 1)));
        $userId = max(0, (int) ($_GET['user_id'] ?? 0));
        $locations = (new ColaboradorLocalizacao())->latestByTeam($userId ?: null, $days);
        $trail = $userId > 0 ? (new ColaboradorLocalizacao())->trailForUser($userId, $days) : [];

        View::render('equipe_localizacao/index', [
            'title' => 'Mapa da Equipe',
            'breadcrumb' => ['Operacional' => '/dashboard', 0 => 'Mapa da Equipe'],
            'days' => $days,
            'userId' => $userId,
            'locations' => $locations,
            'trail' => $trail,
            '_layout' => 'erp',
        ]);
    }
}
