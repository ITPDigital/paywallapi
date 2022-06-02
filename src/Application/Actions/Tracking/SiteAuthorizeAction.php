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

    public function createNewSession($brandId,$numOfDays,$db) {
       // $maxSSIdSql = $db->prepare("SELECT MAX(Id) as id FROM brand_access_sessions");
       // $maxSSIdSql->execute();
       // $maxSSId = $maxSSIdSql->fetch(PDO::FETCH_OBJ); 
        //$st_ss = date('Y-m-d h:i:s') . "|" . $maxSSId->id;
        $st_ss = date('Y-m-d h:i:s');
        $st_ss = hash('sha256', $st_ss);
        //echo ;exit;
        $expires_on = strtotime('+'.$numOfDays.' days');
        $expires_on = date("Y-m-d H:i:s", $expires_on);
        $bdAccSsSql = $db->prepare("INSERT INTO brand_access_sessions (brand_id,st_session, created_on, expires_on, is_active) VALUES (:brandId, :st_session, :created_on, :expires_on, :status)");
        $bdAccSsSql->execute(array(':brandId' => $brandId,':st_session' => $st_ss, ':created_on' => date('Y-m-d h:i:s'), ':expires_on' => $expires_on, ':status' => 1));
        if($bdAccSsSql->rowCount()>0) {
            return $st_ss;
        } else {
            return '';
        }
    }

    public function getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,$action_id,$brand_id,$db) {
        //echo $action_id;exit;
        $metTypeIdSql = $db->prepare("SELECT id,metering_action_id,name,content FROM map_metering_type where content_type_id=:content_type_id and content_category_id=:content_category_id and metering_action_id=:metering_action_id and is_active=:status and type=:type and brand_id=:brand_id");
        $metTypeIdSql->execute(array(':content_type_id' => $content_type_id,':content_category_id' => $content_category_id,':metering_action_id' => $action_id,':status' => 1,':type' => 1, ':brand_id' => $brand_id));
        $selMetertypeId = $metTypeIdSql->fetch(PDO::FETCH_OBJ); 
        return $selMetertypeId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('SiteAuthorizeAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        
        try {
            //$brandId = $commonHelper->resolveArg($request,'brandId');
            $brandDomain = isset($data['brandDomain']) ? $data["brandDomain"] : '';
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
            $this->logger->info('SiteAuthorizeAction: brandDomain'.$brandDomain);
            $date = date('Y-m-d h:i:s');
            $isLoggedIn = $request->getAttribute('isLoggedIn');
            $userId = $request->getAttribute('userId');

            $db =  $this->connection;
            $response = new Response();
            $isValidSs = 0;
            $brandSql = $db->prepare('SELECT b.*,cp.number_of_days from brands b LEFT JOIN const_periods cp on cp.disp_id=b.metering_period WHERE b.domain_name=:brandDomain and b.is_active=:status and cp.is_active=:status;');
            $brandSql->execute(array(':brandDomain' => $brandDomain,':status' => 1));
            $brandDatas = $brandSql->fetch(PDO::FETCH_ASSOC); 
        
            if(count($brandDatas)>0){
                $brandId = (int)$brandDatas['id'];
                $isUserSubscribed = 0;
               /* if($isLoggedIn == 'true' && $userId!=0) {
                    $userDetSql = $db->prepare("SELECT is_subscribed_user from users where id=:user_id and brand_id=:brand_id and is_active=:status");
                    $userDetSql->execute(array(':user_id' => $userId,':brand_id' => $brandId, ':status' => 1));
                    $userData = $userDetSql->fetch(PDO::FETCH_OBJ); 
                    $isUserSubscribed = (int)$userData['is_subscribed_user'];
                }*/
                if($isUserSubscribed==0) {
                    if($st_ss) {//session exists
                        $bdAccSsSql = $db->prepare('SELECT * from brand_access_sessions WHERE brand_id=:brandId and st_session=:st_ss and DATE(expires_on) > CURDATE() and is_active=:status;');
                        $bdAccSsSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss,':status' => 1));
                        $bdAccSsCount = $bdAccSsSql->rowCount();
                        if($bdAccSsCount>0) {//valid session
                            $isValidSs =  $st_ss;
                        } else {//create new session
                            $updBdAccSs = $db->prepare("UPDATE brand_access_sessions set is_active=:status where st_session = :st_ss");	
                            $updBdAccSs->execute(array(':status' => 0,':st_ss' =>$st_ss));
                            $isValidSs = $this->createNewSession($brandId,(int)$brandDatas['number_of_days'],$db);
                        }
                    
                    } else {//new access
                        $isValidSs = $this->createNewSession($brandId,(int)$brandDatas['number_of_days'],$db);
                    }
                    if($isValidSs!='') {//valid session
                        if($st_notmetered=='false' && $viewfree!='true') {
                                /*$bdAccHistSql = $db->prepare('SELECT * from brand_access_history WHERE brand_id=:brandId and st_session=:st_ss and post_id!=:post_id and post_url!=:post_url');
                                $bdAccHistSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss,':post_id' => $post_id,':post_url' => $post_url));*/
                                $bdAccHistSql = $db->prepare('SELECT * from brand_access_history WHERE brand_id=:brandId and st_session=:st_ss and post_id!=:post_id');
                                $bdAccHistSql->execute(array(':brandId' => $brandId,':st_ss' => $st_ss,':post_id' => $post_id));
                                $bdAccHistCout = $bdAccHistSql->rowCount();
                                $paywallData = [];
                                $isNewEntry = true;
                                $widgetType = 2;//notifier
                                if($isLoggedIn == 'true') {
                                    if($premium=="true" || $content_type == "magazine_issue") {
                                        $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,2,$brandId,$db);//require entitlement
                                        $isNewEntry = false;
                                        $widgetType = 1;//paywall
                                    } else {
                                        if($bdAccHistCout >= ((int)$brandDatas['max_limit'] + (int)$brandDatas['offered_limit'])) {
                                            $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,2,$brandId,$db);//require entitlement
                                            $isNewEntry = false;
                                            $widgetType = 1;//paywall
                                        }
                                    }
                                } else {
                                    if($premium=="true" || $content_type == "magazine_issue") {
                                        //echo $brandId;exit;
                                        $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,3,$brandId,$db);//require login with entitlement
                                        $isNewEntry = false;
                                        $widgetType = 1;//paywall
                                    } else if($register=="true") {
                                        $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,1,$brandId,$db);//require login
                                        $isNewEntry = false;
                                        $widgetType = 1;//paywall
                                    } else {
                                        if($bdAccHistCout >= ((int)$brandDatas['max_limit'] + (int)$brandDatas['offered_limit'])) {
                                            $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,3,$brandId,$db);//require login with entitlement
                                            $isNewEntry = false;
                                            $widgetType = 1;//paywall
                                        }
                                        else if($bdAccHistCout >= (int)$brandDatas['max_limit']) {
                                            //$metring_action_id = 2
                                            $paywallData = $this->getMeteringTypeId($content_type_id,$content_category_id,$isLoggedIn,1,$brandId,$db);//require login
                                            $isNewEntry = false;
                                            $widgetType = 1;//paywall
                                        }
                                    }
                                }
                                $notifierData = [];
                                //TODO:insert only if post id not exists
                                if($isNewEntry) {
                                    $postExistSql = $db->prepare("select id from brand_access_history where st_session=:st_session and post_id=:post_id and brand_id=:brand_id and post_url=:post_url");
                                    $postExistSql->execute(array(':st_session' => $isValidSs, ':post_id' => $post_id, ':brand_id' => $brandId, ':post_url' => $post_url));
                                    $isPostExists = $postExistSql->rowCount();
                                    if($isPostExists==0) {
                                        $insBdAccHist = $db->prepare("INSERT INTO brand_access_history (brand_id, content_type, st_session, post_id, post_url, content_type_id, content_category_id, viewed_on, is_active) VALUES (:brand_id, :content_type, :st_session, :post_id, :post_url, :content_type_id, :content_category_id, :viewed_on, :is_active)");
                                        $insBdAccHist->execute(array(':brand_id' => $brandId, ':content_type' => $content_type, ':st_session' => $isValidSs, ':post_id' => $post_id, ':post_url' => $post_url, ':content_type_id' => $content_type_id, ':content_category_id' => $content_category_id,':viewed_on' => $date, ':is_active' => 1));
                                        $insBdAccHist = $insBdAccHist->rowCount();
                                    }
                                    if($widgetType==2) {//notifier
                                        $is_logged_in = $isLoggedIn == 'true' ? 1: 0;
                                        $notifierSql = $db->prepare("SELECT id,metering_action_id,name,custom_count,content FROM map_metering_type where type=:type and is_logged_in=:is_logged_in and brand_id=:brand_id and is_active=:status");
                                        $notifierSql->execute(array(':type' => 2,':is_logged_in' => $is_logged_in,':brand_id' => $brandId,':status' => 1));
                                        $notifierData = $notifierSql->fetchAll(PDO::FETCH_ASSOC); 
                                        foreach($notifierData as $key=>$row){
                                            if((int)$row['metering_action_id']==4) {//all metered
                                                $notifierData = $row;
                                            } else if((int)$row['metering_action_id']==5) {//custom count
                                                if(($bdAccHistCout+1)== (int)$row['custom_count']) {
                                                    $notifierData = $row;
                                                }
                                            } 
                                        }
                                    }

                                }
                                
                                $resData = array('stSS' => $isValidSs, 
                                        'paywall' => $paywallData,
                                        'notifier' => $notifierData,
                                        'count' => $bdAccHistCout,
                                        'newEntry' => $isNewEntry,
                                        'maxLimit' => $brandDatas['max_limit'],
                                        'offeredLimit' => $brandDatas['offered_limit'],
                                        'widgetType' => $widgetType,
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
                        } else {
                            $resData = array('stSS' => $isValidSs, 
                                'paywall' => 'ALLOW_ACCESS',
                                'maxLimit' => $brandDatas['max_limit'],
                                'offeredLimit' => $brandDatas['offered_limit']
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
                    $resData = array('paywall' => 'ALLOW_ACCESS'
                            );
                            $response->getBody()->write(
                                json_encode(array(
                                    "code" => 1,
                                    "status" => 5,
                                    "message" => "Subscribed user ALLOW_ACCESS",
                                    "result" => $resData
                                ))
                            );
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