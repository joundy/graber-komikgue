<?php

require('lib/main.php');
$main = new main();

$main->execute('https://www.komikgue.com/manga/mujang')->manga()->chapters();
