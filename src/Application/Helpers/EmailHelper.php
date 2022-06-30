<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class EmailHelper {
   
   public function isRuleExists($db, $brandId, $emailType, $comp_id, $id) {
        if($id==0) {
            $sql = $db->prepare('SELECT id from email_templates where brand_id=:brand_id and comp_id=:comp_id and email_type_id=:emailType');
            $sql->execute(array(':brand_id' => $brandId,':comp_id' => $comp_id,':emailType' => $emailType));
        } else {
            $sql = $db->prepare('SELECT id from email_templates where brand_id=:brand_id and comp_id=:comp_id and email_type_id=:emailType and id!=:id');
            $sql->execute(array(':brand_id' => $brandId,':comp_id' => $comp_id,':emailType' => $emailType,':id' => $id));
        }
            $count = $sql->rowCount();
       //echo $count;exit;
	    return $count;
   } 
   
}
