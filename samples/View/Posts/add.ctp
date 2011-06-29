<div class="posts form">
<?php echo $this->Form->create('Post' , array( 'type' => 'post' ));?>
	<fieldset>
 		<legend><?php __('Add Post');?></legend>
	<?php
		echo $this->Form->input('title');
		echo $this->Form->input('body');
		echo $this->Form->input('hoge');
	?>
	</fieldset>
<?php echo $this->Form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('List Posts', true), array('action'=>'index'));?></li>
	</ul>
</div>
