<?php

declare(strict_types=1);

namespace App\Application\Helpers;



class UserHelper {
   
   public function createUser($db, $data){	
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
        $password = isset($data['password']) ? $data["password"] :  '';
        $confirm_password = isset($data['confirm_password']) ? $data["confirm_password"] :  '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
		$password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
		$date = date('Y-m-d h:i:s');
		$marketing_optin = isset($data['marketing_optin']) ? $data['marketing_optin'] : '';
		$third_party_optin = isset($data['third_party_optin']) ? $data['third_party_optin'] : '';
		$access_role = isset($data['access_role']) ? $data['access_role'] : '';
		$count = $this->isUserNameExists($db, $email, $brand_id);
		if($count==0) {
			$sql = $db->prepare("INSERT INTO users (brand_id, first_name, last_name, email, password, industry, job_ttl, comp_size, registered_on, status, access_role) 
			values (:brand_id,:first_name,:last_name,:email,:password,:industry,:job_title,:company_size,:registered_on,:status,:access_role)");
			$sql->execute(array(':brand_id' => $brand_id, ':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':password' => $password, ':industry' => $industry, ':job_title' => $job_title, ':company_size' => $company_size, ':registered_on' => date('Y-m-d h:i:s'), ':status' => 1, ':access_role' => $access_role));
			//$sql->debugDumpParams();
			$count = $sql->rowCount();
			$lastinserid = $db->lastInsertId();
			if ($count && $lastinserid) {
				$user_dt_sql = $db->prepare("INSERT INTO user_details (user_id, brand_id, marketing_optin, third_party_optin) VALUES (:user_id, :brand_id, :marketing_optin, :third_party_optin)");
				$user_dt_sql->execute(array(':user_id' => $lastinserid, ':brand_id' => $brand_id, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin));
				$count = $sql->rowCount();
			}
		}
		return $count;
   }

   public function isUserNameExists($db, $email, $brand_id){
		$sql = $db->prepare('SELECT id from users where email=:email and brand_id=:brand_id');
		$sql->execute(array(':email' => $email,':brand_id' => $brand_id));
		$count = $sql->rowCount();
		return $count;
	} 
   
   public function updateAccount($db, $data, $id, $brandid, $updatedby) {
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$comp_name = isset($data['comp_name']) ? $data["comp_name"] :  '';
		$brand_id = $brandid;//isset($data['brand_id']) ? $data["brand_id"] :  '';
		$gender = isset($data['gender']) ? $data["gender"] :  '';
		$dob = isset($data['dob']) ? $data["dob"] :  '';
		$phone = isset($data['phone']) ? $data["phone"] :  '';
		$tax_reg_no = isset($data['tax_reg_no']) ? $data["tax_reg_no"] :  '';
		$user_id = $id;
		$date = date('Y-m-d h:i:s');
		$marketing_optin = isset($data['marketing_optin']) ? $data['marketing_optin'] : '';
		$third_party_optin = isset($data['third_party_optin']) ? $data['third_party_optin'] : '';
		$sql = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, industry = :industry, job_ttl = :job_ttl, comp_size = :comp_size, comp = :comp, update_on = :date, updated_by = :updatedby	 
		WHERE id = :user_id AND brand_id = :brand_id");
		$sql->execute(array(':first_name' => $first_name, ':last_name' => $last_name, ':industry' => $industry, ':job_ttl' => $job_title, ':comp_size' => $company_size, ':comp' => $comp_name, ':date' => $date, ':updatedby' => $updatedby, ':user_id' => $user_id, ':brand_id' => $brand_id));
		$count = $sql->rowCount();
		$user_dt_sql = $db->prepare("UPDATE user_details SET country = :country, dob = :dob, gender = :gender, phone = :phone, marketing_optin = :marketing_optin, third_party_optin = :third_party_optin, tax_reg_no = :tax_reg_no WHERE user_id = :user_id AND brand_id = :brand_id");
		$user_dt_sql->execute(array(':country' => $country, ':dob' => $dob, ':gender' => $gender, ':phone' => $phone, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin, ':tax_reg_no' => $tax_reg_no, ':user_id' => $user_id, ':brand_id' => $brand_id));
		$count += $user_dt_sql->rowCount();
	   return $count;    
   }

  public function sendMail($db, $mail, $data) {
	$reset_key = bin2hex(random_bytes(20));
	$service_name = 'resetpassword_' . $data->brand_id;
	$domian = $_SERVER['HTTP_HOST'];
	$expire = time() + (60 * 60 * 24);
	$cquery = $db->prepare('SELECT count(*) from user_reset_password WHERE email= :email');
	$cquery->execute(array(':email' => $data->email));
	$ncount =  $cquery->fetchColumn();
	if ($ncount) {
	  $sql = $db->prepare('UPDATE user_reset_password SET reset_key= :reset_key, expire = :expire WHERE email= :email');
	  $sql->execute(array(':email' => $data->email,':reset_key' => $reset_key,':expire' => $expire));					
	}
	else {
	  $sql = $db->prepare('INSERT INTO user_reset_password (email, reset_key, expire) VALUES (:email, :reset_key, :expire)');
	  $sql->execute(array(':email' => $data->email,':reset_key' => $reset_key,':expire' => $expire));					
	}
	$link = $domian . '/subscriptions/reset-password?resetAttributeKey= '  . $reset_key . ' &serviceName=' . $service_name;
	$mail_body = '<h2>Please click the link below to reset your password.</h2>';
	$mail_body .= '<a href="' . $link . '">' . $link . '</a>';

	$mail->SMTPDebug=3;
	$mail->isSMTP();

	$mail->Host="email-smtp.us-west-2.amazonaws.com";
	$mail->Port=587;
	$mail->SMTPSecure="tls";
	$mail->SMTPAuth=true;
	$mail->Username="AKIA4UTH2DF5H6RI6QRE";
	$mail->Password="BMSN5Rsy8JMhDtV2W/xt4qJMjxMQAlX/04XLkXxiliM1";
	$mail->addAddress($data->email);
	$mail->Subject="Reset Your Password";
	$mail->isHTML(true);
	$mail->Body= $mail_body;
	$mail->From="webmaster@itp.com";
	$mail->FromName="Webmaster";
	$output = false;
	if($mail->send()) {
	  $output = true;					
	}
	return  $output;
  }   
}
