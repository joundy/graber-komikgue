<?php

require (dirname(__FILE__)."/../vendor/autoload.php");
require (dirname(__FILE__)."/method.php");

use PHPHtmlParser\Dom;

class grabber extends method{

    public $uri;

    private $dom;
    private $getData;
    private $getInfo;

    public function getData(){
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);

        $html = @file_get_contents($this->uri,false,$context);

        if(!$html){
            $this->getData = false;
            exit;
        }
        else{
            $this->getData = $html;
        }

    }

    public function getInfo(){
        $this->dom->load($this->getData);

        $getInfo = $this->dom->find('.dl-horizontal',0);

        $this->getInfo = $getInfo;
    }

    public function grabUnSpecific($word){
        if(strpos($this->getInfo, $word)){
            $grab = explode("<dt>$word</dt> <dd>",$this->getInfo);
            $grab = explode('</dd>',$grab[1]);
            $grab = $grab[0];
        }
        else{
            $grab = null;
        }

        return $grab;
    }

    public function grabArraysData($word){
        if(strpos($this->getInfo, $word)){
            $grab = explode("<dt>$word</dt> <dd>",$this->getInfo);
            $grab = explode('</dd>',$grab[1]);
            $grab = $grab[0];
        
            $grabDom = new Dom;
            $grabDom->load($grab);
        
            $grabs = [];
            foreach ($grabDom->find('a') as $value) {
                $grabs[] = $value->text;
            }
        
        }
        else{
            $grabs = [];    
        }

        return $grabs;
    }

    public function grabPages($uri){
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);

        if(!($data = file_get_contents($uri,false,$context))){
            return false;
            exit;
        }

        $data = explode('var pages =',$data);
        $data = explode('var next_chapter =',$data[1]);

        if(strpos($data[0],'}];')){
            $data = str_replace('];',']',$data[0]);
        }
        else{
            $data = str_replace('};','}',$data[0]);
        }

        return json_decode($data, TRUE);
    }

    public function grabManga($uri){
        $this->dom = new Dom();

        $this->uri = $uri;
        $this->getData();

        $slug = explode('/',$uri);
        $slug = end($slug);


        if($this->getData == false){
            return false;
            exit;
        }

        $this->getInfo();

        $name   = $this->dom->find('.widget-title',0)->text;
        $status = $this->dom->find('.label',0)->text;

        if(strpos($this->getData, '<strong>Sipnosis</strong>')){
            $summary = $this->dom->find('p[style=margin-bottom:0;]',0)->text;
        }
        else{
            $summary = null;
        }

        $chapters = [];
        foreach ($this->dom->find('.chapters li[style=padding: 3px 0;]') as $value) {
            $number = explode(' ',$value->find('a')->text);
            $chapters[] = [
                'link' => $value->find('a')->href,
                'number' => end($number),
                'title' => $value->find('em')->text
            ];
        }

        $img        = $this->dom->find('.img-responsive')->src;
        $otherName  = $this->grabUnSpecific('Nama lain');
        $release    = $this->grabUnSpecific('Waktu rilis');
        $artist     = $this->grabUnSpecific('Artist(s)');
        $categories = $this->grabArraysData('Kategori');
        $authors    = $this->grabArraysData('Author(s)');

        $data = [
            'name'       => $name,
            'slug'       => $slug,
            'status'     => $status,
            'otherName'  => $otherName,
            'release'    => $release,
            'artist'     => $artist,
            'categories' => $categories,
            'authors'    => $authors,
            'summary'    => $summary,
            'img'        => $img,
            'chapters'   => $chapters
        ];

        return $data;
    }
}