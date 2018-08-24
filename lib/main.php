<?php

require(dirname(__FILE__).'/method.php');
require(dirname(__FILE__).'/grabber.php');

class main extends method{

    public function reuploadImg($name,$url){
        
    }

    public function executeManga($uri){
        $grabber = new grabber();
        $data    = $grabber->executeManga($uri);

        try{
            $this->connection->beginTransaction();

            $manga_check = $this->manga_check($data['name']);

            if(count($manga_check) > 0){
                // $mangaId = $manga_check[0]['id'];
                return false;
                exit;
            }
            else{

                //insert manga when manga_check 0
                $mangaId = $this->insert_manga([
                    str_replace(' ','-',$data['name']),
                    $data['name'],
                    $data['otherName'],
                    $data['release'],
                    $data['summary'],
                    0,
                    $data['status'] == 'Ongoing' ? 1 : 2,
                    1
                ]);
            }

            //insert authors_manga
            $this->insert_authors_manga($mangaId,$data['authors'],1);

            //insert artists_manga
            $this->insert_authors_manga($mangaId,[$data['artist']],2);

            //insert categories
            $this->insert_categories_manga($mangaId, $data['categories']);

            $this->connection->commit();
            return true;
        }

        catch(PDOExecption $e){
            //rollback if error
            $this->connection->rollBack();
            return false;
        } 

    }
}