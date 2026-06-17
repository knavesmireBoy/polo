<nav id="main_nav">
	<label class="menu" for="menu-toggle"></label>
	<input id="menu-toggle" type="checkbox">
	<ul id="nav">
		<?php
		foreach ($nav as $k => $v) { ?>
			<li><a href="<?= $k ?>"><?= $v ?></a></li>
		<?php	} ?>
	</ul>
</nav>