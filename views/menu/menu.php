<?php

if(!empty($params['menu']))
{
	switch ($params['menu'])
	{
		case 'main':
			$arMenu = require(__DIR__ . '/../../config/menu/sidebar_menu.php');
			break;
		case 'fast':
			$arMenu = require(__DIR__ . '/../../config/menu/sidebar_menu_fast.php');
			break;
		
		default:
			$arMenu = require(__DIR__ . '/../../config/menu/sidebar_menu.php');
			break;
	}
}
else
	$arMenu = require(__DIR__ . '/../../config/menu/sidebar_menu.php');

$firtsHr = true;
if(!empty($arMenu))
{
	?>

	<!--nav nav-sidebar-->
    <ul class="nav nav-sidebar">
	
	<?php

	// активную пунтк должен быть только один
	$wasActive = false;
	
	?>
	<?php foreach ($arMenu as $link => $title) { ?>
		<?php if($link == 'separator' && $title == 'hr') { ?>
			</ul>
			<!--/nav nav-sidebar-->
			<hr>
			<!--nav nav-sidebar-->
	    	<ul class="nav nav-sidebar">
		<?php } else { ?>

		<?php 

		// активный пункт должен быть один
		if(strpos($_SERVER['REQUEST_URI'], '/' . $link . '/') !== false
			&& !$wasActive
		)
		{
			$wasActive = true;
			$active = 'active';
		}
		else
			$active = '';

		?>
		<li class="<?= $active; ?>">
			<a title="<?php echo $title ?>" href="/<?php echo $link ?>/"><?php echo $title ?></a>
        </li>
        <?php } ?>
	<?php } ?>
	</ul>
	<!--/nav nav-sidebar-->
<?php } ?>