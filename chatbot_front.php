<?php
session_start();
$username = $_SESSION['user']['username'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI Nutritionist Chatbot</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f7f7f7;
      padding: 40px;
    }

    h2 {
      text-align: center;
      font-size: 28px;
    }

    .chat-container {
      max-width: 900px;
      height: 600px;
      margin: 30px auto;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      padding: 20px;
    }

    .chat-box {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      border-radius: 10px;
      border: 1px solid #ddd;
      font-size: 18px;
      background-color: #fefefe;
    }

    .chat-bubble {
      max-width: 70%;
      padding: 12px 16px;
      margin: 8px 0;
      border-radius: 12px;
      white-space: pre-line;
      line-height: 1.5;
    }

    .user-msg {
      background-color: #d9fdd3;
      align-self: flex-start;
      text-align: left;
    }

    .bot-msg {
      background-color: #eef2ff;
      align-self: flex-end;
      text-align: left;
    }

    .loading {
      color: #888;
      font-style: italic;
      margin-top: 10px;
      font-size: 16px;
    }

    form {
      display: flex;
      margin-top: 15px;
    }

    textarea {
      flex: 1;
      padding: 14px;
      font-size: 18px;
      border-radius: 8px 0 0 8px;
      border: 1px solid #ccc;
      resize: vertical;
      font-family: inherit;
      line-height: 1.5;
    }

    button {
      padding: 14px 24px;
      background-color: #4a60ff;
      color: white;
      border: none;
      font-size: 18px;
      cursor: pointer;
      border-radius: 0 8px 8px 0;
    }

    button:hover {
      background-color: #3741d3;
    }
  </style>
</head>
<body>

<h2>Welcome to AI Nutritionist</h2>

<div class="chat-container">
  <div class="chat-box" id="chatBox"></div>
  <div class="loading" id="loading" style="display: none;">Generating response...</div>
  <form id="chatForm">
    <textarea id="userInput" placeholder="Type your message..." rows="2" required></textarea>
    <button type="submit">Send</button>
  </form>
</div>

<script>
  const chatBox = document.getElementById("chatBox");
  const chatForm = document.getElementById("chatForm");
  const userInput = document.getElementById("userInput");
  const loadingIndicator = document.getElementById("loading");

  userInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      chatForm.requestSubmit();
    }
  });

  chatForm.onsubmit = async function(e) {
  e.preventDefault();
  const message = userInput.value.trim();
  if (!message) return;

  appendMessage(message, "user-msg");
  userInput.value = "";
  loadingIndicator.style.display = "block";

  let sessionId = localStorage.getItem("session_id");
  if (!sessionId) {
    sessionId = crypto.randomUUID();
    localStorage.setItem("session_id", sessionId);
  }

  try {
    const response = await fetch("http://127.0.0.1:5000/chat", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        message: message,
        session_id: sessionId
      })
    });

    const data = await response.json();
    appendMessage(data.reply, "bot-msg");
  } catch (err) {
    console.error("Fetch failed:", err);
    appendMessage("Failed to get response. Please try again.", "bot-msg");
  }

  loadingIndicator.style.display = "none";
  chatBox.scrollTop = chatBox.scrollHeight;
};


  function appendMessage(message, className) {
    const div = document.createElement("div");
    div.className = "chat-bubble " + className;
    div.innerHTML = message.replace(/\n/g, "<br>");
    chatBox.appendChild(div);
  }
</script>

</body>
</html>
