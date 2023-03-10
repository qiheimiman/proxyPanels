<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserOauth;
use App\Services\TelegramService;
use Exception;
use Illuminate\Http\Request;
use StdClass;

class TelegramController extends Controller
{
    protected $msg;

    public function webhook(Request $request)
    {
        $this->msg = $this->getMessage($request->input());
        if (! $this->msg) {
            return;
        }
        try {
            switch ($this->msg->message_type) {
                case 'send':
                    $this->fromSend();
                    break;
                case 'reply':
                    $this->fromReply();
                    break;
            }
        } catch (Exception $e) {
            $telegramService = new TelegramService();
            $telegramService->sendMessage($this->msg->chat_id, $e->getMessage());
        }
    }

    private function getMessage(array $data)
    {
        if (! isset($data['message'])) {
            return false;
        }
        $obj = new StdClass();
        $obj->is_private = $data['message']['chat']['type'] === 'private';
        if (! isset($data['message']['text'])) {
            return false;
        }
        $text = explode(' ', $data['message']['text']);
        $obj->command = $text[0];
        $obj->args = array_slice($text, 1);
        $obj->chat_id = $data['message']['chat']['id'];
        $obj->message_id = $data['message']['message_id'];
        $obj->message_type = ! isset($data['message']['reply_to_message']['text']) ? 'send' : 'reply';
        $obj->text = $data['message']['text'];
        if ($obj->message_type === 'reply') {
            $obj->reply_text = $data['message']['reply_to_message']['text'];
        }

        return $obj;
    }

    private function fromSend()
    {
        switch ($this->msg->command) {
            case '/bind':
                $this->bind();
                break;
            case '/traffic':
                $this->traffic();
                break;
            case '/getLatestUrl':
                $this->getLatestUrl();
                break;
            case '/unbind':
                $this->unbind();
                break;
            default:
                $this->help();
        }
    }

    private function bind()
    {
        $msg = $this->msg;
        if (! $msg->is_private) {
            return;
        }
        if (! isset($msg->args[0])) {
            abort(500, '??????????????????????????????????????????');
        }
        $user = User::whereUsername($msg->args[0])->first();
        if (! $user) {
            abort(500, '???????????????');
        }
        if ($user->telegram_user_id) {
            abort(500, '????????????????????????Telegram??????');
        }

        if (! $user->userAuths()->create(['type' => 'telegram', 'identifier' => $msg->chat_id])) {
            abort(500, '????????????');
        }
        $telegramService = new TelegramService();
        $telegramService->sendMessage($msg->chat_id, '????????????');
    }

    private function traffic()
    {
        $msg = $this->msg;
        if (! $msg->is_private) {
            return;
        }
        $telegramService = new TelegramService();
        if (! $oauth = UserOauth::query()->where([
            'type'       => 'telegram',
            'identifier' => $msg->chat_id,
        ])->first()) {
            $this->help();
            $telegramService->sendMessage($msg->chat_id, '??????????????????????????????????????????????????????', 'markdown');

            return;
        }
        $user = $oauth->user;
        $transferEnable = flowAutoShow($user->transfer_enable);
        $up = flowAutoShow($user->u);
        $down = flowAutoShow($user->d);
        $remaining = flowAutoShow($user->transfer_enable - ($user->u + $user->d));
        $text = "????????????????\n?????????????????????????????????????????????\n???????????????`{$transferEnable}`\n???????????????`{$up}`\n???????????????`{$down}`\n???????????????`{$remaining}`";
        $telegramService->sendMessage($msg->chat_id, $text, 'markdown');
    }

    private function help()
    {
        $msg = $this->msg;
        if (! $msg->is_private) {
            return;
        }
        $telegramService = new TelegramService();
        $commands = [
            '/bind ???????????? - ????????????'.sysConfig('website_name').'??????',
            '/traffic - ??????????????????',
            '/getLatestUrl - ???????????????'.sysConfig('website_name').'??????',
            '/unbind - ????????????',
        ];
        $text = implode(PHP_EOL, $commands);
        $telegramService->sendMessage($msg->chat_id, "??????????????????????????????????????????\n\n$text", 'markdown');
    }

    private function getLatestUrl()
    {
        $msg = $this->msg;
        $telegramService = new TelegramService();
        $text = sprintf(
            '%s?????????????????????%s',
            sysConfig('website_name'),
            sysConfig('website_url')
        );
        $telegramService->sendMessage($msg->chat_id, $text, 'markdown');
    }

    private function unbind()
    {
        $msg = $this->msg;
        if (! $msg->is_private) {
            return;
        }
        $user = User::with(['userAuths' => function ($query) use ($msg) {
            $query->whereType('telegram')->whereIdentifier($msg->chat_id);
        },
        ])->first();

        $telegramService = new TelegramService();
        if (! $user) {
            $this->help();
            $telegramService->sendMessage($msg->chat_id, '??????????????????????????????????????????????????????', 'markdown');

            return;
        }
        if (! $user->userAuths()->whereType('telegram')->whereIdentifier($msg->chat_id)->delete()) {
            abort(500, '????????????');
        }
        $telegramService->sendMessage($msg->chat_id, '????????????', 'markdown');
    }

    private function fromReply()
    {
        // ticket
        if (preg_match('/[#](.*)/', $this->msg->reply_text, $match)) {
            $this->replayTicket($match[1]);
        }
    }

    private function replayTicket($ticketId)
    {
        $msg = $this->msg;
        if (! $msg->is_private) {
            return;
        }
        $user = User::with(['userAuths' => function ($query) use ($msg) {
            $query->whereType('telegram')->whereIdentifier($msg->chat_id);
        },
        ])->first();

        if (! $user) {
            abort(500, '???????????????');
        }
        $admin = User::role('Super Admin')->whereId($user->id)->first();
        if ($admin) {
            $ticket = Ticket::whereId($ticketId)->first();
            if (! $ticket) {
                abort(500, '???????????????');
            }
            if ($ticket->status) {
                abort(500, '??????????????????????????????');
            }
            $ticket->reply()->create(['admin_id' => $admin->id, 'content' => $msg->text]);
        }
        $telegramService = new TelegramService();
        $telegramService->sendMessage($msg->chat_id, "#`{$ticketId}` ????????????????????????", 'markdown');
    }
}
