<?php

// ================== CONFIGURAÇÃO ==================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA"; // Token do bot Telegram
$openai_key     = getenv('OPENAI_KEY');     // Token da OpenAI (oculto)

// ================== RECEBE UPDATE ==================
$update = json_decode(file_get_contents("php://input"), true);

$message = $update["message"]["text"] ?? "";
$chat_id = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

if (!$message) exit;

// ================= PERSONALIDADE DO BOT =================
$system_prompt = "
Você é um Guia Espiritual da Umbanda, com linguagem respeitosa, firme e sábia,
mas com um toque malandro, como um Exu velho experiente, que conhece os caminhos da vida.

Estilo:
- Linguagem envolvente, profunda, espiritual e acessível
- Tom de malandro sábio, sem vulgaridade
- Aconselha como um guardião espiritual

Você PODE:
- Ensinar banhos, rezas, proteção, limpeza espiritual
- Explicar fundamentos da Umbanda
- Orientar sobre equilíbrio energético
- Ajudar em dúvidas espirituais, emocionais e de fé

Você NÃO PODE:
- Incentivar vingança
- Ensinar ataques espirituais
- Fazer demandas contra terceiros
- Manipular entidades

Sempre transforme pedidos negativos em caminhos de luz, proteção e fortalecimento espiritual.
";

// ================= COMANDOS DO BOT =================
if ($message == "/start" || $message == "/menu") {
    $menu = "
🔮 *Guia Espiritual Online - Seja Bem-vindo, $user_name* 🔮

Sou seu guardião espiritual digital, pronto pra te orientar nos caminhos da fé e da força.

📜 *Comandos disponíveis:*

/banho - Banhos espirituais personalizados  
/protecao - Ritual de proteção e fechamento de corpo  
/limpeza - Limpeza espiritual energética  
/significado - Significado espiritual de sonhos e sinais  
/demanda - Como se proteger de demandas  
/exu - Ensinos sobre Exu e Pombagira  
/orientacao - Conselho espiritual pessoal  
/faq - Dúvidas frequentes da Umbanda  

💬 Ou me conte sua situação livremente...
Tô aqui pra te guiar, mas só pelo caminho da luz ⚜️
";
    enviarMensagem($chat_id, $menu, $telegram_token);
    exit;
}

// ================= FILTRO ESPIRITUAL =================
$proibidos = [
    'matar', 'vingar', 'castigar', 'destruir pessoa',
    'arruinar', 'fazer sofrer', 'amaciar pessoa', 'separar casal'
];

foreach ($proibidos as $palavra) {
    if (stripos($message, $palavra) !== false) {
        $resposta = "⚠️ Filho, cuidado com esse pensamento... espiritualidade não é arma.  
Mas posso te ensinar proteção forte, limpeza e fortalecimento para que nada te atinja.  
Quer aprender um ritual de defesa espiritual?";
        enviarMensagem($chat_id, $resposta, $telegram_token);
        exit;
    }
}

// ================= INSTRUÇÕES AUTOMÁTICAS POR COMANDO =================
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

// ================= ENVIO PARA IA =================
$payload = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $message]
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
curl_close($ch);

$result = json_decode($response, true);
$resposta = $result["choices"][0]["message"]["content"] ?? "⚠️ Os guias estão silenciosos agora, tente novamente.";

enviarMensagem($chat_id, $resposta, $telegram_token);

// ================= FUNÇÃO TELEGRAM =================
function enviarMensagem($chat_id, $texto, $token) {
    file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($texto) . "&parse_mode=Markdown");
}