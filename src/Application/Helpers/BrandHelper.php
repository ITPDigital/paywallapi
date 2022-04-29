<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class BrandHelper {
   
   public function isBrandExists($db, $brand_name, $comp_id,$id){
       if($id==0) {
         $sql = $db->prepare('SELECT id from brands where brand_name=:brand_name and comp_id=:comp_id');
         $sql->execute(array(':brand_name' => $brand_name,':comp_id' => $comp_id));
       } else {
        $sql = $db->prepare('SELECT id from brands where brand_name=:brand_name and comp_id=:comp_id and id!=:id');
        $sql->execute(array(':brand_name' => $brand_name,':comp_id' => $comp_id,':id' => $id));
       }
        $count = $sql->rowCount();
	    return $count;
   } 

   public function isDomainExists($db, $domain_name, $comp_id,$id){
        if($id==0) {
            $sql = $db->prepare('SELECT id from brands where domain_name=:domain_name and comp_id=:comp_id');
            $sql->execute(array(':domain_name' => $domain_name,':comp_id' => $comp_id));
        } else {
            $sql = $db->prepare('SELECT id from brands where domain_name=:domain_name and comp_id=:comp_id and id!=:id');
            $sql->execute(array(':domain_name' => $domain_name,':comp_id' => $comp_id,':id' => $id));
        }
        $count = $sql->rowCount();
        return $count;
    }
   
}
