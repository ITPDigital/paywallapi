<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class WidgetHelper {
   
   public function isRuleExists($db, $brandId, $widgetType, $actionType, $contentType, $customCount, $contentCategory, $isLoggedIn, $comp_id, $id) {
        if($id==0) {
            $sql = $db->prepare('SELECT id from map_metering_type where brand_id=:brand_id and comp_id=:comp_id and type=:type and metering_action_id=:metering_action_id and custom_count=:custom_count and content_type_id=:content_type_id and content_category_id=:content_category_id and is_logged_in=:is_logged_in');
            $sql->execute(array(':brand_id' => $brandId,':comp_id' => $comp_id,':type' => $widgetType,':metering_action_id' => $actionType,':custom_count' => $customCount,':content_type_id' => $contentType,':content_category_id' => $contentCategory,':is_logged_in' => $isLoggedIn));
        } else {
            $sql = $db->prepare('SELECT id from map_metering_type where brand_id=:brand_id and comp_id=:comp_id and type=:type and metering_action_id=:metering_action_id and custom_count=:custom_count and content_type_id=:content_type_id and content_category_id=:content_category_id and is_logged_in=:is_logged_in and id!=:id');
            $sql->execute(array(':brand_id' => $brandId,':comp_id' => $comp_id,':type' => $widgetType,':metering_action_id' => $actionType,':custom_count' => $customCount,':content_type_id' => $contentType,':content_category_id' => $contentCategory,':is_logged_in' => $isLoggedIn,':id' => $id));
        }
            $count = $sql->rowCount();
       //echo $count;exit;
	    return $count;
   } 
   
}
