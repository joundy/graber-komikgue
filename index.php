<?php

require('lib/main.php');
$main = new main();

if($main->executeManga('https://www.komikgue.com/manga/ao-no-exorcist')){
    echo 'Success';
}
else{
    echo "Something went wrong man or duplicate manga name..";
}
