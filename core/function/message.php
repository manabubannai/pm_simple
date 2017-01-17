<?php

function fetch_conversation_summery($mysqli) {
	// [メモ] DB選択のイメージ図： https://manablog.org/wp-content/uploads/2017/01/pm_php_db.jpg
	$sql = "SELECT
				conversations.conversation_id,
				conversations.conversation_subject,
				MAX(conversations_messages.message_date) AS conversation_last_reply,
				MAX(conversations_messages.message_date) > conversations_members.conversation_last_view AS conversation_unread

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
// var_dump($sql);
	$result = $mysqli->query($sql);
	$conversations = array();

	while ($row = $result->fetch_assoc()) {
		$conversations[] = $row;
	}
	return $conversations;

}

function fetch_conversation_messages($conversation_id, $mysqli) {
	$conversation_id = (int)$conversation_id;

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

	$result = $mysqli->query($sql);
	$messages = array();

	while ($row = $result->fetch_assoc()) {
		$messages[] = $row;
	}
	return $messages;
}

function update_conversation_last_view($conversation_id, $mysqli) {
	$conversation_id = (int)$conversation_id;
	$sql = "UPDATE conversations_members
				SET conversation_last_view = UNIX_TIMESTAMP()
				WHERE conversation_id = {$conversation_id}
				AND user_id = {$_SESSION['user_id']}";
	$mysqli->query($sql);
}


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
