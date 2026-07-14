<?php

// Get bot token and password from environment variables
define("BOT_TOKEN", getenv("8690487459:AAEj-a0X2x2FfQPPUEpcCnMxrtdI-O0XIQg"));
define("PASSWORD", getenv("F4KU"));

// API Details
define("API1_NAME", "015XXXXXXXX");
define("API1_URL", "https://devbd.my.id/sms.php?key=AM–MRXRPSh2PU&number=");
define("API2_NAME", "EnsureGroup");
define("API2_URL", "https://darktube.serv00.net/api?api_key=SMS_6079418217_ec2b63e94e054c563b3eaabdc39246ab&number=");

// Telegram API URL
define("API_URL", "https://api.telegram.org/bot" . BOT_TOKEN . "/");

// Get incoming update
$update = json_decode(file_get_contents("php://input"), true);

if (isset($update["message"])) {
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = $message["text"];

    if ($text == "/start") {
        sendMessage($chat_id, "বটটি ব্যবহার করার জন্য অনুগ্রহ করে পাসওয়ার্ড দিন:");
    } elseif ($text == PASSWORD) {
        showMainMenu($chat_id);
    } elseif (strpos($text, "api1_num_") === 0) {
        // Handle number for API1
        $number = str_replace("api1_num_", "", $text);
        sendMessage($chat_id, "API1 এর জন্য মেসেজটি লিখুন (মেসেজের শুরুতে 'api1_msg_$number|' লিখুন):");
    } elseif (strpos($text, "api1_msg_") === 0) {
        // Handle message for API1: api1_msg_NUMBER|MESSAGE
        $parts = explode("|", str_replace("api1_msg_", "", $text), 2);
        if (count($parts) == 2) {
            $response = sendSMS(API1_URL, $parts[0], $parts[1]);
            sendMessage($chat_id, "API1 থেকে প্রতিক্রিয়া: " . htmlspecialchars($response));
            showMainMenu($chat_id);
        }
    } elseif (strpos($text, "api2_num_") === 0) {
        // Handle number for API2
        $number = str_replace("api2_num_", "", $text);
        sendMessage($chat_id, "API2 এর জন্য মেসেজটি লিখুন (মেসেজের শুরুতে 'api2_msg_$number|' লিখুন):");
    } elseif (strpos($text, "api2_msg_") === 0) {
        // Handle message for API2: api2_msg_NUMBER|MESSAGE
        $parts = explode("|", str_replace("api2_msg_", "", $text), 2);
        if (count($parts) == 2) {
            $response = sendSMS(API2_URL, $parts[0], $parts[1]);
            sendMessage($chat_id, "API2 থেকে প্রতিক্রিয়া: " . htmlspecialchars($response));
            showMainMenu($chat_id);
        }
    } else {
        // Since we are stateless, we will guide the user on how to send number and message
        sendMessage($chat_id, "অনুগ্রহ করে সঠিক ফরম্যাটে তথ্য দিন অথবা /start দিয়ে শুরু করুন।");
    }
} elseif (isset($update["callback_query"])) {
    $callback_query = $update["callback_query"];
    $chat_id = $callback_query["message"]["chat"]["id"];
    $message_id = $callback_query["message"]["message_id"];
    $data = $callback_query["data"];

    switch ($data) {
        case "api1":
            editMessageText($chat_id, $message_id, API1_NAME . " এর জন্য নাম্বারটি এভাবে লিখুন: \n`api1_num_01XXXXXXXXX`", null, true);
            break;
        case "api2":
            editMessageText($chat_id, $message_id, API2_NAME . " এর জন্য নাম্বারটি এভাবে লিখুন: \n`api2_num_01XXXXXXXXX`", null, true);
            break;
        case "back":
            showMainMenu($chat_id, $message_id);
            break;
    }
}

// --- Helper Functions ---

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = ["chat_id" => $chat_id, "text" => $text, "parse_mode" => "Markdown"];
    if ($reply_markup) $data["reply_markup"] = json_encode($reply_markup);
    $options = ["http" => ["header" => "Content-type: application/x-www-form-urlencoded\r\n", "method" => "POST", "content" => http_build_query($data)]];
    $context = stream_context_create($options);
    return file_get_contents(API_URL . "sendMessage", false, $context);
}

function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $markdown = false) {
    $data = ["chat_id" => $chat_id, "message_id" => $message_id, "text" => $text];
    if ($markdown) $data["parse_mode"] = "Markdown";
    if ($reply_markup) $data["reply_markup"] = json_encode($reply_markup);
    $options = ["http" => ["header" => "Content-type: application/x-www-form-urlencoded\r\n", "method" => "POST", "content" => http_build_query($data)]];
    $context = stream_context_create($options);
    return file_get_contents(API_URL . "editMessageText", false, $context);
}

function sendSMS($api_url, $number, $message) {
    if (strpos($api_url, "devbd.my.id") !== false) {
        $full_url = $api_url . $number . "&msg=" . urlencode($message);
    } else if (strpos($api_url, "darktube.serv00.net") !== false) {
        $full_url = $api_url . $number . "&message=" . urlencode($message);
    } else {
        return "Invalid API URL.";
    }
    return file_get_contents($full_url) ?: "API request failed.";
}

function showMainMenu($chat_id, $message_id = null) {
    $keyboard = ["inline_keyboard" => [[["text" => API1_NAME, "callback_data" => "api1"]], [["text" => API2_NAME, "callback_data" => "api2"]]]];
    if ($message_id) {
        editMessageText($chat_id, $message_id, "একটি API নির্বাচন করুন:", $keyboard);
    } else {
        sendMessage($chat_id, "একটি API নির্বাচন করুন:", $keyboard);
    }
}

?>
