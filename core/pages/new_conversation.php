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

		// ユーザーが存在するかを確認する。fetch_user_idsのファンクションは後ほど作成します。
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
		// echo "エラーはありません。あとでメッセージ送信機能を作ります。";
		create_conversation(array_unique($user_ids), $_POST['subject'], $_POST['body'], $mysqli);
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
<form action="" method="post">
	<input type="text" name="to" placeholder="To" value="<?php if(isset($_POST['to'])) { echo htmlentities($_POST['to']); } ?>">
	<br>
	<input type="text" name="subject" placeholder="件名" value="<?php if(isset($_POST['subject'])) { echo htmlentities($_POST['subject']); } ?>">
	<br>
	<textarea name="body" value="<?php if(isset($_POST['body'])) { echo htmlentities($_POST['body']); } ?>"></textarea>
	<br>
	<input type="submit" value="送信する">
</form>
