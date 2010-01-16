
<?php foreach($results as $result): ?>

	id: <?php echo $result['id']; ?><br>
	title: <?php echo $result['title']; ?><br>
	body: <?php echo $result['body']; ?><br>
	hoge: <?php echo $result['hoge']; ?><br>

<hr>
<?php endforeach; ?>

<?php echo $html->link('add'); ?>
