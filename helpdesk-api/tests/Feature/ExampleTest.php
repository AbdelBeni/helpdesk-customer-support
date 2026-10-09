<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {email}';

    protected $description = 'Send a test email';

    public function handle(): int
    {
        $email = $this->argument('email');

        Mail::raw(
            'SMTP is working correctly with Laravel.',
            function ($message) use ($email) {
                $message
                    ->to($email)
                    ->subject('HelpDesk SMTP Test');
            }
        );

        $this->info('Test email sent successfully.');

        return self::SUCCESS;
    }
}