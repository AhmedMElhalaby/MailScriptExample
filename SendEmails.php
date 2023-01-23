<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\DB;

class SendEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;
    protected $message;
    protected $chunk_size;

    public function __construct($users, $message, $chunk_size)
    {
        $this->users = $users;
        $this->message = $message;
        $this->chunk_size = $chunk_size;// Number of emails per request based on service capacity
    }

    public function handle()
    {
        $client = new SesClient([
            'version' => 'latest',
            'region' => env('AWS_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        $recipients = array_chunk($this->users, $this->chunk_size);
        foreach ($recipients as $chunk) {
            $to = array_map(function ($user) {return $user->email;}, $chunk);
            $result = $client->sendEmail([
                'Source' => 'your_email@example.com',
                'Destination' => [
                    'ToAddresses' => $to,
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $this->message,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => 'Test Email',
                    ],
                ],
            ]);
            sleep(5); // Sleep for 5 seconds between each chunk to avoid rate limit exception
        }
    }
}
