<?php

declare(strict_types=1);

namespace App\Application\Actions\Tracking;

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

class SiteAuthorizeAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;
    private $NOT_METERED = 0;
    private $REQUIRE_LOGIN = 1;
    private $REQUIRE_ENTITLEMENT = 2;
    private $REQUIRE_LOGIN_WITH_ENTITLEMENT = 3;

    private $TYPE_PAYWALL = 1;
    private $TYPE_NOTIFIER = 2;

    private $METER_EXCEED_MAX_LIMIT = 1;
    private $METER_EXCEED_OFFERED_LIMIT = 2;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function createNewSession($brandId,$st_ss,$numOfDays) {
        $maxSSIdSql = $db->prepare("SELECT MAX(Id) FROM brand_access_sessions");
        $maxSSIdSql->execute();
        $maxSSId = $maxSSIdSql->fetch(PDO::FETCH_ASSOC); 
        $bdAccSsSql = $db->prepare("INSERT INTO brand_access_sessions (brand_id,st_session, created_on, expires_on, is_active) VALUES (:brandId, :st_session, :created_on, :expires_on, :status)");
        $bdAccSsSql->execute(array(':brandId' => $brandId,':st_session' => $st_ss, ':created_on' => date('Y-m-d h:i:s'), ':expires_on' => strtotime('+'.$numOfDays.' days'), ':status' => 1));
        return $bdAccSsSql->rowCount();
    }

    public function getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,$action_id) {
        $metTypeIdSql = $db->prepare("SELECT id,metering_action_id,name FROM map_metering_type where content_type_id=:content_type_id and content_category_id=:content_category_id and metering_action_id=:metering_action_id and is_active=:status and type=:type");
        $metTypeIdSql->execute(array(':content_type_id' => $content_type_id,':content_category_id' => $content_category_id,':metering_action_id' => $action_id,':status' => 1,':type' => $TYPE_PAYWALL));
        return $metTypeIdSql->fetch(PDO::FETCH_ASSOC); 
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('SiteAuthorizeAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        
        try {
            $brandId = $commonHelper->resolveArg($request,'brandId');
            $post_id = isset($data['articleId']) ? $data["articleId"] : '';
            $post_url = isset($data['article_url']) ? $data["article_url"] : '';
            $content_type = isset($data['contentType']) ? $data["contentType"] :  '';
            $st_ss = isset($data['st_ss']) ? $data["st_ss"] :  '';
            $premium = isset($data['premium']) ? $data["premium"] :  '';
            $register = isset($data['register']) ? $data["register"] :  '';
            $content_type_id = isset($data['content_type_id']) ? $data["content_type_id"] :  '';
            $content_category_id = isset($data['content_category_id']) ? $data["content_category_id"] :  '';
            $st_notmetered = isset($data['st_notmetered']) ? $data["st_notmetered"] :  '';
            $viewfree = isset($data['viewfree']) ? $data["viewfree"] :  '';
            $this->logger->info('SiteAuthorizeAction: brandId'.$brandId);
            $date = date('Y-m-d h:i:s');
            $isLoggedIn = true;
            $db =  $this->connection;
            $response = new Response();
            $isValidSs = 0;
            $brandSql = $db->prepare('SELECT b.*,cp.number_of_days from brands b LEFT JOIN const_periods cp on cp.disp_id=b.metering_period WHERE b.id=:brandId and b.is_active=:status and cp.is_active=:status;');
            $brandSql->execute(array(':brandId' => $brandId,':status' => 1));
            $brandDatas = $brandSql->fetch(PDO::FETCH_ASSOC); 
        
            if(count($brandDatas)>0){
                if($st_ss) {//session exists
                    $bdAccSsSql = $db->prepare('SELECT * from brand_access_sessions WHERE brand_id=:brandId and st_session=:st_ss and DATE(expires_on) < CURDATE() and is_active=:status;');
                    $bdAccSsSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss,':status' => 1));
                    $bdAccSsCount = $bdAccSsSql->rowCount();
                    if($bdAccSsCount>0) {//valid session
                        $isValidSs =  $bdAccSsCount;
                    } else {//create new session
                        $updBdAccSs = $db->prepare("UPDATE brand_access_sessions set is_active=:status where st_session = :st_ss");	
                        $updBdAccSs->execute(array(':status' => 0,':st_ss' =>$st_ss));
                        $st_ss = date('Y-m-d h:i:s') . "|" .  $maxSSId+1;
                        $st_ss = hash('sha256', $st_ss);
                        $isValidSs = $this->createNewSession($brandId,$st_ss,(int)$brandDatas['number_of_days']);
                    }
                  
                } else {//new access
                    $st_ss = date('Y-m-d h:i:s') . "|" .  $maxSSId+1;
                    $st_ss = hash('sha256', $st_ss);
                    $isValidSs = $this->createNewSession($brandId,$st_ss,(int)$brandDatas['number_of_days']);
                }
                if($isValidSs>0) {//valid session
                    if(!$st_notmetered && !$viewfree) {
                       /* $bdAccHistPostSql = $db->prepare('SELECT * from brand_access_history WHERE brand_id=:brandId and post_id=:post_id and post_url=:post_url and st_session=:st_ss');
                        $bdAccHistPostSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss));
                        $bdAccHistPostCout = $bdAccHistPostSql->fetch(PDO::FETCH_ASSOC); 
                        if(count($bdAccHistPostCout)<=0) {//check if the post is already accessed*/
                            $bdAccHistSql = $db->prepare('SELECT * from brand_access_history WHERE brand_id=:brandId and st_session=:st_ss');
                            $bdAccHistSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss));
                            $bdAccHistCout = $bdAccHistSql->rowCount();
                            $selMetId = 0;
                            $exceed_meter_type = 0;
                            if($isLoggedIn) {
                                if($bdAccHistCout > (int)$brandDatas['offered_limit']) {
                                    $selMetId = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,$REQUIRE_ENTITLEMENT);
                                    $exceed_meter_type = $METER_EXCEED_MAX_LIMIT;
                                }
                            } else {
                                if($bdAccHistCout > (int)$brandDatas['max_limit']) {
                                    $selMetId = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,$REQUIRE_LOGIN);
                                    $exceed_meter_type = $METER_EXCEED_OFFERED_LIMIT;
                                }
                            }
                            $insBdAccHist = $db->prepare("INSERT INTO brand_access_history (brand_id, content_type, st_session, post_id, post_url, content_type_id, content_category_id, exceed_meter_type, viewed_on, is_active) VALUES (:brand_id, :content_type, :st_session, :post_id, :post_url, :content_type_id, :content_category_id, :exceed_meter_type, :viewed_on, :is_active)");
                            $insBdAccHist->execute(array(':brand_id' => $brandId, ':content_type' => $content_type, ':st_session' => $st_ss, ':post_id' => $post_id, ':post_url' => $post_url, ':content_type_id' => $content_type_id, ':content_category_id' => $content_category_id,':exceed_meter_type' => $exceed_meter_type,':viewed_on' => $date, ':is_active' => 1));
                            $insBdAccHist = $insBdAccHist->rowCount();
                            $resData = array('st_ss' => $st_ss, 
                                'metering_type_id' => $selMetId,
                                'exceed_meter_type' => $exceed_meter_type,
                                'result' => 'SHOW_WIDGET'  
                            );
                            $response->getBody()->write(
                                json_encode(array(
                                    "code" => 1,
                                    "status" => 1,
                                    "message" => "Metered SHOW_WIDGET",
                                    "result" => $resData
                                ))
                            );
                        /*} else {
                            $selMetId = (int)$bdAccHistPostCout['exceed_meter_type'];
                            $resData = array('st_ss' => $st_ss, 
                                'metering_type_id' => $selMetId,//show the correct meter_type for loggedin/loggedout
                                'result' => 'SHOW_WIDGET'  
                            );
                            $response->getBody()->write(
                                json_encode(array(
                                    "code" => 1,
                                    "status" => 5,
                                    "message" => "Post already exists - Metered SHOW_WIDGET",
                                    "result" => $resData
                                ))
                            );
                        }*/
                    } else {
                        $resData = array('st_ss' => $st_ss, 
                            'result' => 'ALLOW_ACCESS'  
                        );
                        $response->getBody()->write(
                            json_encode(array(
                                "code" => 1,
                                "status" => 4,
                                "message" => "Not metered ALLOW_ACCESS",
                                "result" => $resData
                            ))
                        );
                    }
                } else {
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 3,
                            "message" => "Failed to create new session"
                        ))
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }

            } else {
                $this->logger->info('SiteAuthorizeAction: Invalid brand'.$brandId);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Invalid barnd"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
           
        }
        catch(MySQLException $e) {
            $this->logger->info('AddBrandAction: Error in creating new brand--'.$brand_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new brand'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddBrandAction: Error in creating new brand---'.$brand_name.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new brand'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }

   
}