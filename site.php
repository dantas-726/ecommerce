<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


$app->get('/', function () {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
		'products'=>Product::checkList($products)
	]);
});

$app->get("/categories/:idcategory", function($idcategory){

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getProductsPage($page);
	$pages = [];

	for ($i = 1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [
			'link' => '/categories/' . $category->getidcategory() . '?page=' . $i,
			'page' => $i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category' => $category->getValues(),
		'products' => $pagination["data"],
		'pages' => $pages
	]);
});


$app->get("/products/:desurl", function ($desurl) {

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product' => $product->getValues(),
		'categories' => $product->getCategories()
	]);
});


$app->get("/cart", function () {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart' => $cart->getValues(),
		'products' => $cart->getProducts(),
		'error'=>Cart::getMsgError()

	]);
});

$app->get("/cart/:idproduct/add", function ($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i = 0; $i < $qtd; $i++) {

		$cart->addProduct($product);
	}

	header("Location: /cart");
	exit;
});


$app->get("/cart/:idproduct/minus", function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct){

	$product = new Product();
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});


$app->post("/cart/freight", function () {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->post("/cart/freight", function () {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->get("/checkout", function () {

	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("checkout", [
		'cart' => $cart->getValues(),
		'address' => $address->getValues()
	]);
});

$app->get("/login", function(){

	$page = new Page();
	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues' => (isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']
	]);
});


$app->post("/login", function () {

	try {

		User::login($_POST['login'], $_POST['password']);
	} catch (Exception $e) {

		User::setError($e->getMessage());
	}

	header("Location: /");
	exit;
});

$app->get("/logout", function(){

	User::logout();

	header("Location: /login");
	exit;

});

$app->post("/register", function(){

	$_SESSION['registerValues'] = $_POST;

	if (!isset($_POST['name']) || $_POST['name'] == '') {

		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['email']) || $_POST['email'] == '') {

		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['password']) || $_POST['password'] == '') {

		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;
	}

	if (User::checkLoginExist($_POST['email']) === true) {

		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST[ 'phone']

	]);

	$user->save();
	User::login($_POST['email'], $_POST['password']);
	header('Location: /checkout');
	exit;

});




$app->get("/profile", function () {

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		'user' => $user->getValues(),
		'profileMsg' => User::getSuccess(),
		'profileError' => User::getError()
	]);
});

$app->post("/profile", function () {

	User::verifyLogin(false);

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Preencha o seu nome.");
		header('Location: /profile');
		exit;
	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	if(isset($_POST['despassword']) && $_POST['despassword'] === '') {
		$_POST['despassword'] = $_POST['despassword'];

	}

	$user = User::getFromSession();

	if ($_POST['desemail'] !== $user->getdesemail()) {

		if (User::checkLoginExist($_POST['desemail']) === true) {

			User::setError("Este endereço de e-mail já está cadastrado.");
			header('Location: /profile');
			exit;
		}
	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;
});



$app->get("/profile/change-password", function () {

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError' => User::getError(),
		'changePassSuccess' => User::getSuccess()
	]);
});

$app->post("/profile/change-password", function () {

	User::verifyLogin(false);

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {

		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {

		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['current_pass'] === $_POST['new_pass']) {

		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;
	}

	$user = User::getFromSession();

	if (!password_verify($_POST['current_pass'], $user->getdespassword())) {

		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;
	}

	$user->setdespassword($_POST['new_pass']);

	$user->update();

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /profile/change-password");
	exit;
});

