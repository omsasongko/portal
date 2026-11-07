<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContactMessageModel;
use App\Models\SettingModel;

class Messages extends BaseController
{
    protected ContactMessageModel $messageModel;

    public function __construct()
    {
        helper('menu');
        $this->messageModel = new ContactMessageModel();
    }

    private function identity(): array
    {
        $settingModel = new SettingModel();
        return $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
    }

    /**
     * Paginated list of contact messages (newest first).
     */
    public function index()
    {
        $perPage  = 20;
        $messages = $this->messageModel
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage);

        return view('admin/messages', [
            'title'    => 'Pesan',
            'identity' => $this->identity(),
            'messages' => $messages,
            'pager'    => $this->messageModel->pager,
            'unread'   => $this->messageModel->countUnread(),
        ]);
    }

    /**
     * Show a single message and mark it as read.
     */
    public function show(int $id)
    {
        $msg = $this->messageModel->find($id);
        if (! $msg) {
            return redirect()->to('admin/messages')->with('error', 'Pesan tidak ditemukan.');
        }

        // Mark as read on open.
        if (! $msg['is_read']) {
            $this->messageModel->update($id, ['is_read' => 1]);
            $msg['is_read'] = 1;
        }

        return view('admin/message_detail', [
            'title'    => 'Detail Pesan',
            'identity' => $this->identity(),
            'msg'      => $msg,
        ]);
    }

    /**
     * Toggle read/unread status.
     */
    public function toggleRead()
    {
        $id  = (int) $this->request->getPost('id');
        $msg = $this->messageModel->find($id);

        if (! $msg) {
            return redirect()->to('admin/messages')->with('error', 'Pesan tidak ditemukan.');
        }

        $newStatus = $msg['is_read'] ? 0 : 1;
        $this->messageModel->update($id, ['is_read' => $newStatus]);

        $label = $newStatus ? 'sudah dibaca' : 'belum dibaca';
        return redirect()->to('admin/messages')->with('success', "Pesan ditandai {$label}.");
    }

    /**
     * Delete a message.
     */
    public function delete()
    {
        $id  = (int) $this->request->getPost('id');
        $msg = $this->messageModel->find($id);

        if (! $msg) {
            return redirect()->to('admin/messages')->with('error', 'Pesan tidak ditemukan.');
        }

        $this->messageModel->delete($id);

        return redirect()->to('admin/messages')->with('success', 'Pesan berhasil dihapus.');
    }
}
