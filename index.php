<?php
/**
 * Vignette 实验问卷工具 v2
 * 支持随机分组 + 完整实验流程
 */

// 配置
define('DATA_DIR', __DIR__ . '/data');
define('SURVEYS_FILE', DATA_DIR . '/surveys.json');
define('ADMIN_PASSWORD', 'admin123');

// 确保数据目录存在
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// 路由
$action = $_GET['action'] ?? 'home';
$id = $_GET['id'] ?? null;

switch ($action) {
    case 'admin':
        require_once __DIR__ . '/routes/admin.php';
        break;
    case 'survey':
        require_once __DIR__ . '/routes/survey.php';
        break;
    case 'export':
        require_once __DIR__ . '/routes/export.php';
        break;
    case 'api':
        require_once __DIR__ . '/routes/api.php';
        break;
    default:
        require_once __DIR__ . '/routes/home.php';
}

// ========== 工具函数 ==========

function get_surveys() {
    if (!file_exists(SURVEYS_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(SURVEYS_FILE), true) ?: [];
}

function save_surveys($surveys) {
    file_put_contents(SURVEYS_FILE, json_encode($surveys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function get_responses($survey_id) {
    $file = DATA_DIR . "/responses_{$survey_id}.json";
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_responses($survey_id, $responses) {
    $file = DATA_DIR . "/responses_{$survey_id}.json";
    file_put_contents($file, json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function check_admin_login() {
    session_start();
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            if ($_POST['password'] === ADMIN_PASSWORD) {
                $_SESSION['admin'] = true;
                header('Location: ?action=admin');
                exit;
            } else {
                return '密码错误';
            }
        }
        // 显示登录页
        echo '<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        h1 { font-size: 24px; margin-bottom: 8px; color: #333; text-align: center; }
        .subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 14px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        input[type="password"] { width: 100%; padding: 14px; border: 2px solid #e8e8e8; border-radius: 10px; font-size: 16px; margin-bottom: 20px; transition: border-color 0.2s; }
        input[type="password"]:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 500; cursor: pointer; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); }
        .error { color: #e74c3c; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .hint { margin-top: 20px; color: #999; font-size: 13px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 管理员登录</h1>
        <p class="subtitle">Vignette 实验问卷系统</p>
        ' . (isset($login_error) ? "<p class='error'>{$login_error}</p>" : "") . '
        <form method="POST">
            <label>管理员密码</label>
            <input type="password" name="password" placeholder="请输入密码" required>
            <button type="submit">登 录</button>
        </form>
        <p class="hint">默认密码: admin123</p>
    </div>
</body>
</html>';
        exit;
    }
    return true;
}