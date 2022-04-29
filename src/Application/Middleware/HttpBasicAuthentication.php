<?php

$app = new \Slim\App;

class RandomAuthenticator implements AuthenticatorInterface {
   public function __invoke(array $arguments) {

    //validation for user and password 
     $Password=$arguments['password'];
      $user=$arguments['user'];
if(($Password=="admin") &&($user=="admin") ){
return true;
}  
else{

    return false ;

    }

}}