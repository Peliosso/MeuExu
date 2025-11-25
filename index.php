<?php
// bot.php - Bot Umbanda com Groq, entidades grátis/VIP, menus, memória e tutoriais
// ================== CONFIGURAÇÃO ==================
$telegram_token = "8518979324:AAFMBBZ62q0V3z6OkmiL7VsWNEYZOp460JA";
$groq_key       = getenv('GROQ_KEY'); // variável de ambiente no Render
$memory_file    = __DIR__ . "/memoria.json";
$log_file       = __DIR__ . "/log.txt";
$VIP_USER_ID    = 7926471341; // seu ID VIP

// ================== UTILITÁRIOS ==================
function log_write($text){
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $text . "\n", FILE_APPEND);
}

function sendRequest($url, $payload, $headers = []){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if($err) log_write("CURL ERROR: $err");
    return $resp;
}

function telegramApi($method, $params){
    global $telegram_token;
    $url = "https://api.telegram.org/bot{$telegram_token}/{$method}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp;
}

// enviar mensagem (com reply_markup opcional)
function enviarMensagem($chat_id, $texto, $reply_markup = null){
    $data = [
        'chat_id' => $chat_id,
        'text' => $texto,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    if($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    $res = telegramApi('sendMessage', $data);
    log_write("sendMessage to {$chat_id}: " . $texto . " | resp: " . $res);
    return $res;
}

// editar mensagem (usado em callbacks)
function editarMensagem($chat_id, $message_id, $texto, $reply_markup = null){
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $texto,
        'parse_mode' => 'Markdown'
    ];
    if($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    $res = telegramApi('editMessageText', $data);
    log_write("editMessage {$chat_id}/{$message_id}: " . $texto . " | resp: " . $res);
    return $res;
}

function answerCallback($callback_id, $text = '', $show_alert = false){
    $data = [
        'callback_query_id' => $callback_id,
        'text' => $text,
        'show_alert' => $show_alert
    ];
    return telegramApi('answerCallbackQuery', $data);
}

// memória simples em arquivo JSON
function loadMemory($file){
    if(!file_exists($file)) return [];
    $json = file_get_contents($file);
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}
function saveMemory($file, $data){
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// prompts por entidade (personalidades)
function getPrompt($entidade){
    $prompts = [
        "ze_pelintra" => "Você é Zé Pelintra, malandro carioca, debochado, espirituoso. Fale com gírias, leve malandragem, conselhos práticos e proteção espiritual. Seja sagaz sem ofender.",
        "preto_velho" => "Você é um Preto Velho, calmo e sábio, fala devagar, com carinho paternal; dá conselhos e ensinamentos espirituais e orientações de limpeza e proteção.",
        "exu" => "Você é Exu, astuto, direto e incisivo. Dá estratégias para abrir caminhos e proteção espiritual, com malandragem e firmeza.",
        "pomba_gira" => "Você é Pomba Gira, confiante, sensual e empoderada. Dá conselhos sobre amor, autoestima e proteção com charme e firmeza.",
        "pai_mae_santo" => "Você é Pai/Mãe de Santo, autoridade espiritual, instrutivo e acolhedor, explicando rituais, banhos e orientações com clareza."
    ];
    return $prompts[$entidade] ?? "Você é um Guia Espiritual da Umbanda, respeitoso e firme, pronto para orientar.";
}

// filtro para bloquear pedidos de dano
function containsProhibited($text){
    $proibidos = ['matar','assassinar','explodir','envenenar','atingir','ferir','vinga','vingar','queimar','atacar','roubar','sequestrar','harm','maldade'];
    foreach($proibidos as $p){
        if(stripos($text, $p) !== false) return true;
    }
    return false;
}

// oráculo simples (gera localmente para poupar requisições)
function gerarOraculo($entidade = null){
    $templates = [
        "Hoje é dia de ajeitar o passo: cuidado com promessas fáceis, valorize seu jogo de cintura.",
        "As portas se abrem, mas exijam cuidado: observe alianças e esteja presente.",
        "Energia de limpeza: aproveite para tirar o que não serve e renovar a casa.",
        "Coração em alerta: converse com sinceridade, evite decisões impulsivas.",
        "Força e proteção: confie nos seus guias e faça uma pequena oferenda de agradecimento."
    ];
    $pick = $templates[array_rand($templates)];
    if($entidade){
        $label = ucfirst(str_replace("_"," ",$entidade));
        return "🔮 Oráculo de {$label} 🔮\n\n" . $pick;
    }
    return "🔮 Oráculo do dia 🔮\n\n" . $pick;
}

// chamar Groq (chat completion)
function groqChat($groq_key, $system, $userMessage){
    $url = "https://api.groq.com/openai/v1/chat/completions";
    $payload = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role"=>"system","content"=>$system],
            ["role"=>"user","content"=>$userMessage]
        ],
        "max_completion_tokens" => 800,
        "temperature" => 0.85
    ];
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer {$groq_key}"
    ];
    $resp = sendRequest($url, $payload, $headers);
    log_write("groqChat payload: " . json_encode($payload));
    log_write("groqChat resp: " . $resp);
    return $resp;
}

// ================== RECEBE UPDATE ==================
$raw = file_get_contents("php://input");
$update = json_decode($raw, true);
log_write("Update raw: " . $raw);

// extrai campos comuns
$message = $update['message']['text'] ?? null;
$chat_id = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? null);
$user_id = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? null;
$user_name = $update['message']['from']['first_name'] ?? $update['callback_query']['from']['first_name'] ?? 'filho';
$callback_query = $update['callback_query'] ?? null;
$callback_data = $callback_query['data'] ?? null;
$callback_message_id = $callback_query['message']['message_id'] ?? null;

// carrega memória e entidade atual
$memory = loadMemory($memory_file);
$entidade = $memory[$chat_id] ?? null;

// ================== HANDLERS ==================

// 1) CALLBACKS (botões inline)
if($callback_query){
    $cid = $callback_query['id'];
    answerCallback($cid); // ack
    // escolha de entidade
    if(str_starts_with($callback_data, "entidade_")){
        $sel = str_replace("entidade_", "", $callback_data);
        // VIP check: se for entidade vip (prefix vip_) e usuário não for VIP, recuse
        if(str_starts_with($sel, "vip_")){
            // entidade vip (ex: vip_exu_rei)
            if($user_id != $VIP_USER_ID){
                // editar mensagem para avisar que é VIP
                $texto = "⛔ Essa entidade é *VIP*.\nSe desejar, entre em contato com o administrador para acesso VIP.";
                $btn_back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']];
                editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$btn_back]);
                exit;
            }
            // liberar retirando prefixo
            $sel = substr($sel, 4);
        }
        $memory[$chat_id] = $sel;
        saveMemory($memory_file, $memory);
        $label = ucfirst(str_replace("_"," ", $sel));
        $texto = "✅ Entidade selecionada: *{$label}*\n\nAgora eu respondo no jeitinho dela. Se quiser trocar, clique em *Trocar entidade*.";
        $inline = [
            [
                ['text'=>'🔁 Trocar entidade','callback_data'=>'trocar_entidade'],
                ['text'=>'📜 Comandos','callback_data'=>'menu_commands']
            ]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$inline]);
        exit;
    }

    // voltar ao menu principal
    if($callback_data === 'menu_main'){
        // monta menu principal novamente
        $keyboard = [
            [['text'=>'🌿 Entidades Grátis','callback_data'=>'menu_ent_gratis']],
            [['text'=>'👑 Entidades VIP','callback_data'=>'menu_ent_vip']],
            [['text'=>'📜 Comandos','callback_data'=>'menu_commands']],
            [['text'=>'🔁 Trocar entidade','callback_data'=>'trocar_entidade']],
            [['text'=>'🔮 Oráculo do dia','callback_data'=>'oraculo_today']],
            [['text'=>'⚠️ Demandas','callback_data'=>'menu_demandas']]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "🔮 Menu Principal 🔮\nEscolha uma opção:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // menu entidades grátis
    if($callback_data === 'menu_ent_gratis'){
        $keyboard = [
            [
                ['text'=>'Zé Pelintra','callback_data'=>'entidade_ze_pelintra'],
                ['text'=>'Preto Velho','callback_data'=>'entidade_preto_velho']
            ],
            [
                ['text'=>'Exu','callback_data'=>'entidade_exu'],
                ['text'=>'Pomba Gira','callback_data'=>'entidade_pomba_gira']
            ],
            [['text'=>'Pai/Mãe de Santo','callback_data'=>'entidade_pai_mae_santo']],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "🌿 Entidades Grátis\nEscolha:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // menu entidades VIP
    if($callback_data === 'menu_ent_vip'){
        $keyboard = [
            [
                ['text'=>'Exu Rei (VIP)','callback_data'=>'entidade_vip_exu_rei'],
                ['text'=>'Maria Padilha (VIP)','callback_data'=>'entidade_vip_maria_padilha']
            ],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "👑 Entidades VIP\n(Exclusivo para membros VIP)", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // trocar entidade (abre menu)
    if($callback_data === 'trocar_entidade'){
        $keyboard = [
            [['text'=>'🌿 Grátis','callback_data'=>'menu_ent_gratis']],
            [['text'=>'👑 VIP','callback_data'=>'menu_ent_vip']],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "🔁 Trocar Entidade\nEscolha uma categoria:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // comandos menu
    if($callback_data === 'menu_commands'){
        $keyboard = [
            [
                ['text'=>'Como usar /perguntar','callback_data'=>'tutorial_perguntar'],
                ['text'=>'Como pedir demanda','callback_data'=>'tutorial_demanda']
            ],
            [
                ['text'=>'Como trocar entidade','callback_data'=>'tutorial_trocar'],
                ['text'=>'Voltar','callback_data'=>'menu_main']
            ]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "📜 Comandos - Escolha um item para ver tutorial:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // tutorial handlers (editar com conteúdo)
    if(str_starts_with($callback_data, 'tutorial_')){
        $topic = substr($callback_data, 9);
        $content = "";
        if($topic === 'perguntar'){
            $content = "*Como usar*\n\nBasta mandar qualquer mensagem no chat. O guia escolhido vai responder no estilo dele. Exemplo: `Como eu faço um banho de limpeza?`";
        } elseif($topic === 'demanda'){
            $content = "*Como pedir uma demanda*\n\nEscolha `⚠️ Demandas` no menu. Demandas leves são grátis (limpeza, proteção). Demandas pesadas são VIP e tratadas de forma simbólica/defensiva. Nunca peça para ferir alguém.";
        } elseif($topic === 'trocar'){
            $content = "*Como trocar entidade*\n\nUse o botão *Trocar entidade* ou envie /trocar. Depois selecione outra entidade no menu.";
        } else {
            $content = "Tutorial não encontrado.";
        }
        $back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_commands']];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $content, ["inline_keyboard"=>$back]);
        exit;
    }

    // oráculo via callback
    if($callback_data === 'oraculo_today'){
        $ent = $memory[$chat_id] ?? null;
        $texto = gerarOraculo($ent);
        $back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$back]);
        exit;
    }

    // demandas menu
    if($callback_data === 'menu_demandas'){
        $keyboard = [
            [['text'=>'Demandas Leves (Grátis)','callback_data'=>'demanda_leve']],
            [['text'=>'Demandas Pesadas (VIP)','callback_data'=>'demanda_pesada']],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, "⚠️ Demandas - escolha uma opção:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    if($callback_data === 'demanda_leve'){
        $texto = "✅ Demandas Leves:\n- Corte de inveja simbólico\n- Proteção contra olho gordo\n- Rito de limpeza simples\n\nEnvie sua descrição e o guia responderá com orientações e proteção.";
        $back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_demandas']];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$back]);
        exit;
    }

    if($callback_data === 'demanda_pesada'){
        // VIP check
        if($user_id != $VIP_USER_ID){
            $texto = "⛔ Demandas pesadas são *VIP*. Só disponíveis para membros VIP.";
            $back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_demandas']];
            editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$back]);
            exit;
        }
        // exemplo de demandas pesadas tratadas de forma simbólica
        $texto = "🔥 Demandas Pesadas (VIP) - orientações espirituais simbólicas:\n- Proteção profunda e fechamento de caminhos\n- Rito de justiça cármica espiritual (simbólico)\n- Limpeza e quebra de vínculos energéticos persistentes\n\n*Nota:* Não instruímos ou executamos danos a terceiros; estas práticas são de defesa e equilíbrio energético.";
        $back = [['text'=>'⬅️ Voltar','callback_data'=>'menu_demandas']];
        editarMensagem($callback_query['message']['chat']['id'], $callback_message_id, $texto, ["inline_keyboard"=>$back]);
        exit;
    }

    // fallback para callbacks desconhecidos
    answerCallback($cid, "Opção selecionada.");
    exit;
}

// 2) comandos por texto (mensagens normais)
if($message){
    // comandos simples
    $textTrim = trim($message);
    if(in_array($textTrim, ['/start','/menu'])){
        // construir menu principal usando edit-capable message (send new)
        $keyboard = [
            [['text'=>'🌿 Entidades Grátis','callback_data'=>'menu_ent_gratis']],
            [['text'=>'👑 Entidades VIP','callback_data'=>'menu_ent_vip']],
            [['text'=>'📜 Comandos','callback_data'=>'menu_commands']],
            [['text'=>'🔁 Trocar entidade','callback_data'=>'trocar_entidade']],
            [['text'=>'🔮 Oráculo do dia','callback_data'=>'oraculo_today']],
            [['text'=>'⚠️ Demandas','callback_data'=>'menu_demandas']]
        ];
        $texto = "🔮 *Terreiro Digital* 🔮\n\nEscolha uma opção abaixo:";
        enviarMensagem($chat_id, $texto, ["inline_keyboard"=>$keyboard]);
        exit;
    }

    if($textTrim === '/trocar'){
        unset($memory[$chat_id]);
        saveMemory($memory_file, $memory);
        $keyboard = [
            [['text'=>'🌿 Entidades Grátis','callback_data'=>'menu_ent_gratis']],
            [['text'=>'👑 Entidades VIP','callback_data'=>'menu_ent_vip']],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        enviarMensagem($chat_id, "🔁 Escolha a nova entidade:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    if($textTrim === '/testkey'){
        if($groq_key){
            enviarMensagem($chat_id, "✅ Groq Key encontrada! Valor parcial: ".substr($groq_key,0,10)."..."); 
        } else {
            enviarMensagem($chat_id, "⚠️ Groq Key não encontrada! Configure no Render.");
        }
        exit;
    }

    if($textTrim === '/oraculo'){
        $texto = gerarOraculo($entidade);
        enviarMensagem($chat_id, $texto);
        exit;
    }

    if($textTrim === '/demandas'){
        // abre menu demandas
        $keyboard = [
            [['text'=>'Demandas Leves (Grátis)','callback_data'=>'demanda_leve']],
            [['text'=>'Demandas Pesadas (VIP)','callback_data'=>'demanda_pesada']],
            [['text'=>'⬅️ Voltar','callback_data'=>'menu_main']]
        ];
        enviarMensagem($chat_id, "⚠️ Demandas - escolha:", ["inline_keyboard"=>$keyboard]);
        exit;
    }

    // menus de tutoriais por comando direto
    $tutorials = ['/banho','/protecao','/limpeza','/significado','/exu','/orientacao','/faq'];
    if(in_array($textTrim, $tutorials)){
        $menus = [
            "/banho" => "🛁 Banhos espirituais:\n- Lavanda: paz e relaxamento.\n- Camomila: sono e tranquilidade.\n- Arruda: proteção e limpeza energética.",
            "/protecao" => "🛡️ Proteção espiritual:\n- Fechamento de corpo.\n- Amuletos de proteção.\n- Rezas para afastar inveja.",
            "/limpeza" => "✨ Limpeza energética:\n- Defumação com ervas.\n- Banhos de ervas.\n- Técnicas de purificação da casa.",
            "/significado" => "💭 Significados:\n- Sonhos e sinais da vida.\n- Como interpretar mensagens espirituais.",
            "/exu" => "🔥 Ensinos sobre Exu e Pombagira:\n- Caminhos e energias.\n- Proteção e abertura de caminhos.",
            "/orientacao" => "📝 Conselho espiritual pessoal:\n- Escuta, reflexão e prática de proteção.\n- Fortalecimento interior.",
            "/faq" => "❓ Dúvidas frequentes:\n- Explicações sobre Umbanda e rituais.\n- Orientações espirituais."
        ];
        enviarMensagem($chat_id, $menus[$textTrim]);
        exit;
    }

    // se chegou aqui: mensagem livre -> responde com a entidade selecionada (se houver)
    //  - checa se existe entidade escolhida
    if(!$entidade){
        enviarMensagem($chat_id, "Filho, antes de começar escolha uma entidade com /start (ou clique no menu). Quer que eu abra o menu pra você?");
        exit;
    }

    // bloqueio de pedidos perigosos
    if(containsProhibited($message)){
        enviarMensagem($chat_id, "⚠️ Filho, não posso ajudar com pedidos de dano ou vingança. Posso, porém, orientar rituais de proteção, limpeza e fortalecimento. Deseja isso?");
        exit;
    }

    // checa Groq key
    if(!$groq_key){
        enviarMensagem($chat_id, "⚠️ Chave Groq não configurada no servidor. Use /testkey para verificar.");
        exit;
    }

    // prepara prompt e chama Groq
    $system_prompt = getPrompt($entidade);
    $resp = groqChat($groq_key, $system_prompt, $message);
    $json = json_decode($resp, true);
    if(!$json){
        log_write("groqChat decode fail: " . $resp);
        enviarMensagem($chat_id, "⚠️ Os guias estão silenciosos agora. Tente novamente mais tarde.");
        exit;
    }
    // tenta extrair resposta, considerando variações no retorno
    $reply_text = null;
    if(isset($json['choices'][0]['message']['content'])){
        $reply_text = $json['choices'][0]['message']['content'];
    } elseif(isset($json['choices'][0]['text'])){
        $reply_text = $json['choices'][0]['text'];
    } else {
        $reply_text = "⚠️ Os guias estão silenciosos agora, tente novamente.";
    }

    // envia resposta
    enviarMensagem($chat_id, $reply_text);
    exit;
}

// fallback
log_write("Nada processado para update.");