<?php

// ================= CONFIGURAÇÕES =================

$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";

// ================= BUSCA TOKEN OPENAI VIA RENTR Y =================

$token_url = "https://rentry.co/MeuExu/raw"; // SEU LINK RAW
$openai_key = trim(file_get_contents($token_url));

if (!$openai_key) {
    file_put_contents("erro_token.txt", "Falha ao carregar token IA");
    exit;
}

// ================= RECEBE UPDATE TELEGRAM =================

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) {
    file_put_contents("erro_update.txt", "Update vazio");
    exit;
}

$message   = $update["message"]["text"] ?? "";
$chat_id   = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

if (!$message) exit;

// ================= PERSONALIDADE DO BOT =================

$system_prompt = "
Você é um Guia Espiritual da Umbanda, com linguagem respeitosa, firme e sábia,
mas com um toque malandro, como um Exu velho experiente, que conhece os caminhos da vida.

Estilo:
- Linguagem espiritual profunda e acessível
- Tom de malandro sábio, sem vulgaridade
- Conselheiro espiritual protetor

Você PODE:
- Ensinar banhos, rezas, proteção, limpeza espiritual
- Explicar fundamentos da Umbanda
- Orientar sobre equilíbrio energético
- Ajudar em dúvidas espirituais e emocionais

Você NÃO PODE:
- Incentivar vingança
- Ensinar ataques espirituais
- Fazer demandas contra terceiros
- Manipular entidades

Sempre transforme qualquer pedido negativo em orientação de luz, proteção e fortalecimento espiritual.
";

// ================= MENU =================

if ($message == "/start" || $message == "/menu") {

$menu = "
🔮 *Guia Espiritual Online - Seja Bem-vindo, $user_name* 🔮

Sou teu guardião espiritual digital, pronto pra te orientar nos caminhos da fé.

📜 *Comandos disponíveis:*

/banho - Banhos espirituais personalizados  
/protecao - Ritual de proteção  
/limpeza - Limpeza espiritual  
/significado - Significado espiritual  
/demanda - Defesa contra demandas  
/exu - Ensinamentos sobre Exu  
/orientacao - Conselho espiritual  
/faq - Dúvidas da Umbanda  

💬 Fale comigo livremente também...
Tô aqui pra te guiar, filho ⚜️
";

    enviarMensagem($chat_id, $menu, $telegram_token);
    exit;
}

// ================= FILTRO DE CONTEÚDO PERIGOSO =================

$proibidos = [
    'matar', 'vingar', 'castigar', 'destruir pessoa',
    'arruinar', 'fazer sofrer', 'amaciar pessoa', 'separar casal'
];

foreach ($proibidos as $palavra) {
    if (stripos($message, $palavra) !== false) {
        $resposta = "⚠️ Filho... espiritualidade não é arma de ódio.  
Mas posso te ensinar caminhos de proteção, limpeza e fortalecimento.

Deseja um ritual de defesa espiritual?";
        enviarMensagem($chat_id, $resposta, $telegram_token);
        exit;
    }
}

// ================= COMANDOS AUTOMÁTICOS =================

$comandos_base = [
"/banho" => "Explique banhos espirituais conforme o problema do consulente",
"/protecao" => "Ensine um ritual poderoso de proteção espiritual",
"/limpeza" => "Explique limpeza energética passo a passo",
"/significado" => "Interprete sinais e sonhos espiritualmente",
"/demanda" => "Explique como perceber e se proteger de demandas",
"/exu" => "Explique sobre Exu, Pombagira e seus caminhos",
"/orientacao" => "Dê um conselho profundo espiritual",
"/faq" => "Responda dúvidas sobre Umbanda"
];

foreach ($comandos_base as $cmd => $instrucao) {
    if (stripos($message, $cmd) === 0) {
        $message = $instrucao . ": " . str_replace($cmd, "", $message);
    }
}

// ================= ENVIO PARA OPENAI =================

$payload = [
    "model" => "gpt-4.1-mini",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $message]
    ],
    "temperature" => 0.8
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

// DEBUG AUTOMÁTICO
if (!isset($result["choices"][0]["message"]["content"])) {
    enviarMensagem($chat_id, "❌ ERRO IA:\n" . print_r($result, true), $telegram_token);
    exit;
}

$resposta = $result["choices"][0]["message"]["content"];
enviarMensagem($chat_id, $resposta, $telegram_token);

// ================= FUNÇÃO TELEGRAM =================

function enviarMensagem($chat_id, $texto, $token) {
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($texto) . "&parse_mode=Markdown");
}