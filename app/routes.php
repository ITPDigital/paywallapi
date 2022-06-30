<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Users\RegisterAction;
use App\Application\Actions\Users\LoginAction;
use App\Application\Actions\Users\ChangePwdAction;
use App\Application\Actions\Users\ViewUserAction;
use App\Application\Actions\Users\UpdateUserAction;
use App\Application\Actions\Users\ListUserAction;
use App\Application\Actions\Users\DeleteUserAction;
use App\Application\Actions\Users\ForgotPasswordAction;
use App\Application\Actions\Users\ResetPasswordAction;
use App\Application\Actions\Users\UpdateUserFromAdminAction;
use App\Application\Actions\Users\AddUserFromAdminAction;
use App\Application\Actions\Users\ViewUserWithOrderAction;

use App\Application\Actions\Admin\AdminLoginAction;
use App\Application\Actions\Admin\AdminChangePwdAction;
use App\Application\Actions\Admin\Users\AddAdminUserAction;
use App\Application\Actions\Admin\Users\DeleteAdminUserAction;
use App\Application\Actions\Admin\Users\ListAdminUserAction;
use App\Application\Actions\Admin\Users\UpdateAdminUserAction;
use App\Application\Actions\Admin\Users\ViewAdminUserAction;
use App\Application\Actions\Admin\Users\UpdateAdminUserPwdAction;

use App\Application\Actions\Brands\AddBrandAction;
use App\Application\Actions\Brands\ViewBrandAction;
use App\Application\Actions\Brands\DeleteBrandAction;
use App\Application\Actions\Brands\ListBrandByStatusAction;
use App\Application\Actions\Brands\ListBrandAction;
use App\Application\Actions\Brands\UpdateBrandAction;

use App\Application\Actions\Products\AddProductAction;
use App\Application\Actions\Products\ViewProductAction;
use App\Application\Actions\Products\ListProductAction;
use App\Application\Actions\Products\ListBrandProductAction;
use App\Application\Actions\Products\ListSubBrandProductAction;
use App\Application\Actions\Products\GetProductPromoPlanAction;
use App\Application\Actions\Products\DeleteProductAction;
use App\Application\Actions\Products\UpdateProductAction;

use App\Application\Actions\Plans\AddProPlanAction;
use App\Application\Actions\Plans\ViewProPlanAction;
use App\Application\Actions\Plans\ListProPlanAction;
use App\Application\Actions\Plans\DeleteProPlanAction;
use App\Application\Actions\Plans\UpdateProPlanAction;
use App\Application\Actions\Plans\ListProPlanByStatusAction;
use App\Application\Actions\Plans\ListProPlanWithPrice;

use App\Application\Actions\PlanFeatures\AddProPlanFeatureAction;
use App\Application\Actions\PlanFeatures\ListProPlanFeatureAction;
use App\Application\Actions\PlanFeatures\DeleteProPlanFeatureAction;
use App\Application\Actions\PlanFeatures\UpdateProPlanFeatureAction;

use App\Application\Actions\PlanPromos\AddProPlanPromoAction;
use App\Application\Actions\PlanPromos\ListProPlanPromoAction;
use App\Application\Actions\PlanPromos\ListProPlanPromoByStatusAction;
use App\Application\Actions\PlanPromos\ViewProPlanPromoAction;
use App\Application\Actions\PlanPromos\DeleteProPlanPromoAction;
use App\Application\Actions\PlanPromos\UpdateProPlanPromoAction;

use App\Application\Actions\PlanDiscounts\AddProPlanDiscountAction;
use App\Application\Actions\PlanDiscounts\ListProPlanDiscountAction;
use App\Application\Actions\PlanDiscounts\ListProPlanDiscountByStatusAction;
use App\Application\Actions\PlanDiscounts\ViewProPlanDiscountAction;
use App\Application\Actions\PlanDiscounts\DeleteProPlanDiscountAction;
use App\Application\Actions\PlanDiscounts\UpdateProPlanDiscountAction;

use App\Application\Actions\Config\Periods\ListAllPeriodsByStatusAction;
use App\Application\Actions\Config\Currency\ListAllCurrenciesByStatusAction;
use App\Application\Actions\Config\UserRole\ListAllUserRolesByStatusAction;
use App\Application\Actions\Config\Currency\ListAllCurrenciesAction;
use App\Application\Actions\Config\Periods\ListAllPeriodsAction;
use App\Application\Actions\Config\UserRole\ListAllUserRolesAction;
use App\Application\Actions\Config\ContentCategory\ListAllContentCategoryAction;
use App\Application\Actions\Config\ContentType\ListAllContentTypeAction;
use App\Application\Actions\Config\MetActionType\ListAllMetActionTypeAction;
use App\Application\Actions\Config\CancelReasons\ListAllCancelReasonsAction;
use App\Application\Actions\Config\PaymentType\ListAllPaymentTypeAction;

use App\Application\Actions\EmailTemplates\AddEmailTypeAction;
use App\Application\Actions\EmailTemplates\ListEmailTypeAction;
use App\Application\Actions\EmailTemplates\AddEmailAction;
use App\Application\Actions\EmailTemplates\DeleteEmailAction;
use App\Application\Actions\EmailTemplates\ListAllEmailAction;
use App\Application\Actions\EmailTemplates\UpdateEmailAction;
use App\Application\Actions\EmailTemplates\ViewEmailAction;

use App\Application\Actions\Widgets\AddWidgetGroupAction;
use App\Application\Actions\Widgets\ListWidgetGroupAction;
use App\Application\Actions\Widgets\ListAllWidgetsAction;
use App\Application\Actions\Widgets\ViewWidgetAction;
use App\Application\Actions\Widgets\DeleteWidgetAction;
use App\Application\Actions\Widgets\ListAllWidgetConstantsAction;
use App\Application\Actions\Widgets\AddWidgetAction;
use App\Application\Actions\Widgets\UpdateWidgetAction;

use App\Application\Actions\Orders\AddOrderAction;
use App\Application\Actions\Orders\ViewOrderAction;
use App\Application\Actions\Orders\ViewTransHistoryAction;

use App\Application\Actions\Tracking\SiteAuthorizeAction;
use App\Application\Actions\Tracking\GetBrandDetailsAction;

use App\Application\Actions\Reports\ListAllReportsTypeAction;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Cake\Validation\Validator;
use Selective\Validation\ValidationResult;
use Selective\Validation\Factory\CakeValidationFactory;
use Selective\Validation\Exception\ValidationException;
use \Firebase\JWT\JWT;
use Slim\Exception\HttpNotFoundException;

use App\Application\Middleware\UserMiddleware;
use App\Application\Middleware\AdminRoleMiddleware;

return function (App $app) {
   $app->options('/{routes:.+}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
       $response->getBody()->write('Hello world!');
      // echo json_encode($request->headers()->all());exit;
        return $response;
    });

    $app->get('/test1', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });
	
    $app->get('/pdf', function (Request $request, Response $response) {

		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false, true);

		$pdf->addPage();

		
		$html = '<h1 style="text-align:center;">Invoice</h1>
<table cellpadding="1" cellspacing="1" border="1" style="text-align:center;">
<tr><td>1</td></tr>
<tr style="text-align:center;"><td>2</td></tr>
<tr style="text-align:center;"><td>3</td></tr>
<tr style="text-align:center;"><td>3</td></tr>
<tr><td style="text-align:center;">4</td></tr>
<tr><td style="text-align:center;">5</td></tr>
<tr><td style="text-align:center;">6</td></tr>
</table>';

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

$content = $pdf->output();

$response->getBody()->write($content);

$response = $response
    ->withHeader('Content-Type', 'application/pdf')
    ->withHeader('Content-Disposition', 'attachment; filename="filename.pdf"');

return $response;

	
	
      //  return $response;
    });	

    /*$app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });*/

    $app->get('/db-test', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
        $sth = $db->prepare("select * from users limit 10");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
		$response
				->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
				->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Content-Type', 'application/json');
        return $response;
    });

    // logout
    $app->get('/logout', function ($request, $response) {
        \RKA\Session::destroy();
        return $response->withRedirect('/');
    });

    $app->post('/test', function (Request $request, Response $response, $args): Response {
        $data = $request->getParsedBody();
        
        $html = var_export($data, true);
        $response->getBody()->write($html);
        
        return $response;
    });

    //tracking actions
    //$app->post('/v1/authorize', SiteAuthorizeAction::class)->add(new SiteAuthMiddleware());
    $app->post('/v1/authorize', GetBrandDetailsAction::class);
    $app->post('/v1/subscription/plans', ListSubBrandProductAction::class);//index page

    //user actions
    $app->post('/login', LoginAction::class);
    $app->post('/register', RegisterAction::class);	
    $app->post('/forgotpassword', ForgotPasswordAction::class);	
    $app->post('/reset-password/{token}', ResetPasswordAction::class);	

    //admin action
    $app->post('/admin/login', AdminLoginAction::class); 	
	
    $app->group('/v1/admin', function (Group $group) {
        $group->post('/changepwd', AdminChangePwdAction::class);
        $group->post('/customer/add', AddUserFromAdminAction::class);
        $group->get('/customer/{id}/{brandId}', ViewUserWithOrderAction::class);
        $group->post('/customer/{id}/{brandId}', UpdateUserFromAdminAction::class);
    })->add(new UserMiddleware());

    $app->group('/v1/admin/settings', function (Group $group) {
        $group->post('/user/add', AddAdminUserAction::class);
        $group->get('/users', ListAdminUserAction::class);
        $group->get('/user/{id}', ViewAdminUserAction::class);
        $group->post('/user/del/{id}/{status}', DeleteAdminUserAction::class);
        $group->post('/user/update/{id}', UpdateAdminUserAction::class);
        $group->post('/user/updatepwd/{id}', UpdateAdminUserPwdAction::class);
    })->add(new AdminRoleMiddleware());

	//end user actions
    $app->group('/v1/users', function (Group $group) {
        $group->get('/{brandId}', ListUserAction::class);
        $group->post('/changePwd/{id}', ChangePwdAction::class);
        $group->get('/view/{id}', ViewUserAction::class);
        $group->post('/update/{id}', UpdateUserAction::class);
        $group->post('/del/{id}/{status}', DeleteUserAction::class);
    })->add(new UserMiddleware());

    //brand actions
    $app->group('/v1/brands', function (Group $group) {
        $group->get('', ListBrandAction::class);
        $group->get('/{status}', ListBrandByStatusAction::class);
        $group->post('/add', AddBrandAction::class);
        $group->get('/view/{id}', ViewBrandAction::class);
        $group->post('/update/{id}', UpdateBrandAction::class);
        $group->post('/del/{id}/{status}', DeleteBrandAction::class);
    })->add(new UserMiddleware());

    //product actions
    $app->group('/v1/products', function (Group $group) {
        $group->get('', ListProductAction::class);
        $group->get('/brand/{brandId}', ListBrandProductAction::class);
        $group->get('/promocode/{proId}/{promoCode}', GetProductPromoPlanAction::class);
        $group->post('/add', AddProductAction::class);
        $group->get('/view/{id}', ViewProductAction::class);
        $group->post('/update/{id}', UpdateProductAction::class);
        $group->post('/del/{id}/{status}', DeleteProductAction::class);
    })->add(new UserMiddleware());

    //product plan actions
    $app->group('/v1/plans', function (Group $group) {
        $group->get('', ListProPlanAction::class);
        $group->get('/{status}', ListProPlanByStatusAction::class);
        $group->get('/price/{status}', ListProPlanWithPrice::class);
        $group->post('/add', AddProPlanAction::class);
        $group->get('/view/{id}', ViewProPlanAction::class);
        $group->post('/update/{id}', UpdateProPlanAction::class);
        $group->post('/del/{id}/{status}', DeleteProPlanAction::class);
    })->add(new UserMiddleware());

    //product plan promo actions
    $app->group('/v1/promos', function (Group $group) {
        $group->get('', ListProPlanPromoAction::class);
        $group->get('/{status}', ListProPlanPromoByStatusAction::class);
        $group->post('/add', AddProPlanPromoAction::class);
        $group->get('/view/{id}', ViewProPlanPromoAction::class);
        $group->post('/update/{id}', UpdateProPlanPromoAction::class);
        $group->post('/del/{id}/{status}', DeleteProPlanPromoAction::class);
    })->add(new UserMiddleware());

    //product plan discounts
    $app->group('/v1/discounts', function (Group $group) {
        $group->get('', ListProPlanDiscountAction::class);
        $group->get('/{status}', ListProPlanDiscountByStatusAction::class);
        $group->post('/add', AddProPlanDiscountAction::class);
        $group->get('/view/{id}', ViewProPlanDiscountAction::class);
        $group->post('/update/{id}', UpdateProPlanDiscountAction::class);
        $group->post('/del/{id}/{status}', DeleteProPlanDiscountAction::class);
    })->add(new UserMiddleware());

    //constants
    $app->group('/v1/constants', function (Group $group) {
        $group->get('/periods/{status}', ListAllPeriodsByStatusAction::class);
        $group->get('/currencies/{status}', ListAllCurrenciesByStatusAction::class);
        $group->get('/roles/{status}', ListAllUserRolesByStatusAction::class);
        $group->get('/currencies', ListAllCurrenciesAction::class);
        $group->get('/periods', ListAllPeriodsAction::class);
        $group->get('/roles', ListAllUserRolesAction::class);
        $group->get('/contentcategory', ListAllContentCategoryAction::class);
        $group->get('/contenttype', ListAllContentTypeAction::class);
        $group->get('/metacttype', ListAllMetActionTypeAction::class);
        $group->get('/cancelreasons', ListAllCancelReasonsAction::class);
        $group->get('/paymenttypes', ListAllPaymentTypeAction::class);
    })->add(new UserMiddleware());

    //Widgets
    $app->group('/v1/widgets', function (Group $group) {
        $group->get('/group', ListWidgetGroupAction::class);
        $group->post('/group/add', AddWidgetGroupAction::class);
        $group->get('', ListAllWidgetsAction::class);
        $group->get('/view/{id}', ViewWidgetAction::class);
        $group->get('/constants', ListAllWidgetConstantsAction::class);
        $group->post('/del/{id}/{status}', DeleteWidgetAction::class);
        $group->post('/add', AddWidgetAction::class);
        $group->post('/update/{id}', UpdateWidgetAction::class);
    })->add(new UserMiddleware());

    //Email templates
    $app->group('/v1/email', function (Group $group) {
        $group->get('/type', ListEmailTypeAction::class);
        $group->post('/type/add', AddEmailTypeAction::class);
        $group->get('', ListAllEmailAction::class);
        $group->post('/add', AddEmailAction::class);
        $group->post('/del/{id}/{status}', DeleteEmailAction::class);
        $group->post('/update/{id}', UpdateEmailAction::class);
        $group->get('/view/{id}', ViewEmailAction::class);
    })->add(new UserMiddleware());
	
    //order actions
    $app->group('/v1/orders', function (Group $group) {
        $group->post('/add', AddOrderAction::class);
        $group->get('/view/{userId}/{brandId}', ViewOrderAction::class);
        $group->get('/{orderId}/account/{userId}/brand/{brandId}', ViewTransHistoryAction::class);     
    })->add(new UserMiddleware());

    //reports actions
    $app->group('/v1/reports', function (Group $group) {
        $group->get('', ListAllReportsTypeAction::class);
    })->add(new UserMiddleware());
     
// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
	
};
