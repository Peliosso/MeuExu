<?php
// ================== CONFIGURAÇÃO ==================
$telegram_token = getenv('TELEGRAM_TOKEN'); // Token do bot Telegram
$openai_key = getenv('OPENAI_KEY');         // Token da OpenAI
$log_file = __DIR__ . '/log.txt';           // Arquivo de log

// ================== RECEBE UPDATE ==================
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Função para registrar log
function write_log($message){
    global $log_file;
    $time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$time] $message\n", FILE_APPEND);
}

// Verifica se chegou mensagem
if(isset($update['message'])){
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];

    write_log("Mensagem recebida de $chat_id: $text");

    // ================== COMANDO /START ==================
    if(strtolower(trim($text)) === '/start'){
        $welcome = "Salve, meu filho! Eu sou o Preto Velho do bot da Umbanda.\nMe pergunte o que quiser e eu darei conselhos espirituais e malandragem com sabedoria.";
        file_get_contents("https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=".urlencode($welcome));
        write_log("Resposta enviada a $chat_id: $welcome");
        exit;
    }

    // ================== PROMPT DA IA ==================
    $prompt = <<<EOT
Você é um Preto Velho da Umbanda, malandro e sábio. Responda de forma bem-humorada, com conselhos espirituais, e de acordo com a tradição da Umbanda. Sempre ofereça orientação com paciência e carinho.  
Mensagem do humano: "$text"
Resposta do Preto Velho:
EOT;

    // ================== CHAMA OPENAI ==================
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $openai_key"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role"=>"user","content"=>$prompt]
        ],
        "max_tokens" => 500
    ]));

    $response = curl_exec($ch);
    $resp_json = json_decode($response, true);
    $reply = $resp_json['choices'][0]['message']['content'] ?? "Desculpe, meu filho, não entendi direito. Pergunte de novo.";

    // ================== ENVIA RESPOSTA PARA TELEGRAM ==================
    file_get_contents("https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=".urlencode($reply));
    write_log("Resposta enviada a $chat_id: $reply");
}