<?php
// ================== CONFIGURAÇÃO ==================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";
$groq_key       = getenv('GROQ_KEY'); // Key do Groq (variável de ambiente)
$memory_file    = "memoria.json";      // arquivo para guardar a entidade por chat_id

// ================== FUNÇÕES ==================
function enviarMensagem($chat_id, $texto, $token, $inline_keyboard=null) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($texto) . "&parse_mode=Markdown";
    if ($inline_keyboard) {
        $data = ["inline_keyboard" => $inline_keyboard];
        $url .= "&reply_markup=" . urlencode(json_encode($data));
    }
    $res = file_get_contents($url);
    file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Enviado: {$texto}\nResposta Telegram: {$res}\n", FILE_APPEND);
}

function loadMemory($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveMemory($file, $data) {
    file_put_contents($file, json_encode($data));
}

function getPrompt($entidade) {
    $prompts = [
        "ze_pelintra" => "Você é Zé Pelintra, malandro carioca, debochado, espirituoso. Responda de forma divertida e direta, com gírias e truques de vida.",
        "preto_velho" => "Você é Preto Velho, sábio, calmo e acolhedor. Responda com paciência, exemplos da vida e ensinamentos espirituais.",
        "exu" => "Você é Exu, astuto e provocador. Responda direto, mostrando caminhos, proteção e malandragem.",
        "pomba_gira" => "Você é Pomba Gira, confiante, sensual e divertida. Responda com charme, leveza e empoderamento.",
        "pai_mae_santo" => "Você é Pai/Mãe de Santo, tradicional, instrutivo e acolhedor. Responda com autoridade, cuidado e orientação espiritual."
    ];
    return $prompts[$entidade] ?? "Você é um Guia Espiritual da Umbanda, respeitoso e firme, pronto para orientar.";
}

// ================== RECEBE UPDATE ==================
$update = json_decode(file_get_contents("php://input"), true);
file_put_contents("log.txt", date('Y-m-d H:i:s') . " - Update: " . json_encode($update) . "\n", FILE_APPEND);

$message = $update["message"]["text"] ?? "";
$chat_id = $update["message"]["chat"]["id"] ?? "";
$user_name = $update["message"]["from"]["first_name"] ?? "filho";

// ================== MEMÓRIA ==================
$memory = loadMemory($memory_file);
$entidade = $memory[$chat_id] ?? null;

// ================== COMANDOS ==================
if ($message == "/start" || $message == "/menu") {
    $inline_keyboard = [
        [
            ["text"=>"Zé Pelintra","callback_data"=>"entidade_ze_pelintra"],
            ["text"=>"Preto Velho","callback_data"=>"entidade_preto_velho"]
        ],
        [
            ["text"=>"Exu","callback_data"=>"entidade_exu"],
            ["text"=>"Pomba Gira","callback_data"=>"entidade_pomba_gira"]
        ],
        [
            ["text"=>"Pai/Mãe de Santo","callback_data"=>"entidade_pai_mae_santo"]
        ]
    ];
    $texto = "🔮 *Guia Espiritual Online - Bem-vindo, $user_name!* 🔮\n\nEscolha com qual entidade você quer conversar:";
    enviarMensagem($chat_id, $texto, $telegram_token, $inline_keyboard);
    exit;
}

if ($message == "/trocar") {
    unset($memory[$chat_id]);
    saveMemory($memory_file, $memory);
    enviarMensagem($chat_id, "✅ Filho, escolha outra entidade:", $telegram_token);
    // reaproveitar /start
    $inline_keyboard = [
        [
            ["text"=>"Zé Pelintra","callback_data"=>"entidade_ze_pelintra"],
            ["text"=>"Preto Velho","callback_data"=>"entidade_preto_velho"]
        ],
        [
            ["text"=>"Exu","callback_data"=>"entidade_exu"],
            ["text"=>"Pomba Gira","callback_data"=>"entidade_pomba_gira"]
        ],
        [
            ["text"=>"Pai/Mãe de Santo","callback_data"=>"entidade_pai_mae_santo"]
        ]
    ];
    enviarMensagem($chat_id, "Escolha a entidade:", $telegram_token, $inline_keyboard);
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

// ================== CALLBACKS DE BOTÕES ==================
if (isset($update["callback_query"])) {
    $callback = $update["callback_query"];
    $data = $callback["data"];
    $chat_id = $callback["message"]["chat"]["id"];
    if (str_starts_with($data, "entidade_")) {
        $entidade = str_replace("entidade_", "", $data);
        $memory[$chat_id] = $entidade;
        saveMemory($memory_file, $memory);
        enviarMensagem($chat_id, "✅ Entidade selecionada: *" . ucfirst(str_replace("_"," ",$entidade)) . "*\nAgora todas as suas perguntas serão respondidas no estilo dela.", $telegram_token);
        exit;
    }
}

// ================== FILTRO ESPIRITUAL ==================
$proibidos = ['matar','vingar','castigar','destruir','arruinar','fazer sofrer','amaciar','separar casal'];
foreach ($proibidos as $palavra) {
    if (stripos($message, $palavra) !== false) {
        $resposta = "⚠️ Filho, cuidado com esse pensamento. A espiritualidade não é arma. Vou te ensinar proteção e fortalecimento.";
        enviarMensagem($chat_id, $resposta, $telegram_token);
        exit;
    }
}

// ================== MENUS DE TUTORIAIS ==================
$menus = [
    "/banho" => "🛁 Banhos espirituais:\n- Lavanda: paz e relaxamento.\n- Camomila: sono e tranquilidade.\n- Arruda: proteção e limpeza energética.",
    "/protecao" => "🛡️ Proteção espiritual:\n- Fechamento de corpo.\n- Amuletos de proteção.\n- Rezas para afastar inveja.",
    "/limpeza" => "✨ Limpeza energética:\n- Defumação com ervas.\n- Banhos de ervas.\n- Técnicas de purificação da casa.",
    "/significado" => "💭 Significados:\n- Sonhos e sinais da vida.\n- Como interpretar mensagens espirituais.",
    "/demanda" => "⚠️ Como se proteger de demandas:\n- Banhos de limpeza.\n- Rezas de proteção.\n- Evitar contato com negatividade.",
    "/exu" => "🔥 Ensinos sobre Exu e Pombagira:\n- Caminhos e energias.\n- Proteção e abertura de caminhos.",
    "/orientacao" => "📝 Conselho espiritual pessoal:\n- Escuta, reflexão e prática de proteção.\n- Fortalecimento interior.",
    "/faq" => "❓ Dúvidas frequentes:\n- Explicações sobre Umbanda e rituais.\n- Orientações espirituais."
];

if (isset($menus[$message])) {
    enviarMensagem($chat_id, $menus[$message], $telegram_token);
    exit;
}

// ================== RESPOSTA AUTOMÁTICA ==================
if ($entidade && $groq_key) {
    $system_prompt = getPrompt($entidade);
    $payload = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role"=>"system","content"=>$system_prompt],
            ["role"=>"user","content"=>$message]
        ],
        "max_completion_tokens" => 800,
        "temperature" => 0.85
    ];

   $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
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
    $resposta = $result["choices"][0]["message"]["content"] ?? "⚠️ Os guias estão silenciosos agora, tente novamente.";
    enviarMensagem($chat_id, $resposta, $telegram_token);
    exit;
}

// ================== PADRÃO ==================
$resposta = "⚠️ Filho, comando não reconhecido. Use /start ou escolha um menu: /banho, /protecao, /limpeza, /significado, /demanda, /exu, /orientacao, /faq.";
enviarMensagem($chat_id, $resposta, $telegram_token);