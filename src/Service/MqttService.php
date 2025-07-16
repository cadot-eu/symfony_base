<?php

namespace App\Service;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Psr\Log\LoggerInterface;

class MqttService
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $topicBase;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->host = $_ENV['MQTT_HOST'] ?? 'localhost';
        $this->port = isset($_ENV['MQTT_PORT']) ? (int)$_ENV['MQTT_PORT'] : 1883;
        $this->username = $_ENV['MQTT_USERNAME'] ?? '';
        $this->password = $_ENV['MQTT_PASSWORD'] ?? '';
        $this->topicBase = $_ENV['MQTT_TOPIC_BASE'] ?? '';
    }

    public function publish(string $topic, string $message, $retain = false): void
    {
        $connectionSettings = (new ConnectionSettings())
            ->setUsername($this->username)
            ->setPassword($this->password)
            ->setKeepAliveInterval(60)   // optionnel, bon usage
            ->setLastWillTopic(null)    // pas de will
            ->setLastWillMessage(null);

        $clientId = uniqid('mqtt_client_', true);

        $mqtt = new MqttClient($this->host, $this->port, $clientId);

        try {
            $mqtt->connect($connectionSettings, true);

            $mqtt->publish($this->topicBase . '/' . $topic, $message, 0, $retain);
            $this->logger->info("Message publié sur le topic '{$topic}'");

            $mqtt->disconnect();
        } catch (\Exception $e) {
            $this->logger->error("Erreur MQTT : " . $e->getMessage());
            // selon ton besoin, tu peux re-lancer l'exception ou la gérer différemment
        }
    }
}
