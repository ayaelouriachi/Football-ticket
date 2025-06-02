<?php
function displayMessages() {
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        if (isset($_SESSION["message_$type"])) {
            echo "<div class='alert alert-$type' id='flash-message'>
                    <span class='alert-text'>{$_SESSION["message_$type"]}</span>
                    <button class='alert-close' onclick='closeAlert()'>&times;</button>
                  </div>";
            unset($_SESSION["message_$type"]);
        }
    }
}

function setMessage($message, $type = 'info') {
    $_SESSION["message_$type"] = $message;
}
?>