<?php

require (dirname(__FILE__)."/../vendor/autoload.php");

use PHPHtmlParser\Dom;

class grabber{

    public $uri;

    private $dom;
    private $getData;
    private $getInfo;

    public function getData(){
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        $html = file_get_contents($this->uri,false,$context);

        $this->getData = $html;
    }

    public function getInfo(){
        $this->dom->load($this->getData);

        $getInfo = $this->dom->find('.dl-horizontal',0);

        $this->getInfo = $getInfo;
    }

    public function grapUnSpecific($word){
        if(strpos($this->getInfo, $word) !== false){
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
        if(strpos($this->getInfo, $word) !== false){
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

    public function executeManga($uri){
        $this->dom = new Dom();

        $this->uri = $uri;

        $this->getData();
        $this->getInfo();

        $name       = $this->dom->find('.widget-title',0)->text;
        $status     = $this->dom->find('.label',0)->text;

        if(strpos($this->getInfo, 'margin-bottom:0;') !== false){
            $summary = $this->dom->find('p[style=margin-bottom:0;]',0)->text;
        }
        else{
            $summary = null;
        }

        $img        = $this->dom->find('.img-responsive')->src;
        $otherName  = $this->grapUnSpecific('Nama lain');
        $release    = $this->grapUnSpecific('Waktu rilis');
        $artist     = $this->grapUnSpecific('Artist(s)');
        $categories = $this->grabArraysData('Kategori');
        $authors    = $this->grabArraysData('Author(s)');

        $data = [
            'name'       => $name,
            'status'     => $status,
            'otherName'  => $otherName,
            'release'    => $release,
            'artist'     => $artist,
            'categories' => $categories,
            'authors'    => $authors,
            'summary'    => $summary,
            'img'        => $img
        ];

        return $data;
    }
}