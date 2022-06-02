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

use App\Application\Actions\Constants\ListAllPeriodsByStatusAction;
use App\Application\Actions\Constants\ListAllCurrenciesByStatusAction;
use App\Application\Actions\Constants\ListAllUserRolesByStatusAction;

use App\Application\Actions\EmailTemplates\AddEmailTypeAction;
use App\Application\Actions\EmailTemplates\ListEmailTypeAction;

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

use App\Application\Actions\Tracking\SiteAuthorizeAction;
use App\Application\Actions\Tracking\GetBrandDetailsAction;

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

    /*$app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });*/

    $app->get('/db-test', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
        $sth = $db->prepare("select * from email_type");
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
    $app->post('/v1/subscription/plans', ListSubBrandProductAction::class);

    //user actions
    $app->post('/login', LoginAction::class);
    $app->post('/register', RegisterAction::class);	
    $app->post('/forgotpassword', ForgotPasswordAction::class);	
    $app->post('/reset-password/{token}', ResetPasswordAction::class);	

    //admin actions
   // $app->post('/admin/login', AdminLoginAction::class);
    //$app->post('/admin/add', AddAdminUserAction::class);

       $app->post('/admin/login', AdminLoginAction::class); 
      // $app->post('/admin/user/add', AddAdminUserAction::class)->add(new UserMiddleware());
      // $app->post('/v1/admin/customer/add', RegisterAction::class)->add(new UserMiddleware());	
	
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
    })->add(new UserMiddleware());
	
    //order actions
    $app->group('/v1/orders', function (Group $group) {
        $group->post('/add', AddOrderAction::class);
        $group->get('/view/{userId}/{brandId}', ViewOrderAction::class);        
    })->add(new UserMiddleware());
     
	 //$group->get('/v1/order', ListOrderAction::class);

    /*$app->post('/changePwd', ChangePwdAction::class);	
	$app->get('/viewUser', ViewUserAction::class);	
	$app->post('/updateUser', UpdateUserAction::class);
    $app->get('/getAllUsers', ListUserAction::class);
	$app->post('/deleteUser', DeleteUserAction::class);*/

    /*$app->post('/addBrand', AddBrandAction::class);	
    $app->get('/viewBrand', ViewBrandAction::class);
    $app->post('/deleteBrand', DeleteBrandAction::class);
    $app->get('/getAllBrands', ListBrandAction::class);
    $app->post('/updateBrand', UpdateBrandAction::class);
    
    $app->post('/addProduct', AddProductAction::class);	
    $app->get('/viewProduct', ViewProductAction::class);
    $app->post('/deleteProduct', DeleteProductAction::class);
    $app->get('/getAllProducts', ListProductAction::class);
    $app->get('/getBrandProducts', ListBrandProductAction::class);
    $app->post('/updateProduct', UpdateProductAction::class);

    $app->post('/addProPlan', AddProPlanAction::class);	
    $app->get('/viewProPlan', ViewProPlanAction::class);
    $app->post('/deleteProPlan', DeleteProPlanAction::class);
    $app->get('/getAllProPlans', ListProPlanAction::class);
    $app->post('/updateProPlan', UpdateProPlanAction::class);

    $app->post('/addProPlanPromo', AddProPlanPromoAction::class);	
    $app->get('/viewProPlanPromo', ViewProPlanPromoAction::class);
    $app->post('/updateProPlanPromo', UpdateProPlanPromoAction::class);
    $app->get('/getAllProPlanPromos', ListProPlanPromoAction::class);
    $app->post('/deleteProPlanPromo', DeleteProPlanPromoAction::class);

    $app->post('/addProPlanDiscount', AddProPlanDiscountAction::class);	
    $app->get('/viewProPlanDiscount', ViewProPlanDiscountAction::class);
    $app->post('/updateProPlanDiscount', UpdateProPlanDiscountAction::class);
    $app->get('/getAllProPlanDiscount', ListProPlanDiscountAction::class);
    $app->post('/deleteProPlanDiscount', DeleteProPlanDiscountAction::class);*/


    //product plan features actions
   // $app->post('/addProPlanFeature', AddProPlanFeatureAction::class);	
   // $app->post('/deleteProPlanFeature', DeleteProPlanFeatureAction::class);
   // $app->get('/getAllProPlanFeatures', ListProPlanFeatureAction::class);
  //  $app->post('/updateProPlanFeature', UpdateProPlanFeatureAction::class);

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
	
};
