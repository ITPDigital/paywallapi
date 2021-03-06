<?php

declare(strict_types=1);

namespace App\Application\Actions\Orders;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Cake\Validation\Validator;
use Selective\Validation\ValidationResult;
use Selective\Validation\Factory\CakeValidationFactory;
use Selective\Validation\Exception\ValidationException;
use PDO;
use \Firebase\JWT\JWT;
use App\Application\Helpers\PaymentHelper;


class AddOrderAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {		
        $this->logger->info('AddOrderAction: handler dispatched');
        $data = $request->getParsedBody();  //print '<pre>'; print_r();exit;
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('product_id', 'Field required')
            ->requirePresence('product_id')
            ->notEmptyString('plan_id', 'Field required')
			->requirePresence('plan_id');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $brand_id = $request->getAttribute('brandid');
           // $comp_id = $request->getAttribute('compid');
			$user_id = $request->getAttribute('userid');
            $product_id = isset($data['product_id']) ? $data["product_id"] : '';
            $plan_id = isset($data['plan_id']) ? $data["plan_id"] :  '';
            $is_promo_applied = $data['is_promo_applied'] ? $data['is_promo_applied'] : 0;
            $promo_code = isset($data['promo_code']) ? $data["promo_code"] :  '';
            $promo_discount_id = isset($data['promo_discount_id']) ? $data["promo_discount_id"] :  0;
            $payment_token = isset($data['payment_token']) ? $data["payment_token"] :  0;
            $payment_type = isset($data['payment_type']) ? $data["payment_type"] :  0;

           // $this->logger->info('AddOrderAction: brand_id-'.$brand_id.'--comp_id--'.$comp_id.'--user-id'.$user_id);
            $this->logger->info('AddOrderAction: promo_code-'.$promo_code.'--promo_discount_id--'.$promo_discount_id);            
            $db =  $this->connection;
            $response = new Response();
			$sql = $db->prepare("SELECT GROUP_CONCAT(cp.number_of_days) AS number_of_days, pp.frequency, pp.contract_length, pp.offset, pp.base_price, pp.discount_id, pp.final_price, pp.trial_price FROM product_plan AS pp INNER JOIN const_periods AS cp ON cp.disp_id = pp.frequency OR cp.disp_id = pp.contract_length WHERE pp.id = :plan_id");
			$sql->execute(array(':plan_id' => $plan_id));
			$data = $sql->fetch(PDO::FETCH_OBJ);
            $frequency = $data->frequency;
            $contract_length = $data->contract_length;
            $offset = $data->offset;
            $number_of_days = explode(',',$data->number_of_days);
            $freq_number_of_days = $number_of_days[0];
            $contrct_number_of_days = $number_of_days[1];
            $start_date = date("Y-m-d 00:00:00");
            $end_date = strtotime("+" . $contrct_number_of_days .  " days 23:59:59");
            $end_date = date("Y-m-d H:i:s", $end_date);
			$last_payment_date = date("Y-m-d 00:00:00");
            $next_payment_date = strtotime("+" . $freq_number_of_days .  " days 23:59:59");
            $next_payment_date = date("Y-m-d H:i:s", $next_payment_date);			
            // Price Calculation
            $base_price = $data->base_price;
            $discount_id = (isset($data->discount_id) && $promo_discount_id == 0) ? $data->discount_id :  0;
           // echo $is_promo_applied;exit;
            if ($is_promo_applied==1 && $promo_discount_id!=0) {
                $discount_value = $data->base_price;
                $sql_disc = $db->prepare("SELECT pd.discount_value, md.promo_id FROM product_plan_discount AS pd INNER JOIN product_plan_promos as pp ON pp.promo_code = :promo_code INNER JOIN map_promo_discount AS md ON pd.id = md.discount_id AND pp.id = md.promo_id AND md.discount_id = :discount_id WHERE pd.id = :id");
                $sql_disc->execute(array(':id' => $promo_discount_id, ':discount_id' => $promo_discount_id, ':promo_code' => $promo_code));
                $data_disc = $sql_disc->fetch(PDO::FETCH_OBJ);
                $this->logger->info('AddOrderAction: discount_value-'.$discount_value.'--promo-id--'.$data_disc->promo_id);
                $promo_discount_value = $data_disc->discount_value;
                $final_price =  $discount_value - ($discount_value * ($promo_discount_value / 100));
            }
            else if ( $discount_id ){
              $final_price = $data->trial_price;
            }
            else {
              $final_price = $data->final_price;
            }
			
			$final_price = 100;
			
			// Payment Process Starts here			
			
			$PaymentHelper = new PaymentHelper();
            switch ($payment_type) {
				case 'CARD':
			      $paymentData = $PaymentHelper->checkoutPayment($brand_id, $payment_token, $final_price);
				break;				
			}
	        //print '<pre>'; print_r($paymentData);exit;
			// Payment Process Ends here		

			if ($paymentData['status']) {
				$external_payment_id = $paymentData['response']['id'];
				$external_trans_id = $paymentData['response']['processing']['acquirer_transaction_id'];
				$external_ref_number = $paymentData['response']['processing']['retrieval_reference_number'];
				$external_trans_status = $paymentData['response']['response_summary'];
				$status = $paymentData['response']['approved'];
				$created = date("Y-m-d H:i:s");
				// Adding to user_license table
				$ul_sql = $db->prepare("INSERT INTO user_license (brand_id, user_id, product_id, product_plan_id, contract_length, frequency, offset, discount_id, promo_discount_id, base_price, final_price, start_date, end_date, last_payment_date, next_payment_date, status) VALUES (:brand_id, :user_id, :product_id, :product_plan_id, :contract_length, :offset, :discount_id, :promo_discount_id, :base_price, :final_price, :start_date, :end_date, :last_payment_date, :next_payment_date, :status)");
				$ul_sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':product_id' => $product_id, ':product_plan_id' => $plan_id, ':contract_length' => $contract_length, ':offset' => $offset, ':discount_id' => $discount_id, 'promo_discount_id' => $promo_discount_id, ':base_price' => $base_price, ':final_price' => $final_price, ':start_date' => $start_date, ':end_date' => $end_date, ':last_payment_date' => $last_payment_date, ':next_payment_date' => $next_payment_date, ':status' => 1));

  
				$count = $ul_sql->rowCount();
				$user_license_id = $db->lastInsertId();
			
				if($count && $user_license_id) {
					$this->logger->info('AddOrderAction: New Order has been created successfully for user_id-'.$user_id . 'and plan_id-'.$plan_id);
					//$user_sql = $db->prepare("UPDATE users set is_subscribed_user=:is_subscribed where id=:user_id and brand_id=:brand_id");
					//$user_sql->execute(array(':is_subscribed' => 1, ':user_id' => $user_id, ':brand_id' => $brand_id));
				
				// Adding to user_purchase_history table
				/*$uph_sql = $db->prepare("INSERT INTO user_purchase_history (brand_id, user_id, user_license_id, start_date, end_date, status) VALUES (:brand_id, :user_id, :user_license_id, :start_date, :end_date, :status)");
				$uph_sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':user_license_id' => $user_license_id, ':start_date' => $start_date, ':end_date' => $end_date, ':status' => 1));  

				$purchase_id = $db->lastInsertId();*/
				
				// Adding to user_transaction_history table
				$uth_sql = $db->prepare("INSERT INTO user_transaction_history (brand_id, user_id, user_license_id, external_payment_id, product_plan_id, payment_type, external_trans_id, external_ref_number, external_trans_status, transaction_type, status, amount, last_payment_date, next_payment_date, created, trial_active) VALUES (:brand_id, :user_id, :user_license_id, :external_payment_id, :product_plan_id, :payment_type, :external_trans_id, :external_ref_number, :external_trans_status, :transaction_type, :status, :amount, :last_payment_date, :next_payment_date, :created, :trial_active)");
				$uth_sql->execute(array(':brand_id' => $brand_id, ':user_id' => $user_id, ':user_license_id' => $user_license_id, ':external_payment_id' => $external_payment_id, ':product_plan_id' => $plan_id, ':payment_type' => $payment_type, ':external_trans_id' => $external_trans_id, ':external_ref_number' => $external_ref_number, ':external_trans_status' => $external_trans_status, ':transaction_type' => 'Charge', 'status' => $status, ':amount' => $final_price, ':last_payment_date' => $last_payment_date, ':next_payment_date' => $next_payment_date, ':created' => $created, ':trial_active' => 1));
				
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 1,
						"message" => "New Order has been created successfully",
						"id" => $user_license_id
					))
				);				
				
            } else {
                $this->logger->info('AddOrderAction: Failed to create new product for user_id-'.$user_id . 'and plan_id-'.$plan_id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to create new order"
                    ))
                );
            }
		  }
		  else {
			$this->logger->info('AddOrderAction: Payment failed for user_id-'.$user_id . 'and plan_id-'.$plan_id);
			$response->getBody()->write(
				json_encode(array(
					"code" => 0,
					"status" => 0,
					"message" => 'Payment is failed. Please try again'
				))
			);	 			  
		  }		  
        }
        catch(MySQLException $e) {
            $this->logger->info('AddOrderAction: SQL error in creating new order for user_id-'.$user_id . 'and plan_id-'.$plan_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in creating new order'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddOrderAction: Error in creating new order for user_id-'.$user_id . 'and plan_id-'.$plan_id.'--'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new order'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}