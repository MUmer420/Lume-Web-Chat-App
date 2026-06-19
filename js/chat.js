var lastId = 0;
var POLL_INTERVAL = 1500;
var isPolling = false;

// Universal sanitation engine to isolate output text and prevent client-side XSS
function escapeHTML(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// --- 1. Message Polling & Rendering Engine ---
function loadMessages(replaceAll) {
    if (typeof receiver_id === "undefined") return;
    if (isPolling) return;
    isPolling = true;

    var fetchId = replaceAll ? 0 : lastId;

    var xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_messages.php?user=" + receiver_id + "&last_id=" + fetchId + "&t=" + Date.now(), true);

    xhr.onload = function () {
        isPolling = false;
        if (xhr.status !== 200) return;

        var data;
        try { data = JSON.parse(xhr.responseText); }
        catch (e) { return; }

        var box = document.getElementById("chat-box");
        if (!box) return;

        // Always remove optimistic bubble references on every poll synchronization tick
        removeOptimistic();

        // A. Handle rendering incoming new messages
        if (data.messages && data.messages.length > 0) {
            var wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;

            if (fetchId === 0) {
                box.innerHTML = ""; // Absolute viewport clear for clean refresh
            }

            data.messages.forEach(function(msg) {
                if (document.getElementById("msg-" + msg.id)) return;

                var bubble = document.createElement("div");
                bubble.id = "msg-" + msg.id;
                bubble.setAttribute("data-id", msg.id);

                if (msg.is_deleted) {
                    bubble.className = (msg.is_mine ? "my-message" : "other-message") + " message-deleted-style";
                } else {
                    bubble.className = msg.is_mine ? "my-message" : "other-message";
                }

                var innerHTML = '<div class="message-text">' + escapeHTML(msg.text) + '</div>';
                bubble.innerHTML = innerHTML;
                
                if (!msg.is_deleted) {
                    bubble.style.cursor = "pointer";
                    bubble.addEventListener("click", function() {
                        promptDelete(msg.id, msg.is_mine);
                    });
                }

                box.appendChild(bubble);
            });

            if (wasAtBottom || fetchId === 0) box.scrollTop = box.scrollHeight;
        }

        // REAL-TIME SYNC: Apply real-time global deletion updates without forcing a full refresh
        if (data.deleted_ids && data.deleted_ids.length > 0) {
            data.deleted_ids.forEach(function(delId) {
                var localBubble = document.getElementById("msg-" + delId);
                if (localBubble && !localBubble.classList.contains("message-deleted-style")) {
                    // Update layout classes and clear trigger pointers safely
                    localBubble.className = (localBubble.classList.contains("my-message") ? "my-message" : "other-message") + " message-deleted-style";
                    
                    var textNode = localBubble.querySelector(".message-text");
                    if (textNode) {
                        textNode.textContent = "This message was deleted.";
                    }
                    
                    // Clone the element to wipe all active click event listeners cleanly
                    var cleanBubble = localBubble.cloneNode(true);
                    cleanBubble.style.cursor = "default";
                    localBubble.parentNode.replaceChild(cleanBubble, localBubble);
                }
            });
        }

        if (data.last_id > lastId) lastId = data.last_id;
    };

    xhr.onerror = function () { isPolling = false; };
    xhr.send();
}


function appendOptimistic(message) {
    var box = document.getElementById("chat-box");
    if (!box) return;

    var div = document.createElement("div");
    div.className = "my-message";
    div.setAttribute("data-optimistic", "1");
    div.innerHTML = '<div class="message-text">' + escapeHTML(message) + '</div>';
    
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function removeOptimistic() {
    document.querySelectorAll("[data-optimistic='1']").forEach(function(el) {
        if (el.parentNode) el.parentNode.removeChild(el);
    });
}

// --- 3. Message Outbound Submission Flow ---
var form = document.getElementById("message-form");
if (form) {
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        var input   = document.getElementById("message");
        var message = input.value.trim();
        if (message === "") return;

        appendOptimistic(message);
        input.value = "";
        input.focus();
        input.disabled = true;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "send_message.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onload = function () {
            input.disabled = false;
            if (xhr.status !== 200 || xhr.responseText.trim() !== "success") {
                removeOptimistic();
                if (xhr.status === 200 && xhr.responseText.trim() !== "success") {
                    alert("Message delivery rejected: " + xhr.responseText);
                }
            }
        };

        xhr.onerror = function () {
            input.disabled = false;
            removeOptimistic();
        };

        xhr.send(
            "message="      + encodeURIComponent(message) +
            "&receiver_id=" + encodeURIComponent(receiver_id) +
            "&csrf_token="  + encodeURIComponent(csrf_token)
        );
    });
}

// --- 4. User Block Actions ---
var blockBtn = document.getElementById('block-btn');
if (blockBtn) {
    blockBtn.addEventListener('click', function () {
        var targetId = this.getAttribute('data-receiver');
        if (!confirm("Are you sure you want to block this user?")) return;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "block_user.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            if (xhr.status === 200 && xhr.responseText.trim() === "success") {
                alert("User has been blocked successfully.");
                window.location.href = "users.php";
            } else {
                alert(xhr.responseText);
            }
        };
        xhr.send("blocked_id=" + encodeURIComponent(targetId) + "&csrf_token=" + encodeURIComponent(csrf_token));
    });
}

// --- 5. Database Message Erasure Pipeline ---
function promptDelete(messageId, isMyMessage) {
    var targetBubble = document.getElementById("msg-" + messageId);
    if (targetBubble && targetBubble.classList.contains("message-deleted-style")) return;

    var choice = "";
    
    if (isMyMessage) {
        var mode = prompt("Type '1' to Delete for Yourself\nType '2' to Delete for Everyone:");
        if (mode === "1") choice = "self";
        if (mode === "2") choice = "everyone";
    } else {
        if (confirm("Delete this message for yourself?")) {
            choice = "self";
        }
    }

    if (choice === "") return;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "delete_messages.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    
    xhr.onload = function () {
        if (xhr.status === 200 && xhr.responseText.trim() === "success") {
            loadMessages(true); 
        } else {
            alert(xhr.responseText);
        }
    };

    var payload = "message_id=" + encodeURIComponent(messageId) + 
                  "&mode=" + encodeURIComponent(choice) + 
                  "&csrf_token=" + encodeURIComponent(csrf_token);
                  
    xhr.send(payload);
}

// --- 6. Pipeline Synchronization Initializer Loops ---
loadMessages(true);
setInterval(function () { loadMessages(false); }, POLL_INTERVAL);