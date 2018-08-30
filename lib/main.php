<?php

// require(dirname(__FILE__).'/method.php');
require(dirname(__FILE__).'/grabber.php');

class main extends grabber{

    protected $data;
    protected $mangaId;

    public function coverUpload($name,$url){
        
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

    public function execute($url){
        echo "Grab Manga $url \n";

        var_dump($this->grabManga($url));

        try{
            $grabManga = $this->grabManga($url);

            if($grabManga == false){
                throw new Exception('Failed load manga url');
            }

            $this->data = $grabManga;
        }
        catch(Exception $e){
            echo $e->getMessage();

            $this->insert_error_manga($url);
            die;
        }

        return $this;

    }

    public function chapters(){

        echo "Start insert chapters \n";        

        for ($i=0; $i < count($this->data['chapters']) ; $i++) {
            echo " Insert chapter".$this->data['chapters'][$i]['title']."\n";  

            $this->speChapter([
                'manganame' => $this->data['slug'],
                'slug'      => $this->data['chapters'][$i]['number'],
                'name'      => $this->data['chapters'][$i]['title'],
                'number'    => $this->data['chapters'][$i]['number'],
                'volume'    => null,
                'manga_id'  => $this->mangaId,
                'user_id'   => 1,
                'link'      => $this->data['chapters'][$i]['link']
            ]);

            // echo $this->data['chapters'][$i]['number'];
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

        try{

            $this->connection->beginTransaction();

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

            if($pages == false){
                throw new Exception('Failed load page url');
            }
    
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

            $this->connection->commit();

        }

        catch(Exception $e){
            $this->connection->rollBack();

            $this->insert_error_chapter([
                $data['manganame'],
                $data['slug'],
                $data['name'],
                $data['number'],
                $data['volume'],
                $data['manga_id'],
                $data['user_id'],
                $data['link']
            ]);

            echo 'Error man '.$e->getMessage();
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
                    $this->data['slug'],
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

            //upload img
            $this->coverUpload($this->data['slug'],$this->data['img']);

            $this->connection->commit();

            echo "Insert Success\n";
            return $this;
        }

        catch(Exception $e){
            //rollback if error
            $this->connection->rollBack();

            echo 'Error man '.$e->getMessage;
            die;
        } 

    }

    public function fixChapter(){
        echo "get error chapter \n";
        $chapters = $this->get_error_chapter();

        foreach($chapters as $value){
            $this->speChapter([
                'manganame' => $value['manganame'],
                'slug'      => $value['slug'],
                'name'      => $value['name'],
                'number'    => $value['number'],
                'volume'    => $value['volume'],
                'manga_id'  => $value['manga_id'],
                'user_id'   => $value['user_id'],
                'link'      => $value['link']
            ]);
            
            echo "delete error chapter \n";
            $this->delete_error_chapter($value['id']);

        }
    }

    public function updateChapter(){
        echo "get last chapter \n";
        $last_chapters = $this->get_last_chapter();

        foreach ($last_chapters as $last_chapter) {
            echo "grab ".$last_chapter['slug']."\n";
            $grabManga = $this->grabManga('https://www.komikgue.com/manga/'.$last_chapter['slug']);
            $chapters = $grabManga['chapters'];

            echo "update ".$last_chapter['slug']."\n";
            foreach ($chapters as $chapter) {

                if(str_replace(',','.',$chapter['number']) > str_replace(',','.',$last_chapter['last_chapter'])){

                    echo $last_chapter['slug']." chapter ".$chapter['number']."\n";
                    $this->speChapter([
                        'manganame' => $last_chapter['slug'],
                        'slug'      => $chapter['number'],
                        'name'      => $chapter['title'],
                        'number'    => $chapter['number'],
                        'volume'    => null,
                        'manga_id'  => $last_chapter['id'],
                        'user_id'   => 1,
                        'link'      => $chapter['link']
                    ]);
                }
                
            }

            echo $last_chapter['slug']." updated. \n";

        }
        
    }
}