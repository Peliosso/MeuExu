<?php
$groq_key = getenv('GROQ_KEY');

$payload = [
    "model" => "llama-3.3-70b-versatile",    // ou outro modelo disponível para você
    "messages" => [
        ["role" => "system", "content" => "Você é um Guia Espiritual da Umbanda."],
        ["role" => "user", "content" => "Qual é o melhor banho espiritual?"]
    ],
    "max_completion_tokens" => 300,
    "temperature" => 0.85
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: " . "Bearer {$groq_key}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

var_dump($response);