<?php

// require(dirname(__FILE__).'/method.php');
require(dirname(__FILE__).'/grabber.php');

class main extends grabber{

    protected $data;

    public function cover($name,$url){
        
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

    public function execute($uri){
        $this->data = $this->grabManga($uri);

        return $this;
    }

    public function chapters(){
        // return $this->grabChapter('https://www.komikgue.com/manga/horimiya/90/1');

        // return $this->insert_chapter([
        //     'slug',
        //     'testing name',
        //     'number',
        //     null,
        //     2,
        //     1
        // ]);

        return $this->insert_chapter_page([
            99,             //slug
            'testing.jpg',  
            0, //external
            3 //chapter_id
        ]);
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
            $this->cover($this->data['name'],$this->data['img']);
            return true;
        }

        catch(PDOExecption $e){
            //rollback if error
            $this->connection->rollBack();
            return false;
        } 

    }
}