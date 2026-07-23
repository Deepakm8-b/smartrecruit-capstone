<?php
/**
 * login.php — SmartRecruit login (UC02)
 * Author: Deepak Bhandari (2443463047)
 *
 * Verifies credentials with password_verify() against the BCrypt hash,
 * regenerates the session ID on success (prevents session fixation),
 * and stores user_id + role in the session for every later page.
 *
 * !! COLUMN NAME CHECK !!
 * Assumes users columns: user_id, email, password_hash, role.
 */

session_start();
require_once 'db.php';

$error = '';
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, role FROM users WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Success: new session ID, store identity, go to dashboard
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['role'];

        header('Location: dashboard.php');
        exit;
    }

    // One generic message for both wrong email and wrong password —
    // never reveal which one failed (information disclosure risk).
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main style="max-width:420px;margin:60px auto;padding:0 16px;">
        <h1>Log in</h1>

        <?php if ($flash): ?>
            <p style="color:#0a7a2f;"><?= htmlspecialchars($flash) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p style="color:#b00020;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <label>Email
                <input type="email" name="email" required>
            </label><br><br>

            <label>Password
                <input type="password" name="password" required>
            </label><br><br>

            <button type="submit">Log in</button>
        </form>

        <p>New here? <a href="register.php">Create an account</a></p>
    </main>
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>
