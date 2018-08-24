<?php

require('db.php');

class method extends db{

    public function manga_check($name){
        $query = "SELECT * FROM manga where name = ? LIMIT 1";
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$name]);

        return $stmt->fetchAll();
    }

    public function insert_manga($data){
        $query = "INSERT INTO manga 
                    (slug,name,otherNames,releaseDate,summary,cover,status_id,user_id,created_at,updated_at)
                    values (?,?,?,?,?,?,?,?,now(),now())";
        $stmt = $this->connection->prepare($query);

        try{
            $stmt->execute($data);
            $lastId = $this->connection->lastInsertId();
        }
        catch(PDOExecption $e){
            return false;
        }

        return $lastId;
    }

    public function author($name){
        //check author
        $query = "SELECT * FROM author where name = ? LIMIT 1";
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$name]);

        $author_check = $stmt->fetchAll();

        if(count($author_check ) > 0){
            $id = $author_check[0]['id'];
        }
        else{

            $query = "INSERT into author(name, created_at, updated_at) values (?,now(),now())";
            $stmt = $this->connection->prepare($query);

            try{
                $stmt->execute([$name]);
                $id = $this->connection->lastInsertId();
            }
            catch(PDOExecption $e){
                return false;
            }
        }

        return $id;
        
    }

    public function insert_authors_manga($mangaId, $authors, $type){
        $query = "INSERT INTO author_manga VALUES(?,?,?)";
        $stmt = $this->connection->prepare($query);

        try{
            // $this->connection->beginTransaction();

            for ($i=0; $i < count($authors) ; $i++) { 
                $stmt->execute([$mangaId, $this->author($authors[$i]),$type]);
            }

            // $this->connection->commit();
        }
        catch(PDOExecption $e){
            return false;
        }
    }

    public function category($name){
        //check category
        $query = "SELECT * FROM category where name = ? LIMIT 1";
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$name]);

        $category_check = $stmt->fetchAll();

        if(count($category_check ) > 0){
            $id = $category_check[0]['id'];
        }
        else{

            $query = "INSERT into category (slug,name, created_at, updated_at) values ('".str_replace(' ','-',$name)."',?,now(),NULL)";
            $stmt = $this->connection->prepare($query);

            try{
                $stmt->execute([$name]);
                $id = $this->connection->lastInsertId();
            }
            catch(PDOExecption $e){
                return false;
            }
        }

        return $id;
        
    }

    public function insert_categories_manga($mangaId, $categories){
        $query = "INSERT INTO category_manga VALUES(?,?)";
        $stmt = $this->connection->prepare($query);

        try{
            // $this->connection->beginTransaction();

            for ($i=0; $i < count($categories) ; $i++) { 
                $stmt->execute([$mangaId, $this->category($categories[$i])]);
            }

            // $this->connection->commit();
        }
        catch(PDOExecption $e){
            return false;
        }
    }

    public function execute($data){
        //manga_check

        try{
            $this->connection->beginTransaction();

            $manga_check = $this->manga_check($data['name']);

            if(count($manga_check) > 0){
                $mangaId = $manga_check[0]['id'];
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
            return false;
        } 

    }

}