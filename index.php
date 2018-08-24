<?php

require('lib/main.php');
$main = new main();

if($main->executeManga('https://www.komikgue.com/manga/20th-century-boys')){
    echo 'Success';
}
else{
    echo "Something went wrong man or duplicate manga name..";
}
