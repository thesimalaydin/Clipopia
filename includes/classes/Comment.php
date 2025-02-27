<?php
require_once("ButtonProvider.php");
require_once("CommentControls.php");

class Comment{

    private $con, $sqlData, $userLoggedInObj, $videoId;

    public function __construct($con, $input, $userLoggedInObj, $videoId){

        if(!is_array($input)){
            $query = $con->prepare("SELECT * FROM comments WHERE id = :id");
            $query->bindParam(":id", $input);
            $query->execute();

            $input = $query->fetch(PDO::FETCH_ASSOC);
        }

        $this->sqlData = $input;
        $this->con = $con;
        $this->userLoggedInObj = $userLoggedInObj;
        $this->videoId = $videoId;

    }

    public function create(){
        $id = $this->sqlData["id"];
        $videoId = $this->getVideoId();
        $body = $this->sqlData["body"];
        $timeSpan = $this->time_elapsed_string($postedBy = $this->sqlData["datePosted"]);
        $postedBy = $this->sqlData["postedBy"];
        $profileButton = ButtonProvider::createUserProfileButton($this->con, $postedBy);

        $commentControlsObj = new CommentControls($this->con, $this, $this->userLoggedInObj);
        $commentControls = $commentControlsObj->create();

        $numResponses = $this->getNumberOfReplies();

        if($numResponses > 0){
            $viewRepliesText = "<span class='repliesSection viewReplies' onclick='getReplies($id, this, $videoId)'>
                                View all $numResponses replies</span>";
        }
        else{
            $viewRepliesText = "<div class='repliesSection'></div>";
        }

        return "<div class='itemContainer'>
                    <div class='comment'>
                        $profileButton
                        <div class='mainContainer'>

                            <div class='commentHeader'>
                                <a href='profile.php?username=$postedBy'>
                                    <span class='username'>$postedBy</span>
                                </a>
                                <span class='timestamp'>$timeSpan</span>
                            </div>

                            <div class='body'>
                                $body
                            </div>
                        </div>
                    </div>

                    $commentControls
                    $viewRepliesText
                </div>";
    }

    public function getNumberOfReplies() {
        $query = $this->con->prepare("SELECT count(*) FROM comments WHERE responseTo = :responseTo");
        $query->bindParam(":responseTo", $id);
        $id = $this->sqlData["id"];
        $query->execute();

        return $query->fetchColumn();
    }

    function time_elapsed_string($time_ago) {
        $time_ago = strtotime($time_ago);
        $cur_time   = time();
        $time_elapsed   = $cur_time - $time_ago;
        $seconds    = $time_elapsed ;
        $minutes    = round($time_elapsed / 60 );
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400 );
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640 );
        $years      = round($time_elapsed / 31207680 );
        // Seconds
        if($seconds <= 60){
            return "just now";
        }
        //Minutes
        else if($minutes <=60){
            if($minutes==1){
                return "one minute ago";
            }
            else{
                return "$minutes minutes ago";
            }
        }
        //Hours
        else if($hours <=24){
            if($hours==1){
                return "an hour ago";
            }else{
                return "$hours hrs ago";
            }
        }
        //Days
        else if($days <= 7){
            if($days==1){
                return "yesterday";
            }else{
                return "$days days ago";
            }
        }
        //Weeks
        else if($weeks <= 4.3){
            if($weeks==1){
                return "a week ago";
            }else{
                return "$weeks weeks ago";
            }
        }
        //Months
        else if($months <=12){
            if($months==1){
                return "a month ago";
            }else{
                return "$months months ago";
            }
        }
        //Years
        else{
            if($years==1){
                return "one year ago";
            }else{
                return "$years years ago";
            }
        }
    }
    
    
    
    

    public function getId(){
        return $this->sqlData["id"];
    }

    public function getVideoId(){
        return $this->videoId;
    }

    public function wasLikedBy(){
        $query = $this->con->prepare("SELECT * FROM likes WHERE username = :username AND commentId = :commentId");
        $query->bindParam(":username", $username);
        $query->bindParam(":commentId", $id);

        $id = $this->getId();
        $username = $this->userLoggedInObj->getUsername();
        $query->execute();

        return $query->rowCount() > 0;
    }

    public function wasDislikedBy(){
        $query = $this->con->prepare("SELECT * FROM dislikes WHERE username = :username AND commentId = :commentId");
        $query->bindParam(":username", $username);
        $query->bindParam(":commentId", $id);
        
        $id = $this->getId();
        $username = $this->userLoggedInObj->getUsername();
        $query->execute();

        return $query->rowCount() > 0;
    }

    public function getLikes(){
        $query = $this->con->prepare("SELECT count(*) as 'count' FROM likes WHERE commentId = :commentId");
        $query->bindParam(":commentId", $commentId);
        $commentId = $this->getId();
        $query->execute();

        $data = $query->fetch(PDO::FETCH_ASSOC);
        $numLikes = $data["count"];

        $query = $this->con->prepare("SELECT count(*) as 'count' FROM dislikes WHERE commentId = :commentId");
        $query->bindParam(":commentId", $commentId);
        $query->execute();

        $data = $query->fetch(PDO::FETCH_ASSOC);
        $numDislikes = $data["count"];

        return $numLikes - $numDislikes;
    }

    public function like(){
        $id = $this->getId();
        $username = $this->userLoggedInObj->getUsername();

        if($this->waslikedBy()) {
            // User has already liked
            $query = $this->con->prepare("DELETE FROM likes WHERE username = :username AND commentId = :commentId");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();

            return -1;
        }
        else{
            $query = $this->con->prepare("DELETE FROM dislikes WHERE username = :username AND commentId = :commentId");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();
            $count = $query->rowCount();

            $query = $this->con->prepare("INSERT INTO likes(username, commentId) VALUES(:username, :commentId)");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();

            return 1 + $count;
        }
    }

    public function dislike(){
        $id = $this->getId();
        $username = $this->userLoggedInObj->getUsername();

        if($this->wasDislikedBy()) {
            // User has already liked
            $query = $this->con->prepare("DELETE FROM dislikes WHERE username = :username AND commentId = :commentId");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();

            return 1;
        }
        else{
            $query = $this->con->prepare("DELETE FROM likes WHERE username = :username AND commentId = :commentId");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();
            $count = $query->rowCount();

            $query = $this->con->prepare("INSERT INTO dislikes(username, commentId) VALUES(:username, :commentId)");
            $query->bindParam(":username", $username);
            $query->bindParam(":commentId", $id);
            $query->execute();

            return -1 - $count;
        }
    }

    public function getReplies() {
        $query = $this->con->prepare("SELECT * FROM comments WHERE responseTo = :commentId ORDER BY datePosted ASC");
        $query->bindParam(":commentId", $id);
        $id = $this->getId();

        $query->execute();

        $comments = "";
        $videoId = $this->getVideoId();

        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $comment = new Comment($this->con, $row, $this->userLoggedInObj, $videoId);
            $comments .= $comment->create();
        }

        return $comments;
    }
}

?>