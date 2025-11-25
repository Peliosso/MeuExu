<?php

// ================= CONFIGURAÇÕES =================

$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";
$gemini_key = "AIzaSyAYbLaedTJ-LLsAJsWVfJlDSJTmygQlsJQ";

// ================= RECEBE UPDATE =================

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit;

$message   = $update["message"]["text"] ?? "";
$chat_id   = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

if (!$message) exit;

// ================= PERSONALIDADE ESPIRITUAL =================

$system_prompt = "
Você é um Guia Espiritual da Umbanda, com linguagem respeitosa, firme e sábia,
com tom de Exu velho experiente, protetor e conselheiro.

Estilo:
- Linguagem espiritual profunda e acolhedora
- Tom firme, mas humilde
- Conselheiro sábio e protetor

Você PODE:
- Ensinar banhos, rezas, proteção, limpeza espiritual
- Explicar fundamentos da Umbanda
- Orientar espiritualmente

Você NÃO PODE:
- Incentivar vingança
- Ensinar ataques espirituais
- Fazer demandas contra terceiros

Sempre conduza para caminhos de luz, proteção e equilíbrio.
";

// ================= MENU =================

if ($message == "/start" || $message == "/menu") {

$menu = "
🔮 *Guia Espiritual Online - Seja Bem-vindo, $user_name* 🔮

Sou teu guardião espiritual digital.

📜 Comandos:
/banho  
/protecao  
/limpeza  
/significado  
/demanda  
/exu  
/orientacao  
/faq  

Ou fale livremente comigo, filho ⚜️
";

enviarMensagem($chat_id, $menu, $telegram_token);
exit;
}

// ================= FILTRO =================

$proibidos = ['matar','vingar','castigar','destruir','arruinar','fazer sofrer'];

foreach ($proibidos as $p) {
    if (stripos($message, $p) !== false) {
        enviarMensagem($chat_id,
        "⚠️ Espiritualidade não é arma, filho.  
Posso te guiar na proteção e fortalecimento espiritual.",
        $telegram_token);
        exit;
    }
}

// ================= COMANDOS =================

$comandos_base = [
"/banho" => "Explique banhos espirituais conforme o problema do consulente",
"/protecao" => "Ensine um ritual poderoso de proteção espiritual",
"/limpeza" => "Explique limpeza energética passo a passo",
"/significado" => "Interprete sinais e sonhos espiritualmente",
"/demanda" => "Explique como se proteger espiritualmente",
"/exu" => "Explique sobre Exu e Pombagira",
"/orientacao" => "Dê um conselho espiritual profundo",
"/faq" => "Responda dúvidas sobre Umbanda"
];

foreach ($comandos_base as $cmd => $instrucao) {
    if (stripos($message, $cmd) === 0) {
        $message = $instrucao . ": " . str_replace($cmd, "", $message);
    }
}

// ================= ENVIO PARA GEMINI =================

// ================= ENVIO PARA GEMINI =================

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $system_prompt . "\n\nPergunta: " . $message]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.8,
        "maxOutputTokens" => 2048
    ]
];

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=".$gemini_key;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// ===== DEBUG AUTOMÁTICO =====
if (!isset($data["candidates"][0]["content"]["parts"][0]["text"])) {
    enviarMensagem($chat_id, "❌ ERRO GEMINI:\n".print_r($data,true), $telegram_token);
    exit;
}

$resposta = $data["candidates"][0]["content"]["parts"][0]["text"];

// ================= ENVIA AO TELEGRAM =================

enviarMensagem($chat_id, $resposta, $telegram_token);

// ================= FUNÇÃO TELEGRAM =================

function enviarMensagem($chat_id, $texto, $token){
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=".urlencode($texto)."&parse_mode=Markdown");
}