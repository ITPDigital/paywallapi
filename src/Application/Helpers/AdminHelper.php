<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class AdminHelper {
   
   public function isAdminUserExists($db, $email, $comp_id){
        $sql = $db->prepare('SELECT id from company_admin where email=:email and comp_id=:comp_id');
        $sql->execute(array(':email' => $email,':comp_id' => $comp_id));
        $count = $sql->rowCount();
	    return $count;
   } 
   
}
