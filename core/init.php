<?php
$core_path = dirname(__FILE__);

// GETのパラメータで指定されたファイル名がcore/pagesのフォルダ内にあるかを調べる（セキュリティ対策用）
$file_exists = file_exists("{$core_path}/pages/{$_GET['page']}.php");

// GETのパラメータでpageがない場合 or 指定されたファイルが存在しない場合はリダイレクト
if (empty($_GET['page']) || $file_exists == false ) {
	header('Location: index.php?page=inbox');
}
// GETのパラメータ（?page=hogehoge）で指定されたファイルを自動的に読み込む為のスクリプト
$include_file = "{$core_path}/pages/{$_GET['page']}.php";

// ログインしていないユーザーがloginページにアクセスした場合のリダイレクト処理
session_start();
if (empty($_SESSION['user_id']) && $_GET['page'] != 'login') {
	header('Location: index.php?page=login');
}

// DB接続
$host = "localhost";
$username = "root";
$password = "root";
$dbname = "pm_simple";

$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
	error_log($mysqli->connect_error);
	exit;
}

include("{$core_path}/function/user.php");
if (isset($_POST['user_name'], $_POST['user_password'])) {
	if (validate_credentials($_POST['user_name'], $_POST['user_password'], $mysqli) === true ) {
		header('Location: index.php?page=inbox');
	} else {
		echo "ユーザー名とパスワードが一致しません。";
	}
}

include("{$core_path}/function/message.php");