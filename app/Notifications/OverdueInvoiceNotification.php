<?php

namespace App\Notifications;

use App\Models\SalesInvoice;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OverdueInvoiceNotification extends Notification
{
    public function __construct(public SalesInvoice $invoice) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ فاتورة متأخرة: {$this->invoice->invoice_number}")
            ->greeting('السلام عليكم')
            ->line("الفاتورة رقم {$this->invoice->invoice_number} للعميل {$this->invoice->clientCompany->name}")
            ->line("المبلغ المستحق: {$this->invoice->remainingAmount()} ر.س")
            ->line("تاريخ الاستحقاق: {$this->invoice->due_date->toDateString()}")
            ->action('عرض الفاتورة', url("/sales-invoices/{$this->invoice->id}"))
            ->salutation('نظام إدارة الحراسات الأمنية');
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'client_name' => $this->invoice->clientCompany->name,
            'amount' => $this->invoice->remainingAmount(),
            'due_date' => $this->invoice->due_date->toDateString(),
        ];
    }
}
