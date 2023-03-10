<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class PaymentConfirm extends Notification
{
    use Queueable;

    private $order;
    private $sign;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->sign = string_encrypt($order->payment->id);
    }

    public function via($notifiable)
    {
        return sysConfig('payment_confirm_notification');
    }

    public function toTelegram($notifiable)
    {
        $order = $this->order;
        $goods = $this->order->goods;
        $message = sprintf("ğ äººå·¥æ¯ä»\nâââââââââââââââ\n\t\tâ¹ï¸ è´¦å·ï¼%s\n\t\tğ° éé¢ï¼%1.2f\n\t\tğ¦ ååï¼%s\n\t\t", $order->user->username, $order->amount, $goods->name ?? 'ä½é¢åå¼');
        foreach (User::role('Super Admin')->get() as $admin) {
            if (! $admin->telegram_user_id) {
                continue;
            }

            return TelegramMessage::create()
                ->to($admin->telegram_user_id)
                ->token(sysConfig('telegram_token'))
                ->content($message)
                ->button('å¦ æ±º', route('payment.notify', ['method' => 'manual', 'sign' => $this->sign, 'status' => 0]))
                ->button('ç¡® è®¤', route('payment.notify', ['method' => 'manual', 'sign' => $this->sign, 'status' => 1]));
        }

        return false;
    }

    public function toCustom($notifiable)
    {
        $order = $this->order;
        $goods = $this->order->goods;

        return [
            'title'    => 'ğ äººå·¥æ¯ä»',
            'body'     => [
                [
                    'keyname' => 'â¹ï¸ è´¦å·',
                    'value'   => $order->user->username,
                ],
                [
                    'keyname' => 'ğ° éé¢',
                    'value'   => sprintf('%1.2f', $order->amount),
                ],
                [
                    'keyname' => 'ğ¦ åå',
                    'value'   => $goods->name ?? 'ä½é¢åå¼',
                ],
            ],
            'markdown' => '- â¹ï¸ è´¦å·: '.$order->user->username.PHP_EOL.'- ğ° éé¢: '.sprintf('%1.2f', $order->amount).PHP_EOL.'- ğ¦ åå: '.($goods->name ?? 'ä½é¢åå¼'),
            'button'   => [
                route('payment.notify', ['method' => 'manual', 'sign' => $this->sign, 'status' => 0]),
                route('payment.notify', ['method' => 'manual', 'sign' => $this->sign, 'status' => 1]),
            ],
        ];
    }
}
