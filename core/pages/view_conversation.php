<?php
$errors = array();

// エラーがないか、true or falseで返す
$valid_conversation = (isset($_GET['conversation_id']) && validate_conversation_id($_GET['conversation_id'], $mysqli));

if ($valid_conversation === false) {
	$errors[] = "IDエラーが発生しました";
}

if (isset($_POST['message'])) {
	if (empty($_POST['message'])) {
		$errors[] = "メッセージを入力してください。";
	}
	if (empty($errors)) {
		add_conversation_message($_GET['conversation_id'], $_POST['message'], $mysqli);
	}
}

if (!empty($errors)) {
	foreach ($errors as $error) {
		echo $error;
	}
}

if ($valid_conversation) {

	if (isset($_POST['message'])) {
		update_conversation_last_view($_GET['conversation_id'], $mysqli);
		$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
	} else {
		$messages = fetch_conversation_messages($_GET['conversation_id'], $mysqli);
		update_conversation_last_view($_GET['conversation_id'], $mysqli);
	}

	?>

	<a href="index.php?page=inbox">受信ボックス</a>
	<a href="index.php?page=logout">ログアウト</a>

	<form action="" method="post">
		<textarea name="message"></textarea>
		<input type="submit" value="返信する">
	</form>

	<?php foreach ($messages as $message) { ?>
		<style>
			.unread { font-weight: bold; }
		</style>
		<div class="<?php if ($message['message_unread']) { echo 'unread'; } ?>">
			<p><?php echo $message['user_name'] ?>（<?php echo date('y/m/d H:i:s', $message['message_date']) ?>）</p>
			<p><?php echo $message['message_text'] ?></p>
		</div>
	<?php } // End of foreach

} // End of if ?>
