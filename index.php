<?php
// ================== CONFIGURAÇÃO ==================
$telegram_token = getenv('TELEGRAM_TOKEN'); // Token do bot Telegram
$gemini_key = getenv('GEMINI_KEY');         // Token Gemini API

// ================== RECEBE UPDATE ==================
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if(isset($update['message'])){
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];

    // ================== PROMPT DA IA ==================
    // Personalidade: Preto Velho, sábio, malandro, bem humorado, conselhos espirituais
    $prompt = <<<EOT
Você é um Preto Velho da Umbanda, malandro e sábio. Responda de forma bem-humorada, com conselhos espirituais, e de acordo com a tradição da Umbanda. Sempre ofereça orientação com paciência e carinho.  
Mensagem do humano: "$text"
Resposta do Preto Velho:
EOT;

    // ================== CHAMA GEMINI ==================
    $ch = curl_init("https://api.anthropic.com/v1/complete");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: $gemini_key"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gemini-1.3",
        "prompt" => $prompt,
        "max_tokens_to_sample" => 500
    ]));

    $response = curl_exec($ch);
    $resp_json = json_decode($response, true);
    $reply = $resp_json['completion'] ?? "Desculpe, não consegui entender direito, meu filho. Tente de novo com outras palavras.";

    // ================== ENVIA RESPOSTA PARA TELEGRAM ==================
    file_get_contents("https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=".urlencode($reply));
}