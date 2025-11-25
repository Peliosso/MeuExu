<?php

// ================== CONFIGURAÇÃO ==================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA"; // Token do Telegram
$openai_key     = getenv('OPENAI_KEY'); // Token da OpenAI (oculto no Render)

// ================== RECEBE UPDATE ==================
$update = json_decode(file_get_contents("php://input"), true);

// ===== LOG PARA DEPURAÇÃO =====
file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Update recebido: " . json_encode($update) . "\n", FILE_APPEND);

$message = $update["message"]["text"] ?? "";
$chat_id = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

if (!$message) {
    file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Mensagem vazia.\n", FILE_APPEND);
    exit;
}

// ================== FUNÇÃO PARA ENVIAR MENSAGEM ==================
function enviarMensagem($chat_id, $texto, $token) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($texto) . "&parse_mode=Markdown";
    $res = file_get_contents($url);
    file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Mensagem enviada: {$texto}\nResposta Telegram: {$res}\n", FILE_APPEND);
}

// ================= COMANDOS ==================
if ($message == "/start" || $message == "/menu") {
    $texto = "🔮 *Guia Espiritual Online - Bem-vindo, $user_name!* 🔮

Sou seu guia da Umbanda digital. Aqui você pode receber orientação espiritual.

📜 *Comandos disponíveis:*
/testkey - Verificar se a OpenAI Key foi encontrada
";
    enviarMensagem($chat_id, $texto, $telegram_token);
    exit;
}

if ($message == "/testkey") {
    if ($openai_key) {
        $resposta = "✅ OpenAI Key encontrada!\nValor parcial para debug: " . substr($openai_key,0,10) . "...";
    } else {
        $resposta = "⚠️ OpenAI Key não encontrada! Configure a variável de ambiente no Render corretamente.";
    }
    enviarMensagem($chat_id, $resposta, $telegram_token);
    exit;
}

// ================= MENSAGEM PADRÃO ==================
$resposta = "⚠️ Filho, comando não reconhecido. Use /start para começar ou /testkey para checar a chave.";
enviarMensagem($chat_id, $resposta, $telegram_token);