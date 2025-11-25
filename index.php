<?php

// ================== CONFIGURAÇÃO ==================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";
$groq_key       = getenv('GROQ_KEY'); // Key do Groq (variável de ambiente no Render)

// ================== RECEBE UPDATE ==================
$update = json_decode(file_get_contents("php://input"), true);
file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Update: " . json_encode($update) . "\n", FILE_APPEND);

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
    file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Enviado: {$texto}\nResposta Telegram: {$res}\n", FILE_APPEND);
}

// ================== COMANDOS ==================
if ($message == "/start" || $message == "/menu") {
    $texto = "🔮 *Guia Espiritual Online - Bem-vindo, $user_name!* 🔮

Use /perguntar (sua dúvida) para receber orientação espiritual da Umbanda via IA.
Também disponível:
/testkey - Checar se a Groq Key está funcionando.
";
    enviarMensagem($chat_id, $texto, $telegram_token);
    exit;
}

if ($message == "/testkey") {
    if ($groq_key) {
        $resposta = "✅ Groq Key encontrada!\nValor parcial: " . substr($groq_key,0,10) . "...";
    } else {
        $resposta = "⚠️ Groq Key não encontrada! Configure no Render.";
    }
    enviarMensagem($chat_id, $resposta, $telegram_token);
    exit;
}

// ================== COMANDO /PERGUNTAR ==================
if (stripos($message, "/perguntar") === 0) {
    if (!$groq_key) {
        enviarMensagem($chat_id, "⚠️ Groq Key não encontrada! Configure no Render.", $telegram_token);
        exit;
    }

    $pergunta = trim(substr($message, 11)); // remove "/perguntar "
    if (!$pergunta) {
        enviarMensagem($chat_id, "⚠️ Filho, escreva sua pergunta após /perguntar.", $telegram_token);
        exit;
    }

    // ======== Prompt da personalidade do bot ========
    $system_prompt = "
Você é um Guia Espiritual da Umbanda, com linguagem respeitosa, firme e sábia,
mas com um toque malandro, como um Exu velho experiente, que conhece os caminhos da vida.
Dê respostas espirituais e de orientação, sem incentivar vingança ou manipulação.
";

    // ======== Requisição para Groq ========
    $payload = [
        "model" => "mixtral", // modelo do Groq
        "prompt" => $system_prompt . "\nUsuário: " . $pergunta . "\nGuia:",
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
    curl_close($ch);

    $result = json_decode($response, true);
    $resposta = $result["choices"][0]["text"] ?? "⚠️ Os guias estão silenciosos agora, tente novamente.";

    enviarMensagem($chat_id, $resposta, $telegram_token);
    exit;
}

// ================== MENSAGEM PADRÃO ==================
$resposta = "⚠️ Filho, comando não reconhecido. Use /start ou /perguntar (sua dúvida).";
enviarMensagem($chat_id, $resposta, $telegram_token);