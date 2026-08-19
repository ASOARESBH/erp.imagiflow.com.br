<?php

namespace App\Controllers\Api\Mobile\V1;

use App\Models\MobileReadRepository;
use App\Models\Notificacao;

class MobileNotificationController extends MobileController
{
    public function index(): void
    {
        $this->requirePermission('view_profile');
        $pagination = $this->pagination();
        $result = (new MobileReadRepository())->notifications($this->currentUserId(), $pagination['page'], $pagination['per_page']);
        $unread = (new Notificacao())->countNaoLidas($this->currentUserId());
        $data = $this->paginated($result['items'], $result['total'], $pagination);
        $data['unread_count'] = $unread;
        $this->success($data);
    }

    public function markRead(int $id): void
    {
        $this->requirePermission('view_profile');
        $model = new Notificacao();
        $notification = $model->findByUsuario($this->currentUserId());
        $exists = false;
        foreach ($notification as $item) {
            if ((int) $item->id === $id) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $this->error('Notificação não encontrada.', [], 404);
        }
        if (!$model->marcarLida($id, $this->currentUserId())) {
            $this->error('Não foi possível atualizar a notificação.', [], 500);
        }
        $this->audit('mobile_notification_read', ['notificacao_id' => $id]);
        $this->success(['id' => $id], 'Notificação marcada como lida.');
    }
}
