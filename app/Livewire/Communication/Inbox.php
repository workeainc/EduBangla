<?php

namespace App\Livewire\Communication;

use App\Domain\Communication\Actions\MarkNoticeDeliveryRead;
use App\Domain\Communication\CommunicationAuthorizer;
use App\Models\NoticeDelivery;
use App\Models\School;
use Livewire\Component;

class Inbox extends Component
{
    public School $school;

    public string $role;

    public ?NoticeDelivery $delivery = null;

    public string $message = '';

    public function mount(School $school, string $role, ?NoticeDelivery $delivery = null): void
    {
        $this->school = $school;
        $this->role = $role;
        CommunicationAuthorizer::recipient(auth()->user(), $school->id, $role);
        if ($delivery && ($delivery->school_id !== $school->id || $delivery->user_id !== auth()->id() || $delivery->recipient_role !== $role)) {
            abort(404);
        }
        $this->delivery = $delivery;
    }

    public function markRead(int $deliveryId): void
    {
        $this->delivery = app(MarkNoticeDeliveryRead::class)->handle(auth()->user(), $this->school->id, $this->role, $deliveryId);
        $this->message = 'Notice marked as read.';
    }

    public function render()
    {
        return view('livewire.communication.inbox', ['deliveries' => NoticeDelivery::forSchool($this->school)->where('user_id', auth()->id())->where('recipient_role', $this->role)->with('notice')->latest('delivered_at')->get()]);
    }
}
