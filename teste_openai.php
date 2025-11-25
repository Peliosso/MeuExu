<?php
$groq_key = getenv('GROQ_KEY'); // sua chave do Groq

$prompt = "Você é um Guia Espiritual da Umbanda.\nPergunta: Qual é o melhor banho espiritual?\nGuia:";

$payload = [
    "model" => "mixtral",   // modelo do Groq
    "prompt" => $prompt,
    "max_tokens" => 300,
    "temperature" => 0.85
];

$ch = curl_init("https://api.groq.com/v1/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer {$groq_key}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
var_dump($response);