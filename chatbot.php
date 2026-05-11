<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

/*
|--------------------------------------------------------------------------
| HANDLE AJAX REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['message']))
{
    $message = trim($_POST['message']);

    if ($message == "")
    {
        exit("Empty message");
    }

    // Escape user message
    $safeMessage = mysqli_real_escape_string($conn, $message);

    // GROQ API KEY
    $apiKey = "YOUR_API_KEY";

    // API DATA
    $data = [
        "model" => "llama-3.3-70b-versatile",

        "messages" => [

    [
        "role" => "system",
        "content" => "Reply shortly and clearly in 30 to 50 lines only."
    ],

    [
        "role" => "user",
        "content" => $message
    ]
]
    ];

    // CURL START
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]);

    $response = curl_exec($ch);

    // CURL ERROR
    if (curl_errno($ch))
    {
        echo "CURL ERROR: " . curl_error($ch);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    // DECODE RESPONSE
    $result = json_decode($response, true);

    // API ERROR
    if (isset($result['error']))
    {
        echo "API ERROR: " . $result['error']['message'];
        exit;
    }

    // GET BOT REPLY
    if (isset($result['choices'][0]['message']['content']))
    {
        $reply = trim($result['choices'][0]['message']['content']);

        // Escape for DB
        $safeReply = mysqli_real_escape_string($conn, $reply);

        // SAVE CHAT
        $sql = "INSERT INTO chatbot(user_message, bot_reply)
                VALUES('$safeMessage', '$safeReply')";

        mysqli_query($conn, $sql);

        // RETURN ONLY REPLY
        echo $reply;

        exit;
    }
    else
    {
        echo "No response from AI";
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ChatGPT Style Chatbot</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}

body{
    background:#343541;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.chat-container{
    width:100%;
    max-width:900px;
    height:95vh;
    background:#444654;
    display:flex;
    flex-direction:column;
    border-radius:10px;
    overflow:hidden;
}

/* HEADER */

.chat-header{
    background:#202123;
    color:white;
    padding:20px;
    font-size:22px;
    font-weight:bold;
    text-align:center;
}

/* CHAT AREA */

#chat-box{
    flex:1;
    overflow-y:auto;
    padding:25px;
    display:flex;
    flex-direction:column;
    gap:18px;
    scroll-behavior:smooth;
}

/* USER MESSAGE */

.user-message{
    align-self:flex-end;
    background:#19c37d;
    color:white;
    padding:14px 18px;
    border-radius:15px 15px 0px 15px;
    max-width:70%;
    line-height:1.6;
    font-size:15px;
    word-wrap:break-word;
    box-shadow:0 2px 8px rgba(0,0,0,0.2);
}

/* BOT MESSAGE */

.bot-message{
    align-self:flex-start;
    background:#2d2f3a;
    color:white;
    padding:16px 18px;
    border-radius:15px 15px 15px 0px;
    max-width:75%;
    line-height:1.8;
    font-size:15px;
    white-space:pre-wrap;
    word-wrap:break-word;
    overflow-wrap:break-word;
    box-shadow:0 2px 8px rgba(0,0,0,0.2);
}

/* INPUT AREA */

.input-area{
    background:#40414f;
    padding:20px;
    display:flex;
    gap:10px;
}

.input-area input{
    flex:1;
    padding:15px;
    border:none;
    outline:none;
    border-radius:8px;
    background:#2d2f3a;
    color:white;
    font-size:16px;
}

.input-area input::placeholder{
    color:#aaa;
}

.input-area button{
    background:#19c37d;
    color:white;
    border:none;
    padding:15px 25px;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
}

.input-area button:hover{
    opacity:0.9;
}

/* SCROLLBAR */

#chat-box::-webkit-scrollbar{
    width:6px;
}

#chat-box::-webkit-scrollbar-thumb{
    background:#888;
    border-radius:10px;
}

</style>
</head>
<body>

<div class="chat-container">

    <!-- HEADER -->
    <div class="chat-header">
        AI Chatbot
    </div>

    <!-- CHAT BOX -->
    <div id="chat-box"></div>

    <!-- INPUT -->
    <div class="input-area">

        <input
            type="text"
            id="message"
            placeholder="Message AI Chatbot..."
            onkeypress="handleEnter(event)"
        >

        <button onclick="sendMessage()">
            Send
        </button>

    </div>

</div>

<script>

function formatReply(text)
{
    return text
        .replace(/\*\*(.*?)\*\*/g, "<b>$1</b>")
        .replace(/\n/g, "<br><br>");
}

function handleEnter(event)
{
    if(event.key === "Enter")
    {
        sendMessage();
    }
}

async function sendMessage()
{
    let messageInput = document.getElementById("message");

    let message = messageInput.value.trim();

    if(message === "")
    {
        return;
    }

    let chatBox = document.getElementById("chat-box");

    // USER MESSAGE
    chatBox.innerHTML += `
        <div class="user-message">
            ${message}
        </div>
    `;

    messageInput.value = "";

    chatBox.scrollTop = chatBox.scrollHeight;

    // BOT THINKING
    chatBox.innerHTML += `
        <div class="bot-message" id="typing">
            Typing...
        </div>
    `;

    chatBox.scrollTop = chatBox.scrollHeight;

    // FETCH
    let response = await fetch("chatbot.php", {

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:"message=" + encodeURIComponent(message)
    });

    let botReply = await response.text();

    // REMOVE TYPING
    document.getElementById("typing").remove();

    // BOT REPLY
    chatBox.innerHTML += `
    <div class="bot-message">
        ${formatReply(botReply)}
    </div>
`;

    chatBox.scrollTop = chatBox.scrollHeight;
}

</script>

</body>
</html>