<div class="Geos form">
<?php echo $form->create('Geo' , array( 'type' => 'post' ));?>
	<fieldset>
 		<legend><?php __('Add Geo');?></legend>
	<?php
		echo $form->input('title');
		echo $form->input('body');
		echo $form->input('Geo.loc.lat', array('label' => 'latitude'));
		echo $form->input('Geo.loc.long', array('label' => 'longitude'));
	?>
	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Geos', true), array('action'=>'index'));?></li>
	</ul>
</div>

	</fieldset>
<?php echo $form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $html->link(__('List Geos', true), array('action'=>'index'));?></li>
	</ul>
</div>
