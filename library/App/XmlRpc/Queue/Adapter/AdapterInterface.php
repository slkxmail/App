<?php

namespace App\XmlRpc\Queue\Adapter;

interface AdapterInterface
{
    public function addRequest($url, $queueName, $method, $params=array());
    public function deleteRequest($requestId);

    public function existsQueue($queueName);
    public function deleteQueue($queueName);
}