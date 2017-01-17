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

$conversations = fetch_conversation_summery($mysqli);
if (empty($conversations)) {
	$errors[] = "メッセージがありません";
}
if (!empty($errors)) {
	foreach ($errors as $error) {
		echo $error;
	}
}
?>
<a href="index.php?page=new_conversation">新規メッセージ</a>
<a href="index.php?page=logout">ログアウト</a>

<?php foreach ($conversations as $conversation) { ?>

<style type="text/css">
	.unread{font-weight:bold;}
</style>
	<div class="<?php if ($conversation['conversation_unread']) { echo 'unread'; } ?>">
		<p>
			<a href="index.php?page=inbox&amp;delete_conversation=<?php echo $conversation['conversation_id'] ?>">[x]</a>
			<!-- 下記の部分を追加 -->
			<a href="index.php?page=view_conversation&amp;conversation_id=<?php echo $conversation['conversation_id'] ?>"><?php echo $conversation['conversation_subject'] ?></a>
		</p>
		<p>Last Reply: <?php echo date('y/m/d H:i:s', $conversation['conversation_last_reply']) ?></p>
	</div>

<?php } ?>
