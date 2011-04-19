<?php echo $html->link('Add data', 'add'); ?>
<br>
<br>
<?php foreach($results as $result): ?>

	id: <?php echo $result['Post']['_id']; ?> [<?php echo $html->link('edit','edit/'.$result['Post']['_id']); ?>] [<?php echo $html->link('delete','delete/'.$result['Post']['_id']); ?>]<br>
	title: <?php echo $result['Post']['title']; ?><br>
	body: <?php echo $result['Post']['body']; ?><br>
	hoge: <?php echo $result['Post']['hoge']; ?><br>

<hr>
<?php endforeach; ?>