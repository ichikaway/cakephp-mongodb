<div class="Subdocuments form">
<?php echo $form->create('Subdocument' , array( 'type' => 'post' ));?>
	<fieldset>
 		<legend><?php __('Add Subdocument');?></legend>
	<?php
		echo $form->input('title');
		echo $form->input('body');
		//echo $form->input('subdoc.name');
		//echo $form->input('subdoc.age');
		echo $form->input('Subdocument.subdoc.0.name');
		echo $form->input('Subdocument.subdoc.0.age');
		echo $form->input('Subdocument.subdoc.1.name');
		echo $form->input('Subdocument.subdoc.1.age');
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Subdocuments', true), array('action'=>'index'));?></li>
	</ul>
</div>

	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Subdocuments', true), array('action'=>'index'));?></li>
	</ul>
</div>