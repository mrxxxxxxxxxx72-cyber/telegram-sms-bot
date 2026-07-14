<?php

// Get bot token and password from environment variables
define("BOT_TOKEN", getenv("TELEGRAM_BOT_TOKEN"));
define("PASSWORD", getenv("BOT_PASSWORD"));

// API Details
define("API1_NAME", "015XXXXXXXX");
define("API1_URL", "https://devbd.my.id/sms.php?key=AM–MRXRPSh2PU&number=");
define("API2_NAME", "EnsureGroup");
define("API2_URL", "https://darktube.serv00.net/api?api_key=SMS_6079418217_ec2b63e94e054c563b3eaabdc39246ab&number=");

// Telegram API URL
define("API_URL", "https://api.telegram.org/bot" . BOT_TOKEN . "/");

// Function to send Telegram messages
function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML",
    ];
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    $options = [
        "http" => [
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    file_get_contents(API_URL . "sendMessage", false, $context);
}

// Function to edit Telegram messages
function editMessageText($chat_id, $message_id, $text, $reply_markup = null) {
    $data = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "HTML",
    ];
    if ($reply_markup) {
        $data["reply_markup"] = json_encode($reply_markup);
    }
    $options = [
        "http" => [
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    file_get_contents(API_URL . "editMessageText", false, $context);
}

// Function to send API request
function sendSMS($api_url, $number, $message) {
    if (strpos($api_url, "devbd.my.id") !== false) {
        $full_url = $api_url . $number . "&msg=" . urlencode($message);
    } else if (strpos($api_url, "darktube.serv00.net") !== false) {
        $full_url = $api_url . $number . "&message=" . urlencode($message);
    } else {
        return "Invalid API URL.";
    }

    $response = file_get_contents($full_url);
    return $response ? $response : "API request failed.";
}

// Get incoming update
$update = json_decode(file_get_contents("php://input"), true);

// Simple session management for password (not persistent across restarts)
session_start();

if (isset($update["message"])) {
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = $message["text"];
    $user_id = $message["from"]["id"];

    if ($text == "/start") {
        if (isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] === true) {
            showMainMenu($chat_id);
        } else {
            $_SESSION["step"] = "waiting_for_password";
            sendMessage($chat_id, "বটটি ব্যবহার করার জন্য অনুগ্রহ করে পাসওয়ার্ড দিন:");
        }
    } elseif (isset($_SESSION["step"]) && $_SESSION["step"] == "waiting_for_password") {
        if ($text == PASSWORD) {
            $_SESSION["authenticated"] = true;
            $_SESSION["step"] = "main_menu";
            sendMessage($chat_id, "সফলভাবে লগইন করা হয়েছে!");
            showMainMenu($chat_id);
        } else {
            sendMessage($chat_id, "ভুল পাসওয়ার্ড। আবার চেষ্টা করুন:");
        }
    } elseif (isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] === true) {
        switch ($_SESSION["step"]) {
            case "waiting_for_number_api1":
                $_SESSION["number"] = $text;
                $_SESSION["step"] = "waiting_for_message_api1";
                sendMessage($chat_id, "মেসেজটি লিখুন:");
                break;
            case "waiting_for_message_api1":
                $response = sendSMS(API1_URL, $_SESSION["number"], $text);
                sendMessage($chat_id, "API1 থেকে প্রতিক্রিয়া: " . htmlspecialchars($response));
                showMainMenu($chat_id);
                break;
            case "waiting_for_number_api2":
                $_SESSION["number"] = $text;
                $_SESSION["step"] = "waiting_for_message_api2";
                sendMessage($chat_id, "মেসেজটি লিখুন:");
                break;
            case "waiting_for_message_api2":
                $response = sendSMS(API2_URL, $_SESSION["number"], $text);
                sendMessage($chat_id, "API2 থেকে প্রতিক্রিয়া: " . htmlspecialchars($response));
                showMainMenu($chat_id);
                break;
            default:
                showMainMenu($chat_id);
                break;
        }
    } else {
        sendMessage($chat_id, "বটটি ব্যবহার করার জন্য অনুগ্রহ করে /start কমান্ড দিন এবং পাসওয়ার্ড দিয়ে লগইন করুন।");
    }
} elseif (isset($update["callback_query"])) {
    $callback_query = $update["callback_query"];
    $chat_id = $callback_query["message"]["chat"]["id"];
    $message_id = $callback_query["message"]["message_id"];
    $data = $callback_query["data"];
    $user_id = $callback_query["from"]["id"];

    if (!isset($_SESSION["authenticated"]) || $_SESSION["authenticated"] !== true) {
        editMessageText($chat_id, $message_id, "অনুগ্রহ করে /start কমান্ড দিন এবং পাসওয়ার্ড দিয়ে লগইন করুন।");
        return;
    }

    switch ($data) {
        case "api1":
            $_SESSION["step"] = "waiting_for_number_api1";
            editMessageText($chat_id, $message_id, API1_NAME . " এর জন্য নাম্বার দিন:");
            break;
        case "api2":
            $_SESSION["step"] = "waiting_for_number_api2";
            editMessageText($chat_id, $message_id, API2_NAME . " এর জন্য নাম্বার দিন:");
            break;
        case "back_to_main_menu":
            $_SESSION["step"] = "main_menu";
            showMainMenu($chat_id, $message_id);
            break;
        default:
            editMessageText($chat_id, $message_id, "অজানা কমান্ড।");
            showMainMenu($chat_id, $message_id);
            break;
    }
}

// Helper functions for menus
function showMainMenu($chat_id, $message_id = null) {
    $keyboard = [
        "inline_keyboard" => [
            [["text" => API1_NAME, "callback_data" => "api1"]],
            [["text" => API2_NAME, "callback_data" => "api2"]],
        ],
    ];
    if ($message_id) {
        editMessageText($chat_id, $message_id, "একটি API নির্বাচন করুন:", $keyboard);
    } else {
        sendMessage($chat_id, "একটি API নির্বাচন করুন:", $keyboard);
    }
}

?>
