<?php
$openai_key = getenv('OPENAI_KEY');

$payload = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role"=>"system","content"=>"Você é um Guia Espiritual da Umbanda."],
        ["role"=>"user","content"=>"Qual é o melhor banho espiritual?"]
    ],
    "temperature" => 0.85
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer {$openai_key}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
var_dump($response);