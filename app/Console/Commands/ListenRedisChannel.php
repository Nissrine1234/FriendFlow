<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ListenRedisChannel extends Command
{
    protected $signature = 'redis:subscribe';
    protected $description = 'Subscribe to a Redis channel';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Subscribing to Redis channel "friendflow_notifications"...');

        try {
            $client = Redis::connection()->client();
            $this->info("Connexion Redis établie avec succès");

            $pubsub = $client->pubSubLoop();
            $pubsub->subscribe('friendflow_notifications');

            foreach ($pubsub as $message) {
                if ($message->kind === 'message') {
                    echo "📩 Nouveau message reçu : {$message->payload}\n";
                }
            }

        } catch (\Exception $e) {
            $this->error("Erreur lors de l'abonnement : " . $e->getMessage());
        }
    }
}
