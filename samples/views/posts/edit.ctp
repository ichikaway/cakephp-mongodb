<div class="posts form">
<?php echo $form->create('Post' , array( 'type' => 'post' ));?>
	<fieldset>
 		<legend><?php __('Edit Post');?></legend>
	<?php
		echo $form->hidden('_id');
		echo $form->input('title');
		echo $form->input('body');
		echo $form->input('hoge');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Posts', true), array('action'=>'index'));?></li>
	</ul>
</div>