<?php

require(dirname(__FILE__).'/db.php');

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
        $stmt->execute($data);
        $lastId = $this->connection->lastInsertId();

        return $lastId;
    }

    public function author($name){
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

        for ($i=0; $i < count($authors) ; $i++) { 
            $stmt->execute([$mangaId, $this->author($authors[$i]),$type]);
        }

    }

    public function category($name){
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

            $stmt->execute([$name]);
            $id = $this->connection->lastInsertId();

        }

        return $id;
        
    }

    public function insert_categories_manga($mangaId, $categories){
        $query = "INSERT INTO category_manga VALUES(?,?)";
        $stmt = $this->connection->prepare($query);

        for ($i=0; $i < count($categories) ; $i++) { 
            $stmt->execute([$mangaId, $this->category($categories[$i])]);
        }
    }

    public function insert_chapter($data){
        $query = "INSERT INTO chapter (slug,name,number,volume,manga_id,user_id,created_at,updated_at) values(?,?,?,?,?,?,now(),now())";
        // echo $query;
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);

        $lastId = $this->connection->lastInsertId();

        return $lastId;
    }

    public function insert_chapter_page($data){
        $query = "INSERT INTO page (slug,image,external,chapter_id,created_at,updated_at) VALUES (?,?,?,?,now(),now())";
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);

        $lastId = $this->connection->lastInsertId();

        return $lastId;
    }

}