<?php

// require(dirname(__FILE__).'/method.php');
require(dirname(__FILE__).'/grabber.php');

class main extends grabber{

    protected $data;
    protected $mangaId;

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
        echo "Grab Manga $uri \n";
        $this->data = $this->grabManga($uri);

        return $this;
    }

    public function chapters(){

        echo "Start insert chapters \n";        

        for ($i=0; $i < count($this->data['chapters']) ; $i++) {
            echo " Insert chapter".$this->data['chapters'][$i]['title']."\n";  

            $this->speChapter([
                'manganame' => str_replace(' ','-',strtolower($this->data['name'])),
                'slug'      => $this->data['chapters'][$i]['number'],
                'name'      => $this->data['chapters'][$i]['title'],
                'number'    => $this->data['chapters'][$i]['number'],
                'volume'    => null,
                'manga_id'  => $this->mangaId,
                'user_id'   => 1,
                'link'      => $this->data['chapters'][$i]['link']
            ]);

            // var_dump($this->data['chapters'][$i]);
        }
    }

    public function speChapter($data){
        $this->chapter([
            'manganame' => $data['manganame'],
            'slug'      => $data['slug'],
            'name'      => $data['name'],
            'number'    => $data['number'],
            'volume'    => $data['volume'],
            'manga_id'  => $data['manga_id'],
            'user_id'   => $data['user_id'],
            'link'      => $data['link']
        ]);
    }

    public function chapter($data){
        echo "Insert chapter into DB\n";

        $chapterId = $this->insert_chapter([
            $data['slug'],
            $data['name'],
            $data['number'],
            $data['volume'],
            $data['manga_id'],
            $data['user_id']
        ]);

        echo "Grab pages \n";
        $pages = $this->grabPages($data['link']);

        for ($i=0; $i < count($pages) ;  $i++) { 

            echo  "Insert page ".$pages[$i]['page_slug']."\n";
            if($pages[$i]['external'] == 0){

                $link = explode('/',$data['link']);

                $name = $link[4];
                $slug_url = $link[5];

                $url = "https://www.komikgue.com/uploads/manga/$name/chapters/".$slug_url."/".$pages[$i]['page_image'];
                $this->pageUpload($data['manganame'],$url,$slug_url,$pages[$i]['page_image']);
            }

            echo  "Insert page ".$pages[$i]['page_slug']." into DB\n";
            $this->insert_chapter_page([
                    $pages[$i]['page_slug'],
                    $pages[$i]['page_image'],  
                    $pages[$i]['external'],
                    $chapterId
                ]);
        }

        // return 
    }

    public function manga(){
        echo "Insert manga into DB \n";

        try{
            $this->connection->beginTransaction();

            $manga_check = $this->manga_check($this->data['name']);

            if(count($manga_check) > 0){
                // $this->mangaId = $manga_check[0]['id'];
                return false;
                exit;
            }
            else{

                //insert manga when manga_check 0
                $this->mangaId = $this->insert_manga([
                    str_replace(' ','-',strtolower($this->data['name'])),
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
            $this->insert_authors_manga($this->mangaId,$this->data['authors'],1);

            //insert artists_manga
            $this->insert_authors_manga($this->mangaId,[$this->data['artist']],2);

            //insert categories
            $this->insert_categories_manga($this->mangaId, $this->data['categories']);

            $this->connection->commit();

            //upload img
            $this->coverUpload(strtolower($this->data['name']),$this->data['img']);

            echo "Insert Success\n";
            return $this;
        }

        catch(PDOExecption $e){
            //rollback if error
            $this->connection->rollBack();
            return false;
        } 

    }
}