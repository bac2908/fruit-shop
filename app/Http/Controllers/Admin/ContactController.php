<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Notifications\ContactReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ContactMessage::statuses())],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = ContactMessage::query()->with('handler')->latest();

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        $counts = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.contacts.index', [
            'messages' => $query->paginate(25)->withQueryString(),
            'counts' => $counts,
            'selectedStatus' => $validated['status'] ?? null,
            'search' => $validated['q'] ?? '',
        ]);
    }

    public function show(Request $request, ContactMessage $contactMessage)
    {
        if ($contactMessage->status === ContactMessage::STATUS_NEW) {
            $contactMessage->forceFill([
                'status' => ContactMessage::STATUS_READ,
                'handled_by' => $request->user()->id,
                'read_at' => now(),
            ])->save();
        }

        return view('admin.contacts.show', compact('contactMessage'));
    }

    public function update(Request $request, ContactMessage $contactMessage)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::statuses())],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $payload = [
            'status' => $validated['status'],
            'admin_note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
            'handled_by' => $request->user()->id,
        ];

        if (!$contactMessage->read_at) {
            $payload['read_at'] = now();
        }

        $contactMessage->forceFill($payload)->save();

        return back()->with('success', 'Đã cập nhật yêu cầu liên hệ.');
    }

    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $validated = $request->validate([
            'reply_message' => ['required', 'string', 'min:10', 'max:3000', 'not_regex:/[<>]/'],
        ]);
        $reply = trim($validated['reply_message']);

        Notification::route('mail', [$contactMessage->email => $contactMessage->name])
            ->notify(new ContactReplyNotification($contactMessage, $reply));

        $contactMessage->forceFill([
            'status' => ContactMessage::STATUS_REPLIED,
            'handled_by' => $request->user()->id,
            'reply_message' => $reply,
            'read_at' => $contactMessage->read_at ?: now(),
            'replied_at' => now(),
        ])->save();

        return back()->with('success', 'Đã gửi phản hồi đến ' . $contactMessage->email . '.');
    }
}
