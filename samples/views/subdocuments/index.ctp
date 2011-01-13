<?php echo $html->link('Add data', 'add'); ?>
<br>
<br>
<?php foreach($results as $result): ?>

	id: <?php echo $result['Subdocument']['_id']; ?> [<?php echo $html->link('edit','edit/'.$result['Subdocument']['_id']); ?>] [<?php echo $html->link('delete','delete/'.$result['Subdocument']['_id']); ?>]<br>
	title: <?php echo $result['Subdocument']['title']; ?><br>
	body: <?php echo $result['Subdocument']['body']; ?><br>
	<?php foreach($result['Subdocument']['subdoc'] as $num => $val): ?>
		subdoc_name:<?php echo h($val['name']) ?><br>
		subdoc_age:<?php echo h($val['age']) ?><br>
	<?php endforeach; ?>

<hr>
<?php endforeach; ?>