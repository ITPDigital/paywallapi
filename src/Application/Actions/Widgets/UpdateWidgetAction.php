<?php

declare(strict_types=1);

namespace App\Application\Actions\Widgets;

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
use App\Application\Helpers\WidgetHelper;


class UpdateWidgetAction implements RequestHandlerInterface
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
        $commonHelper = new CommonHelper();
        $this->logger->info('UpdateWidgetAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('widgetType', 'Field required')
			->requirePresence('widgetType')
            ->notEmptyString('brandId', 'Field required')
			->requirePresence('brandId')
            ->notEmptyString('actionType', 'Field required')
			->requirePresence('actionType')
            ->notEmptyString('widgetDesc', 'Field required')
			->requirePresence('widgetDesc')
            ->notEmptyString('widgetName', 'Field required')
			->requirePresence('widgetName')
            ->notEmptyString('widgetGroup', 'Field required')
			->requirePresence('widgetGroup')
            ->notEmptyString('widgetContent', 'Field required')
			->requirePresence('widgetContent');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $comp_id = $request->getAttribute('compid');
            $widgetType = isset($data['widgetType']) ? $data["widgetType"] : '';
            $brandId = isset($data['brandId']) ? $data["brandId"] : '';
            $actionType = isset($data['actionType']) ? $data["actionType"] :  '';
            $widgetDesc = isset($data['widgetDesc']) ? $data["widgetDesc"] :  '';
            $widgetName = isset($data['widgetName']) ? $data["widgetName"] :  '';
            $widgetGroup = isset($data['widgetGroup']) ? $data["widgetGroup"] :  '';
            $contentType = isset($data['contentType']) ? $data["contentType"] :  '';
            $customCount = isset($data['customCount']) ? $data["customCount"] :  '0';
            $contentCategory = isset($data['contentCategory']) ? $data["contentCategory"] :  '';
            $content = isset($data['widgetContent']) ? $data["widgetContent"] :  '';
            $isLoggedIn = isset($data['isLoggedIn']) ? $data["isLoggedIn"] :  '';
            $status = isset($data['status']) ? $data["status"] :  '';
            $user_id = $request->getAttribute('userid');
            $date = date('Y-m-d h:i:s');
            $this->logger->info('UpdateWidgetAction: widgetId'.$id);
            $db =  $this->connection;
            $response = new Response();
            $widgetHelper = new WidgetHelper();
            $isRuleExists = $widgetHelper->isRuleExists($db, $brandId, $widgetType, $actionType, $contentType, $customCount, $contentCategory, $isLoggedIn, $comp_id, $id);
            if($isRuleExists>0) {
                $this->logger->info('UpdateWidgetAction: Rule already exists'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Rule already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $sql = $db->prepare("UPDATE map_metering_type set brand_id = :brand_id,type=:type,widget_group_id=:widget_group_id,metering_action_id=:metering_action_id,custom_count=:custom_count,description=:description,name=:name,content_type_id=:content_type_id,content_category_id=:content_category_id,is_logged_in=:is_logged_in,content=:content,updated_on=:updated_on,updated_by=:updated_by,is_active=:is_active where id = :id and comp_id=:comp_id");		
            $sql->execute(array(':brand_id'=>$brandId,
            ':type' => $widgetType,
            ':widget_group_id' => $widgetGroup, 
            ':metering_action_id' => $actionType, 
            ':custom_count' => $customCount,
            ':description' => $widgetDesc,
            ':name' => $widgetName,
            ':content_type_id' => $contentType,
            ':content_category_id' => $contentCategory, 
            ':is_logged_in' => $isLoggedIn,
            ':content' => $content,
            ':updated_on' => $date,
            ':updated_by' => $user_id, 
            ':is_active' => $status,
            ':id' => $id,
            ':comp_id' => $comp_id));
            $count = $sql->rowCount();
            if($count) {
                $this->logger->info('UpdateWidgetAction: Widget data updated successfully'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Widget data updated successfully",
                        "id" => (int)$id,
                        "count" => $isRuleExists,
                    ))
                );
            } else {
                $this->logger->info('UpdateWidgetAction: Failed to update Widget'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 3,
                        "message" => "Failed to update Widget"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('UpdateWidgetAction: Error in updating Widget detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating Widget detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('UpdateWidgetAction: Error in updating Widget detail---'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in updating Widget detail'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}