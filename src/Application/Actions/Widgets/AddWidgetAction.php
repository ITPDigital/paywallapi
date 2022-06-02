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
use App\Application\Helpers\WidgetHelper;

class AddWidgetAction implements RequestHandlerInterface
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
        $this->logger->info('AddWidgetAction: handler dispatched');
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
            $this->logger->info('AddWidgetAction: widgetName'.$widgetName);
            $db =  $this->connection;
            $response = new Response();
            $widgetHelper = new WidgetHelper();
            $isRuleExists = $widgetHelper->isRuleExists($db, $brandId, $widgetType, $actionType, $contentType, $customCount, $contentCategory, $isLoggedIn, $comp_id, 0);
            if($isRuleExists>0) {
                $this->logger->info('AddWidgetAction: Rule already exists'.$widgetName);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Rule already exists"
                    ))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
            $sql = $db->prepare("INSERT INTO map_metering_type (brand_id, comp_id, type, widget_group_id, metering_action_id, custom_count, description, name, content_type_id, content_category_id, is_logged_in, content, created_on, created_by, is_active) VALUES 
            (:brand_id, :comp_id, :type, :widget_group_id, :metering_action_id, :custom_count, :description, :name, :content_type_id, :content_category_id, :is_logged_in, :content, :created_on, :created_by, :is_active)");
            $sql->execute(array(':brand_id' => $brandId, ':comp_id' => $comp_id, ':type' => $widgetType, ':widget_group_id' => $widgetGroup, ':metering_action_id' => $actionType, ':custom_count' => $customCount, ':description' => $widgetDesc, ':name' => $widgetName, ':content_type_id' => $contentType, ':content_category_id' => $contentCategory, ':is_logged_in' => $isLoggedIn, ':content' => $content,':created_by' => $user_id, ':created_on' => $date,':is_active' => $status));
            $lastinserid = $db->lastInsertId();
            if($lastinserid) {
                $this->logger->info('AddWidgetAction: New widget created successfully'.$widgetName);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "New widget created successfully",
                        "id" => (int)$lastinserid
                    ))
                );
            } else {
                $this->logger->info('AddWidgetAction: Failed to create new widget'.$widgetName);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "Failed to create new widget"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('AddWidgetAction: Error in creating new widget--'.$widgetName.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new widget'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('AddWidgetAction: Error in creating new widget---'.$widgetName.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in creating new widget'
                ))
            );
        }
		return $response->withHeader('Content-Type', 'application/json');
    }
}