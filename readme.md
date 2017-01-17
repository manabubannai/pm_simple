# タイトル
PHPでプライベートメッセージ機能を作成する方法

# 仕様
・１対１のプライベートメッセージ（チャット）機能
・複数人のチャット機能
・会員登録機能

- - -

# １．PHPプライベートメッセージ機能のフォルダ設計をする
	<a href="https://manablog.org/wp-content/uploads/2017/01/folder.jpg"><img src="https://manablog.org/wp-content/uploads/2017/01/folder.jpg" alt="" width="1996" height="740" class="alignnone size-full wp-image-6927" /></a>
	下記のとおりです。
	index.php
		/core
			init.php
		/function
			message.php
			user.php
		/pages
			inbox.php
			login.php
			logout.php
			new_conversation.php
			view_conversation.php
	各種ファイルがどういった役割を果たすのかは、順を追って説明していきます。

# ２．テンプレートシステムを作成する
	まずはテンプレートシステムを作っていきます。
	index.phpを読み込むと、条件に応じで最適な画面が出力される仕組みです。
	※SEOに疑問をお持ちの方へ
	メッセージ機能においてはSEO対策は不要ですので、本記事はSEOには触れていません。
## ２−１．メッセージシステムの核となるindex.phpを作成する（新規作成ファイル：index.php）
	<?php
	include('core/init.php');
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<title>PHP：メッセージシステム</title>
	</head>
	<body>
	<h1>PHP：メッセージシステム</h1>
	<?php
		// ここでテンプレートファイルを読み込む。$include_fileは後ほど作成します。
		include($include_file);
	?>
	</body>
	</html>
## ２−２．core/init.phpを作成する（新規作成ファイル：core/init.php）
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
##	以上で、超簡易的なテンプレートシステムが完了しました。
	index.php?page=inbox.php　：core/pages/inbox.phpが読み込まれます
	index.php?page=new_conversation.php　：core/pages/new_conversation.phpが読み込まれます
	index.php?page=spam.php　：core/pages/inbox.phpが読み込まれます

## ３．会員登録機能を作成する
## ３−１．ユーザーテーブルを作成する
	DB名：pm_simple
	CREATE TABLE `users` (
	  `user_id` int(8) NOT NULL,
	  `user_name` varchar(20) NOT NULL,
	  `user_password` varchar(60) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	ALTER TABLE `users`
	  ADD PRIMARY KEY (`user_id`);
	ALTER TABLE `users`
	  MODIFY `user_id` int(8) NOT NULL AUTO_INCREMENT;
## ３−２．ユーザーデータを入れる
	INSERT INTO `users` (`user_id`, `user_name`, `user_password`) VALUES
	(1, 'manabu', '$2y$10$GWCPe6X9xaz.CgZLXwShtuFlNGD8kkAT.Ot6bbnnpF/LSWvDqkKCO');
	※パスワード：pass
## ３−３．ログインしていないユーザーがloginページにアクセスした場合のリダイレクト処理を作成する（編集ファイル：core/init.php）
	session_start();
	if (empty($_SESSION['user_id']) && $_GET['page'] != 'login') {
		header('HTTP/1.1 403 FOrbidden');
		header('Location: index.php?page=login');
		die();
	}
## ３−４．ログインフォームを作成する（編集ファイル：core/pages/login.php）
	<a href="https://manablog.org/wp-content/uploads/2017/01/login.jpg"><img src="https://manablog.org/wp-content/uploads/2017/01/login.jpg" alt="" width="768" height="492" class="alignnone size-full wp-image-6928" /></a>
	<h2>ログイン</h2>
	<form action="" method="post">
		<input type="text" name="user_name" placeholder="ユーザー名">
		<input type="password" name="user_password" placeholder="パスワード">
		<input type="submit" value="ログイン">
	</form>
## ３−５．ユーザー認証機能を作成する（編集ファイル：core/init.php）
	// まずはDBへ接続
	$host = "localhost";
	$username = "root";
	$password = "root";
	$dbname = "pm_simple";
	$mysqli = new mysqli($host, $username, $password, $dbname);
	if ($mysqli->connect_error) {
		error_log($mysqli->connect_error);
		exit;
	}

	// ユーザー認証機能
	include("{$core_path}/function/user.php");
	if (isset($_POST['user_name'], $_POST['user_password'])) {
		if (validate_credentials($_POST['user_name'], $_POST['user_password'], $mysqli) === true ) {
			header('Location: index.php?page=inbox');
		} else {
			echo "ユーザー名とパスワードが一致しません。";
		}
	}
## ３−６．validate_credentialsを作成する（編集ファイル：core/function/user.php）
	下記のとおり。尚、このあたりがよく分からない方は、<a href="https://manablog.org/php7-login/">【PHP7対応】ログイン・会員登録機能を作る方法【2016年版】</a>をご覧ください。ちょっとメンドイかもですが、急がば回れです。
	function validate_credentials($username, $password, $mysqli) {
		$username = $mysqli->real_escape_string($username);
		$password = $mysqli->real_escape_string($password);

		$sql = "SELECT
						user_id, user_name, user_password
					FROM
						users
					WHERE
						user_name = '$username'";
		$result = $mysqli->query($sql);

		if ($result->num_rows != 1) {
			return false;
		}

		// パスワード(暗号化済み）とユーザーIDの取り出し
		while ($row = $result->fetch_assoc()) {
			$db_hashed_pwd = $row['user_password'];
			$user_id = $row['user_id'];
		}

		// ハッシュ化されたパスワードがマッチするかどうかを確認
		if (password_verify($password, $db_hashed_pwd)) {
			$_SESSION['user_id'] = $user_id;
			return true;
		} else {
			return false;
		}

	}
## 以上で、会員登録機能が完成しました。
	ログインページから、ID:manabu, パスワード:passでログインできます。

# ４．PHPプライベートメッセージ機能に利用するDB設計をする
	ここからが本番です。
	PHPのプライベートメッセージ機能には３つのテーブルを利用します。
## ４−１．conversationテーブルを作成する
	カンバセーションIDとカンバセーションタイトルを保存するためのDBです。
	CREATE TABLE `conversations` (
	  `conversation_id` int(8) NOT NULL,
	  `conversation_subject` varchar(128) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	ALTER TABLE `conversations`
	  ADD PRIMARY KEY (`conversation_id`);
	ALTER TABLE `conversations`
	  MODIFY `conversation_id` int(8) NOT NULL AUTO_INCREMENT;
## ４−２．conversations_membersテーブルを作成する
	カンバセーションIDとユーザーIDを紐付けるためのDBです。
	尚、conversation_last_viewはメッセージの既読 or 未読判定に利用し、conversation_deletedはメッセージ削除機能に利用します。
	CREATE TABLE `conversations_members` (
	  `conversation_id` int(8) NOT NULL,
	  `user_id` int(8) NOT NULL,
	  `conversation_last_view` int(10) NOT NULL,
	  `conversation_deleted` int(1) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
## ４−３．conversations_ membersテーブルのconversation_ idとuser_idをユニークにする ※
	https://manablog.org/wp-content/uploads/2017/01/01.jpg
	phpmyadminから、indexの部分を選択します。

	https://manablog.org/wp-content/uploads/2017/01/02.jpg
	上記のように設定します。
## ４−４．conversations_messagesテーブルを作成する
	カンバセーションIDとユーザーIDを紐付けるつつ、メッセージ本文を保存するDBです。
	CREATE TABLE `conversations_messages` (
	  `message_id` int(10) NOT NULL,
	  `conversation_id` int(8) NOT NULL,
	  `user_id` int(8) NOT NULL,
	  `message_date` int(10) NOT NULL,
	  `message_text` text NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	ALTER TABLE `conversations_messages`
	  ADD PRIMARY KEY (`message_id`);
	ALTER TABLE `conversations_messages`
	  MODIFY `message_id` int(10) NOT NULL AUTO_INCREMENT;
## 以上で、PHPプライベートメッセージ機能に利用するDB設計が完了しました。
	尚、「日本語でOK」って感じで離脱しそうな方はご安心を。
	現在は抽象的な部分ですが、実際に手を動かして、データを見つつ進めれば混乱がなくなるかと思います。

# ５．新規メッセージの送信フォームとバリデーションを作成する
## ５−１．新規メッセージ送信フォームを作成する（編集ファイル：core/pages/new_conversation.php）
	<form action="" method="post">
		<input type="text" name="to" placeholder="To">
		<br>
		<input type="text" name="subject" placeholder="件名">
		<br>
		<textarea name="body"></textarea>
		<br>
		<input type="submit" value="送信する">
	</form>
## ５−２．新規メッセージ送信フォームのバリデーション機能を作成する（編集ファイル：core/pages/new_conversation.php）
	<?php
	// フォームの送信ボタンが押された時に下記を実行
	if (isset($_POST['to'], $_POST['subject'], $_POST['body'])) {

		$errors = array();

		// 宛名の入力ミスがある場合
		if (empty($_POST['to'])) {
			$errors[] = "宛先を追加してください。";
		} else if (preg_match('#^[a-z, ]+$#i', $_POST['to']) === 0 ) {
			$errors[] = "宛名が間違っています。";
		} else {

			// 下記より、複数ユーザーへのメッセージ送信用の処理
			// ユーザー名を「,」で区切る
			$user_names = explode(',', $_POST['to']);
			foreach ($user_names as $name) {
				$name = trim($name);
			}

			$user_ids = fetch_user_ids($user_names, $mysqli);
			if (count($user_ids) !== count($user_names)) {
				$errors[] = "次のユーザーが見つかりません：" . implode(', ', array_diff($user_names, array_keys($user_ids)));
			}
		}

		if (empty($_POST['subject'])) {
			$errors[] = "件名を入力してください。";
		}
		if (empty($_POST['body'])) {
			$errors[] = "本文を入力してください。";
		}

		if (empty($errors)) {
			// エラーのない場合
			echo "エラーはありません。あとでメッセージ送信機能を作ります。";
		}

	}

	if (isset($errors)) {
		if (empty($errors)) {
			// サクセスメッセージ
			echo "メッセージを送信しました" . "<a href='index.php?page=inbox'>受信箱に戻る</a>";
		} else {
			foreach ($errors as $error) {
				echo $error;
			}
		}
	}
	?>
## ５−３．fetch_ user_idsのファンクションを作成する（編集ファイル：core/function/user.php）
	function fetch_user_ids($user_names, $mysqli) {

		foreach ($user_names as $name) {
			$name = $mysqli->real_escape_string($name);
		}

		$implode_username = implode("','", $user_names);

		$sql = "SELECT
						user_id, user_name
					FROM
						users
					WHERE
						user_name
					IN
					 ('" . $implode_username . "')";
		$result = $mysqli->query($sql);

		$names = array();
		while ($row = $result->fetch_assoc()) {
			$names[$row['user_name']] = $row['user_id'];
		}
		return $names;
	}
## ５−４．補足：バリデーションエラーが発生しても、入力内容を保持する（編集ファイル：core/pages/new_conversation.php）
	<form action="" method="post">
		<input type="text" name="to" placeholder="To" value="<?php if(isset($_POST['to'])) { echo htmlentities($_POST['to']); } ?>">
		<br>
		<input type="text" name="subject" placeholder="件名" value="<?php if(isset($_POST['subject'])) { echo htmlentities($_POST['subject']); } ?>">
		<br>
		<textarea name="body" value="<?php if(isset($_POST['body'])) { echo htmlentities($_POST['body']); } ?>"></textarea>
		<br>
		<input type="submit" value="送信する">
	</form>
## 以上で、新規メッセージ送信フォームをバリデーションが完成しました。

# ６．新規メッセージ送信機能を作成する
## ６−１．新規メッセージ送信用のスクリプトを追加する（編集ファイル：core/pages/new_conversation.php）
	if (empty($errors)) {
		// エラーのない場合
		// echo "エラーはありません。あとでメッセージ送信機能を作ります。";
		create_conversation(array_unique($user_ids), $_POST['subject'], $_POST['body'], $mysqli);
	}
## ６−２．create_conversationのファンクションを作成する（編集ファイル：core/function/message.php）
	<?php

	function create_conversation($user_ids, $subject, $body, $mysqli) {
		$subject = $mysqli->real_escape_string(htmlentities($subject));
		$body = $mysqli->real_escape_string(htmlentities($body));

		// conversationsテーブルに情報を挿入する
		$sql = "INSERT INTO conversations (conversation_subject) VALUES ('$subject')";
		$result = $mysqli->query($sql);

		// conversationsテーブルへ最後に挿入したIDを取得する
		$conversation_id = mysqli_insert_id($mysqli);

		$sql = "INSERT INTO
					conversations_messages (
						conversation_id,
						user_id,
						message_date,
						message_text
					)
					VALUES (
						$conversation_id,
						{$_SESSION['user_id']},
						UNIX_TIMESTAMP(),
						'$body')";
		$result = $mysqli->query($sql);

		// conversations_mmbersテーブルに情報を挿入する
		// 下記は複数ユーザーに対応するための処理
		$values = array();
		$user_ids[] = $_SESSION['user_id'];
		$time = time();

		foreach ($user_ids as $user_id) {
			$user_id = (int) $user_id;
			$values[] = "($conversation_id, $user_id, $time, 0)";
		}

		$sql = "INSERT INTO
					conversations_members (
					conversation_id,
					user_id,
					conversation_last_view,
					conversation_deleted)
				VALUES " . implode(", ", $values);

		$result = $mysqli->query($sql);

	}
## ６−３．新規メッセージ送信をinitファイルで読み込む（編集ファイル：core/init.php）
	<?php
	include("{$core_path}/function/message.php");
## 以上で、メール送信機能が完成しました。

# ７．受信したメッセージをリスト形式で一覧表示する機能を作成する
## ７−１．fetch_ conversation_summeryのファンクションを作成する（編集ファイル：core/function/message.php）
	function fetch_conversation_summery($mysqli) {
		// [メモ] DB選択のイメージ図： https://manablog.org/wp-content/uploads/2017/01/pm_php_db.jpg
		$sql = "SELECT
				conversations.conversation_id,
				conversations.conversation_subject,
				MAX(conversations_messages.message_date) AS conversation_last_reply

				FROM conversations
				-- conversations.conversation_idとconversations_messages.conversation_idを結合
				-- JOIN の左側のテーブル（conversations）のデータは基本的に全て表示します
				LEFT JOIN conversations_messages
				ON
					conversations.conversation_id = conversations_messages.conversation_id

				-- conversations.conversation_id とconversations_members.conversation_idを結合
				-- 左右両方のテーブルで対応するデータが存在するものしか表示しません。
				INNER JOIN conversations_members
				ON
					conversations.conversation_id = conversations_members.conversation_id

				WHERE
					conversations_members.user_id = {$_SESSION['user_id']}
				AND
					conversations_members.conversation_deleted = 0

				-- どのカラムを対象にグループ化するのかを指定
				GROUP BY
					conversations.conversation_id

				ORDER BY
					conversation_last_reply DESC;";

		$result = $mysqli->query($sql);
		$conversations = array();

		while ($row = $result->fetch_assoc()) {
			$conversations[] = $row;
		}
		return $conversations;

	}
## ７−２．fetch_ conversation_summery機能でinboxページにメッセージを表示する（編集ファイル：core/pages/inbox.php）
	<?php
	$conversations = fetch_conversation_summery($mysqli);
	var_dump($conversations);
	?>
	<a href="index.php?page=new_conversation">新規メッセージ</a>
	<a href="index.php?page=logout">ログアウト</a>

	<?php foreach ($conversations as $conversation) { ?>

	<div>
		<p><a href=""><?php echo $conversation['conversation_subject'] ?></a></p>
		<p><small>Last Reply: <?php echo date('y/m/d H:i:s', $conversation['conversation_last_reply']) ?></small></p>
	</div>
	<?php } ?>
## ７−３．未読メッセージを太字にするファンクションを追加する（編集ファイル：core/function/message.php）
	さきほど書いたクエリ文をすこし変更します。
	$sql = "SELECT
				conversations.conversation_id,
				conversations.conversation_subject,
				MAX(conversations_messages.message_date) AS conversation_last_reply,
				MAX(conversations_messages.message_date) > conversations_members.conversation_last_view AS conversation_unread
## ７−４．未読メッセージにはクラスを自動追加する（編集ファイル：core/pages/inbox.php）
	<style type="text/css">
	.unread{font-weight:bold;}
	</style>
	<div class="<?php if ($conversation['conversation_unread']) { echo 'unread'; } ?>">
	<p><a href=""><?php echo $conversation['conversation_subject'] ?></a></p>
	<p><small>Last Reply: <?php echo date('y/m/d H:i:s', $conversation['conversation_last_reply']) ?></small></p>
	</div>
## ７−５．inboxページにメッセージがない場合のエラー表示を作成する（編集ファイル：core/pages/inbox.php）
	$conversations = fetch_conversation_summery($mysqli);
	if (empty($conversations)) {
		$errors[] = "メッセージがありません";
	}
	if (!empty($errors)) {
		foreach ($errors as $error) {
			echo $error;
		}
	}
## 以上で、受信したメッセージをリスト形式で一覧表示する機能が完成しました。

# ８．受信したメッセージの削除機能を作成する
## ８−１．メッセージ一覧表示ページに削除ボタンを追加する（編集ファイル：core/pages/inbox.php）
	<div class="<?php if ($conversation['conversation_unread']) { echo 'unread'; } ?>">
		<p>
			<a href="index.php?page=inbox&amp;delete_conversation=<?php echo $conversation['conversation_id'] ?>">[x]</a>
			<a href=""><?php echo $conversation['conversation_subject'] ?></a>
		</p>
		<p>Last Reply: <?php echo date('y/m/d H:i:s', $conversation['conversation_last_reply']) ?></p>
	</div>
## ８−２．削除ボタンのバリデーションと削除機能を作成する（編集ファイル：core/pages/inbox.php）
	<?php
	$errors = array();

	if (isset($_GET['delete_conversation'])){
		if (validate_conversation_id($_GET['delete_conversation'], $mysqli) === false ) {
			$errors[] = "削除IDエラーが発生しました。";
		}
		if (empty($errors)) {
			delete_conversation($_GET['delete_conversation'], $mysqli);
		}
	}
## ８−３．validate_ conversation_ idを作成する（編集ファイル：core/function/message.php）
	function validate_conversation_id($conversation_id, $mysqli) {
		$conversation_id = (int)$conversation_id;
		$sql = "SELECT COUNT(1)
					FROM conversations_members
					WHERE conversation_id = {$conversation_id}
					AND user_id = {$_SESSION['user_id']}
					AND conversation_deleted = 0";

		$result = $mysqli->query($sql);
		if ($result->num_rows === 1) {
			return true;
		}
	}
## ８−４．delete_ conversationを作成する（編集ファイル：core/function/message.php）
	function delete_conversation($conversation_id, $mysqli) {
		$conversation_id = (int)$conversation_id;

		// conversation_deletedを選択する（DISTINCTはグループメッセージに対応する為に挿入）
		$sql = "SELECT DISTINCT conversation_deleted
					FROM conversations_members
					WHERE conversation_id = {$conversation_id}
					AND user_id != {$_SESSION['user_id']}";
		$result = $mysqli->query($sql);

		// conversation_deletedのフラグ（1 or 0）を取得する
		while ($row = mysqli_fetch_assoc($result)) {
			$conversation_deleted = $row['conversation_deleted'];
		}

		if ($result->num_rows === 1 && $conversation_deleted == 1) {
			// 全てのメッセージを完全消去
			$sql01 = "DELETE FROM conversations WHERE conversation_id = {$conversation_id}";
			$sql02 = "DELETE FROM conversations_members WHERE conversation_id = {$conversation_id}";
			$sql03 = "DELETE FROM conversations_messages WHERE conversation_id = {$conversation_id}";

			$mysqli->query($sql01);
			$mysqli->query($sql02);
			$mysqli->query($sql03);
		} else {
			// conversation_deletedのフラグだけアップデート（データは残る）
			$sql = "UPDATE conversations_members
						SET conversation_deleted = 1
						WHERE conversation_id = {$conversation_id}
						AND user_id = {$_SESSION['user_id']}";
			$mysqli->query($sql);
		}

	}
## 以上で、受信したメール削除機能が完成しました。

# ９．受信したメッセージ詳細を表示する機能を作成する
## ９−１．メール一覧ページからメール詳細ページへのリンクを動かす（編集ファイル：core/pages/inbox.php）
	<p>
		<a href="index.php?page=inbox&amp;delete_conversation=<?php echo $conversation['conversation_id'] ?>">[x]</a>
		<!-- 下記の部分を追加 -->
		<a href="index.php?page=view_conversation&amp;conversation_id=<?php echo $conversation['conversation_id'] ?>"><?php echo $conversation['conversation_subject'] ?></a>
	</p>
## ９−２．メール詳細ページのフロントエンドを作成する（編集ファイル：core/pages/view_conversation.php）
	<?php
	$errors = array();

	// エラーがないか、true or falseで返す
	$valid_conversation = (isset($_GET['conversation_id']) && validate_conversation_id($_GET['conversation_id'], $mysqli));

	if ($valid_conversation === false) {
		$errors[] = "IDエラーが発生しました";
	}
	if (!empty($errors)) {
		foreach ($errors as $error) {
			echo $error;
		}
	}

	if ($valid_conversation) {
	$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
	?>

	<a href="index.php?page=inbox">受信ボックス</a>
	<a href="index.php?page=logout">ログアウト</a>
	<!-- メッセージを下記に表示（あとから作成する） -->

	<?php } ?>
## ９−３．fetch_ conversation_messages機能を作成する（編集ファイル：core/function/message.php）
	function fetch_conversation_messages($conversation_id, $mysqli) {
		$conversation_id = (int)$conversation_id;

		$sql = "SELECT
					conversations_messages.message_date,
					conversations_messages.message_text,
					users.user_name
					FROM conversations_messages
					INNER JOIN users ON conversations_messages.user_id = users.user_id

					WHERE conversations_messages.conversation_id = {$conversation_id}
					ORDER BY conversations_messages.message_date DESC";

		$result = $mysqli->query($sql);
		$messages = array();

		while ($row = $result->fetch_assoc()) {
			$messages[] = $row;
		}
		return $messages;
	}
## ９−４．メール詳細ページにメール本文を表示する（編集ファイル：core/pages/view_conversation.php）
	if ($valid_conversation) {
		$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
		?>

		<a href="index.php?page=inbox">受信ボックス</a>
		<a href="index.php?page=logout">ログアウト</a>

		<?php foreach ($messages as $message) { ?>
			<p><?php echo $message['user_name'] ?>（<?php echo date('y/m/d H:i:s', $message['message_date']) ?>）</p>
			<p><?php echo $message['message_text'] ?></p>
		<?php } // End of foreach

	} // End of if ?>
## ９−５．未読メッセージを判別する機能を作成する（編集ファイル：core/function/message.php）
	<!-- クエリ文を変更する -->
	$sql = "SELECT
					conversations_messages.message_date,
					conversations_messages.message_date > conversations_members.conversation_last_view AS message_unread,
					conversations_messages.message_text,
					users.user_name
				FROM conversations_messages
				INNER JOIN users ON conversations_messages.user_id = users.user_id
				INNER JOIN conversations_members ON conversations_messages.conversation_id = conversations_members.conversation_id

				WHERE conversations_messages.conversation_id = {$conversation_id}
				-- 下記を削るとデータが重複する（実際にクエリ結果を見比べると分かりやすい）
				AND conversations_members.user_id = {$_SESSION['user_id']}
				ORDER BY conversations_messages.message_date DESC";
## ９−６．未読メッセージの場合はクラスを自動追加する（編集ファイル：core/pages/view_conversation.php）
	<?php foreach ($messages as $message) { ?>
		<style>
			.unread { font-weight: bold; }
		</style>
		<div class="<?php if ($message['message_unread']) { echo 'unread'; } ?>">
			<p><?php echo $message['user_name'] ?>（<?php echo date('y/m/d H:i:s', $message['message_date']) ?>）</p>
			<p><?php echo $message['message_text'] ?></p>
		</div>
	<?php } // End of foreach
## ９−７．表示したメッセージの開封時間をアップデートする機能を作成する（編集ファイル：private _message.inc.php）
	function update_conversation_last_view($conversation_id, $mysqli) {
		$conversation_id = (int)$conversation_id;
		$sql = "UPDATE conversations_members
					SET conversation_last_view = UNIX_TIMESTAMP()
					WHERE conversation_id = {$conversation_id}
					AND user_id = {$_SESSION['user_id']}";
		$mysqli->query($sql);
	}
## ９−８．update_ conversation_ last_ viewを読み込む（編集ファイル：core/pages/view_conversation.php）
	if ($valid_conversation) {
		$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
		update_conversation_last_view($_GET['conversation_id'], $mysqli);
	?>
## 以上で、受信したメッセージ詳細を表示する機能が完成しました。

# １０．メッセージの返信機能を作成する
## １０−１．メッセージ返信用のフォームを作成する（編集ファイル：core/pages/view_conversation.php）
	<form action="" method="post">
		<textarea name="message"></textarea>
		<input type="submit" value="返信する">
	</form>
## １０−２．メッセージ返信用のフォームバリデーションを作成する（編集ファイル：core/pages/view_conversation.php）
	if (isset($_POST['message'])) {
		if (empty($_POST['message'])) {
			$errors[] = "メッセージを入力してください。";
		}
		if (empty($errors)) {
			add_conversation_message($_GET['conversation_id'], $_POST['message'], $mysqli);
		}
	}
## １０−３．add_ conversation_message機能を作成する（編集ファイル：core/function/message.php）
	function add_conversation_message($conversation_id, $text, $mysqli) {
		$conversation_id = (int)$conversation_id;
		$text = $mysqli->real_escape_string(htmlentities($text));

		$sql = "INSERT INTO conversations_messages (
						conversation_id,
						user_id,
						message_date,
						message_text
					)
					VALUES (
						{$conversation_id},
						{$_SESSION['user_id']},
						UNIX_TIMESTAMP(),
						'{$text}'
					)";

		$result = $mysqli->query($sql);

	}
## １０−４．自分の返信が未読になってしまう問題を解決する（編集ファイル：private _message.inc.php）
	if ($valid_conversation) {

		if (isset($_POST['message'])) {
			update_conversation_last_view($_GET['conversation_id'], $mysqli);
			$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
		} else {
			$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
			update_conversation_last_view($_GET['conversation_id'], $mysqli);
		}
## 以上で、メッセージの返信機能が完成しました。














