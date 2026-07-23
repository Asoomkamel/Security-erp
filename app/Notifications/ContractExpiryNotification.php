<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ContractExpiryNotification extends Notification
{
    public function __construct(
        public Contract $contract,
        public int $daysRemaining
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🔔 تنبيه: عقد رقم {$this->contract->contract_number} على وشك الانتهاء")
            ->greeting('السلام عليكم')
            ->line("العقد رقم {$this->contract->contract_number} الخاص بـ {$this->contract->clientCompany->name}")
            ->line("ينتهي في {$this->contract->end_date->toDateString()} (باقي {$this->daysRemaining} يوم)")
            ->line("القيمة الشهرية: {$this->contract->calculatedTotal()} ر.س")
            ->action('عرض العقد', url("/contracts/{$this->contract->id}"))
            ->salutation('نظام إدارة الحراسات الأمنية');
    }

    public function toArray($notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'client_name' => $this->contract->clientCompany->name,
            'end_date' => $this->contract->end_date->toDateString(),
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
