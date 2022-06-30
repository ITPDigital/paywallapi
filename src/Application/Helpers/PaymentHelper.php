<?php

declare(strict_types=1);

namespace App\Application\Helpers;


class PaymentHelper {
    public function checkoutPayment($brand_id, $payment_token, $final_price){ //return $brand_id .'--'. $payment_token . '--' . $final_price;exit;
		$response = [];
		$t=time();
		$url = "https://api.sandbox.checkout.com/payments";
		$ch = curl_init($url);
		$header = array(
		'Content-Type: application/json;charset=UTF-8',
		'Authorization: sk_test_50f8954f-35d7-4209-8f3d-042a67136ef8');

		$data_string = '{
				"source": {
					"type": "token",
					"token": "'. $payment_token .'",
					"name": "Muhammad TVK" 
				  },
				"amount": '. $final_price .',
				"currency": "USD",
				"reference": "' . $brand_id . ' ' . time() .'",
				"customer": {
					"email": "muhammad.tvk@itp.com",
					"name": "Muhammad TVK"
				}  
			}';


		////////////////////
		// HANDLE MADA CARDS 
		////////////////////

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$output = curl_exec($ch);		
		curl_close($ch);

		$decoded = json_decode($output, true); //print '<pre>'; print_r($decoded);exit;
		//handle 2d Charge - check if the charge was successful or not
		if (isset($decoded['response_code']) && $decoded['response_code'] == "10000") {
			$response =  ['status' => 1, 'response' => $decoded];
		}
		else {
			$response =  ['status' => 0, 'response' => $decoded];			
		}
		return $response;
    }

}