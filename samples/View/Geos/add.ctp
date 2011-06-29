<div class="Geos form">
<?php echo $this->Form->create('Geo' , array( 'type' => 'post' ));?>
	<fieldset>
 		<legend><?php __('Add Geo');?></legend>
	<?php
		echo $this->Form->input('title');
		echo $this->Form->input('body');
		echo $this->Form->input('Geo.loc.lat', array('label' => 'latitude'));
		echo $this->Form->input('Geo.loc.long', array('label' => 'longitude'));
	?>
	</fieldset>
<?php echo $this->Form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('List Geos', true), array('action'=>'index'));?></li>
	</ul>
</div>

	</fieldset>
<?php echo $this->Form->end('Submit');?>
</div>
<div class="actions">
	<ul>
		<li><?php echo $this->Html->link(__('List Geos', true), array('action'=>'index'));?></li>
	</ul>
</div>
