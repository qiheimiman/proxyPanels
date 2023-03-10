<?php

return [
    'attribute'               => 'NOTIFICATIONS',
    'new'                     => ':num new message|:num new messages',
    'empty'                   => 'No new message',
    'payment_received'        => 'Order has paid, Amount：:amount! Click me to view the detail',
    'account_expired'         => 'Account Going to Expired',
    'account_expired_content' => 'Your account will be expired after【:days】days. For your server experience, please renew your account ahead of time.',
    'account_expired_blade'   => 'Account will be expired after【:days】days, Please renew',
    'active_email'            => 'Please completed this action in 30 minutes',
    'close_ticket'            => 'Ticket #【:id】 - :title Closed',
    'view_web'                => 'View Our Website',
    'view_ticket'             => 'View The Ticket',
    'new_ticket'              => 'New Ticket Opened: :title',
    'reply_ticket'            => 'New Ticket Replied: :title',
    'ticket_content'          => 'Ticket Content: ',
    'node_block'              => 'Node Blocked Warning',
    'node_offline'            => 'Node maybe offline!',
    'node_offline_content'    => 'Following Nodes abnormal: return heartbeats information are abnormal, Please pay attention.',
    'block_report'            => 'Blocked Report: ',
    'traffic_warning'         => 'Data Traffic Waring: ',
    'traffic_remain'          => 'Data Traffic Consumption: :percent%',
    'traffic_tips'            => 'Please pay attention on the service reset day. You may also cloud consider reset your data before the reset day.',
    'verification_account'    => 'Account Verification',
    'verification'            => 'Your verification code: ',
    'verification_limit'      => 'Please completed this action in :minutes minutes',
    'data_anomaly'            => 'User Data Traffic Abnormal Warning',
    'data_anomaly_content'    => 'User :id：Recent Hourly Data Usage [Upload: :upload | Download: :download | Total: :total]',
    'node'                    => [
        'upload'   => 'Upload',
        'download' => 'download',
        'total'    => 'Total',
    ],
];
