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
/testkey - Verificar se a OpenAI Key foi encontrada  

💬 Ou me conte sua situação livremente...
Tô aqui pra te guiar, mas só pelo caminho da luz ⚜️
";
    enviarMensagem($chat_id, $menu, $telegram_token);
    exit;
}

// ================= COMANDO DE TESTE DE CHAVE =================
if ($message == "/testkey") {
    $openai_key = getenv('OPENAI_KEY');
    if ($openai_key) {
        $resposta = "✅ OpenAI Key encontrada! Tudo certo para enviar mensagens à IA.";
    } else {
        $resposta = "⚠️ OpenAI Key não encontrada! Configure a variável de ambiente corretamente.";
    }
    enviarMensagem($chat_id, $resposta, $telegram_token);
    exit;
}