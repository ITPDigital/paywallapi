<?php

declare(strict_types=1);

namespace App\Application\Actions\Products;

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
use App\Application\Helpers\CommonHelper;

class ViewProductAction implements RequestHandlerInterface
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
        $this->logger->info('ViewProductAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $this->logger->info('ViewProductAction: product id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from products where id=:id and comp_id=:comp_id');
            $sql->execute(array(':id' => $id,':comp_id' => $comp_id));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                //$sql = $db->prepare('SELECT pp.id,pp.plan_name,pp.plan_display_name,pp.plan_desc,pp.frequency,pp.final_price from map_plan_product mp LEFT JOIN product_plan pp ON mp.plan_id=pp.id  where mp.product_id=:product_id and mp.comp_id=:comp_id and pp.is_active=:status;');
                $sql = $db->prepare('SELECT pp.id,pp.plan_name,pp.plan_display_name,pp.plan_desc,pp.frequency,pp.final_price,pp.currency,cc.symbol,mpd.discount_id,cp.display_name as frequency_text from map_plan_product mp LEFT JOIN product_plan pp ON mp.plan_id=pp.id LEFT JOIN const_currencies cc ON pp.currency=cc.disp_id LEFT JOIN map_plan_discount mpd on pp.id=mpd.plan_id LEFT JOIN const_periods cp on cp.disp_id=pp.frequency where mp.product_id=:product_id and mp.is_active=:status and mp.comp_id=:comp_id and pp.is_active=:status and mpd.type=:type and mpd.is_active=:status;');
                $sql->execute(array(':product_id' => $id,':comp_id' => $comp_id,':status' => 1, ':type' => 1));
                $planDatas = $sql->fetchAll(PDO::FETCH_ASSOC);
                $trialPrice;
                foreach ($planDatas as $key => $planData) {
                    $discSql = $db->prepare('SELECT * from product_plan_discount where id=:discount_id and is_active=:status');
                    $discSql->execute(array(':discount_id' => (int)$planData['discount_id'],':status' => 1));
                    $discData = $discSql->fetch(PDO::FETCH_ASSOC);

                    $finalPrice = (float)$planData['final_price'];
                    if($discData['discount_type'] == 'PERCENTAGE') {
                        $trialPrice = $finalPrice - (($finalPrice*(float)$discData['discount_value'])/100);
                    } else if($discData['discount_type'] == 'AMOUNT') {
                        $trialPrice = (float)$planData['final_price'] - (float)$discData['discount_value'];
                    }
                    $planDatas[$key]['trial_price'] = $planData['symbol'].''.number_format(floor($trialPrice*100)/100, 2);
                }
                $data['plans'] = $planDatas;
                $this->logger->info('ViewProductAction: Product found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Product found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewProductAction: Product not found"'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Product not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewProductAction: Error in getting product detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewProductAction: Error in getting product detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting product detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}