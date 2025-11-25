<?php

// ================= CONFIG =================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";

// ======== BUSCA TOKEN IA VIA RENTR Y ========
$token_url = "https://rentry.co/MeuExu/raw";
$openai_key = trim(@file_get_contents($token_url));

if (!$openai_key) {
    file_put_contents("erro_token.txt", "Token IA não encontrado");
    exit;
}

// ================= UPDATE TELEGRAM =================
$update = json_decode(file_get_contents("php://input"), true);

if (!$update || !isset($update["message"])) {
    exit;
}

$message   = $update["message"]["text"] ?? "";
$chat_id   = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

if (!$message) exit;

// ================= PERSONALIDADE IA =================
$system_prompt = "
Você é um Guia Espiritual da Umbanda, com linguagem respeitosa, firme e sábia,
mas com um toque malandro, como um Exu velho experiente.

Estilo:
- Linguagem espiritual profunda e acessível
- Tom de malandro sábio, sem vulgaridade
- Conselheiro espiritual protetor

Você PODE:
- Ensinar banhos, rezas, proteção, limpeza espiritual
- Explicar fundamentos da Umbanda
- Orientar sobre equilíbrio energético

Você NÃO PODE:
- Incentivar vingança
- Ensinar ataques espirituais
- Fazer demandas contra terceiros
- Manipular entidades

Sempre transforme pedidos negativos em orientação de luz.
";

// ================= MENU =================
if ($message == "/start" || $message == "/menu") {

$menu = "
🔮 *Guia Espiritual Online — Seja Bem-vindo, $user_name* 🔮

Sou teu guardião espiritual digital.

📜 *Comandos:*
/banho - Banhos espirituais  
/protecao - Ritual de proteção  
/limpeza - Limpeza espiritual  
/exu - Ensinamentos sobre Exu  
/orientacao - Conselho espiritual  

💬 Ou fale comigo livremente...
";

    enviarMensagem($chat_id, $menu, $telegram_token);
    exit;
}

// ================= FILTRO =================
$proibidos = ['matar','vingar','castigar','destruir','separar casal'];

foreach ($proibidos as $p) {
    if (stripos($message, $p) !== false) {
        enviarMensagem($chat_id, "⚠️ Filho... não trabalho com maldade. Posso te orientar em proteção e fortalecimento espiritual.", $telegram_token);
        exit;
    }
}

// ================= OPENAI IA =================
$payload = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $message]
    ],
    "temperature" => 0.7
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
curl_close($ch);

$result = json_decode($response, true);

if (!isset($result["choices"][0]["message"]["content"])) {
    enviarMensagem($chat_id, "❌ Erro na IA:\n" . print_r($result, true), $telegram_token);
    exit;
}

$resposta = $result["choices"][0]["message"]["content"];
enviarMensagem($chat_id, $resposta, $telegram_token);

// ================= FUNÇÃO =================
function enviarMensagem($chat_id, $texto, $token) {
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($texto));
}