<?php

// require(dirname(__FILE__).'/method.php');
require(dirname(__FILE__).'/grabber.php');

class main extends grabber{

    protected $data;

    public function coverUpload($name,$url){
        
        $name = str_replace(' ','-',$name);
        
        //create directory
        if (!file_exists("../uploads/manga/$name") && !is_dir("../uploads/manga/$name")) {
            mkdir("../uploads/manga/$name");         
        }

        if (!file_exists("../uploads/manga/$name/cover") && !is_dir("../uploads/manga/$name/cover")) {
            mkdir("../uploads/manga/$name/cover");         
        }

        //copy img
        copy($url,"../uploads/manga/$name/cover/cover_250x350.jpg");
        copy(str_replace('/cover_250x350.jpg','/cover_thumb.jpg',$url),"../uploads/manga/$name/cover/cover_thumb.jpg");
    }

    public function pageUpload($name,$url,$slug,$file){
        //create directory
        if (!file_exists("../uploads/manga/$name") && !is_dir("../uploads/manga/$name")) {
            mkdir("../uploads/manga/$name");         
        }

        if (!file_exists("../uploads/manga/$name/chapters") && !is_dir("../uploads/manga/$name/chapters")) {
            mkdir("../uploads/manga/$name/chapters");         
        }

        if (!file_exists("../uploads/manga/$name/chapters/$slug") && !is_dir("../uploads/manga/$name/chapters/$slug")) {
            mkdir("../uploads/manga/$name/chapters/$slug");         
        }

        copy($url,"../uploads/manga/$name/chapters/$slug/$file");

    }   

    public function execute($uri){
        // $this->data = $this->grabManga($uri);

        // var_dump($this->grabPages('https://www.komikgue.com/manga/horimiya/90'));
        return $this;
    }

    public function speChapter(){
        $this->chapter([
            'manganame' => 'nanatsu-no-taizai',
            'slug'  => 95,
            'name'  => "Testing chapterid",
            'number' => 95,
            'volume' => null,
            'manga_id' => 2,
            'user_id' => 1,
            'link' => 'https://www.komikgue.com/manga/one-piece/915/1'
        ]);
    }

    public function chapter($data){

        $chapterId = $this->insert_chapter([
            $data['slug'],
            $data['name'],
            $data['number'],
            $data['volume'],
            $data['manga_id'],
            $data['user_id']
        ]);

        $pages = $this->grabPages($data['link']);

        for ($i=0; $i < count($pages) ;  $i++) { 

            if($pages[$i]['external'] == 0){

                $link = explode('/',$data['link']);

                $name = $link[4];
                $slug_url = $link[5];

                $url = "https://www.komikgue.com/uploads/manga/$name/chapters/".$slug_url."/".$pages[$i]['page_image'];
                $this->pageUpload($data['manganame'],$url,$data['slug'],$pages[$i]['page_image']);
            }

            $this->insert_chapter_page([
                    $pages[$i]['page_slug'],
                    $pages[$i]['page_image'],  
                    $pages[$i]['external'],
                    $chapterId
                ]);
        }

        // return 
    }

    public function manga($uri){

        try{
            $this->connection->beginTransaction();

            $manga_check = $this->manga_check($this->data['name']);

            if(count($manga_check) > 0){
                // $mangaId = $manga_check[0]['id'];
                return false;
                exit;
            }
            else{

                //insert manga when manga_check 0
                $mangaId = $this->insert_manga([
                    str_replace(' ','-',$this->data['name']),
                    $this->data['name'],
                    $this->data['otherName'],
                    $this->data['release'],
                    $this->data['summary'],
                    1,
                    $this->data['status'] == 'Ongoing' ? 1 : 2,
                    1
                ]);
            }

            //insert authors_manga
            $this->insert_authors_manga($mangaId,$this->data['authors'],1);

            //insert artists_manga
            $this->insert_authors_manga($mangaId,[$this->data['artist']],2);

            //insert categories
            $this->insert_categories_manga($mangaId, $this->data['categories']);

            $this->connection->commit();

            //upload img
            $this->coverUpload($this->data['name'],$this->data['img']);
            return true;
        }

        catch(PDOExecption $e){
            //rollback if error
            $this->connection->rollBack();
            return false;
        } 

    }
}