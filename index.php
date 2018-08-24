<?php

require('lib/method.php');

// $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
// $context = stream_context_create($opts);
// $data = file_get_contents('https://www.komikgue.com/manga/tales-of-demons-and-gods/187.5/1',false,$context);

// $data = explode('var pages =',$data);
// $data = explode('var next_chapter =',$data[1]);
    
// $data = json_decode(str_replace('];',']',$data[0]), TRUE);

// var_dump($data);

// die;

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

$opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
$context = stream_context_create($opts);
$html = file_get_contents('https://www.komikgue.com/manga/one-piece',false,$context);

$dom = new Dom;
$dom->load($html);

$getInfo = $dom->find('.dl-horizontal',0);

//name
$name = $dom->find('.widget-title',0)->text;

//status
$status = $dom->find('.label-success',0)->text;

//otherName
if(strpos($getInfo, 'Nama lain') !== false){
    $otherName = explode('<dt>Nama lain</dt> <dd>',$getInfo);
    $otherName = explode('</dd>',$otherName[1]);
    $otherName = $otherName[0];
}
else{
    $otherName = null;
}

//release
if(strpos($getInfo, 'Waktu rilis') !== false){
    $release = explode('<dt>Waktu rilis</dt> <dd>',$getInfo);
    $release = explode('</dd>',$release[1]);
    $release = $release[0];
}
else{
    $release = null;
}

//artist
if(strpos($getInfo, 'Artist(s)') !== false){
    $artist = explode('<dt>Artist(s)</dt> <dd>',$getInfo);
    $artist = explode('</dd>',$artist[1]);
    $artist = $artist[0];
}
else{
    $artist = null;
}

//category
if(strpos($getInfo, 'Kategori') !== false){
    $categori = explode('<dt>Kategori</dt> <dd>',$getInfo);
    $categori = explode('</dd>',$categori[1]);
    $categori = $categori[0];

    $categoriDom = new Dom;
    $categoriDom->load($categori);

    $categories = [];
    foreach ($categoriDom->find('a') as $value) {
        $categories[] = $value->text;
    }

}
else{
    $categories = [];    
}

//author
if(strpos($getInfo, 'Author(s)') !== false){
    $author = explode('<dt>Author(s)</dt> <dd>',$getInfo);
    $author = explode('</dd>',$author[1]);
    $author = $author[0];

    $authorDom = new Dom;
    $authorDom->load($author);

    $authors = [];
    foreach ($authorDom->find('a') as $value) {
        $authors[] = $value->text;
    }

}
else{
    $author = [];    
}

//synopsis
$synopsis = $dom->find('p[style=margin-bottom:0;]',0)->text;

//img
$img = $dom->find('.img-responsive')->src;


//MAIN
//chapters
$chapters = [];
foreach ($dom->find('.chapters li[style=padding: 3px 0;]') as $value) {
    $chapters[] = [
        'link' => $value->find('a')->href,
        'number' => explode(' ',$value->find('a')->text)[1],
        'title' => $value->find('em')->text
    ];
}

$data = [
    'name'      => $name,
    'status'    => $status,
    'otherName' => $otherName,
    'release'   => $release,
    'artist'    => $artist,
    'categories'  => $categories,
    'authors'    => $authors,
    'summary'   => $synopsis
];


// echo $categories;
// var_dump($data);

$method = new method();
$method->execute($data);