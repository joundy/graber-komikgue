<?php

require('lib/main.php');
$main = new main();


echo "Please input Manga link'\ninput: ";
$url = trim(fgets(STDIN));

// var_dump($main->getData('https://www.komikgue.com/manga/amalgam-of-distortion'));
// 
$main->execute($url)->manga()->chapters();

