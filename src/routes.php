<?php
	use Slim\App;
	use Slim\Http\Request;
	use Slim\Http\Response;

	return function (App $app) {
		$container = $app->getContainer();

		$app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
			require_once './core/defines.php';
			$_SESSION['alm'] = 0;
			$this->logger->info("Slim-Skeleton '/' ".(isset($args['name'])?$args['name']:''));
			if(!isset($args['name'])) { $args['name'] = HOMEPAGE; }
			
			if(!isset($_SESSION)) { session_start(); }
			if((isset($_SESSION['usuario']))
			) {
				//if($args['name'] == '') {
				//	return $this->view->render($response, 'notificaciones.phtml', $args);
				//}else{
					// $arrMod = array('usuarios'=>5, 'perfil'=>5, 'prod-terminado'=>20, 'prod-terminado' );
					$params = array('vista' => ucfirst($args['name']));
					try{
						// if(array_key_exists($args['name'], $arrMod)) {
							// $modulo = $arrMod[$args['name']];
							$user = $_SESSION['usuario']->id_usuario;
							// $perm = $this->model->usuario->getAcciones($user, $modulo);
							$perm = $this->model->usuario->getAcciones($user, 0);
							$arrPerm = getPermisos($perm);

						$params = array('vista' => ucfirst($args['name']), 'permisos' => $arrPerm, 'todo' => $this);
							// if($args['name']=='resultados') {
							// 	date_default_timezone_set('America/Mexico_City');

							// 	$params['permisos'] = array_merge($arrPerm, getPermisos($this->model->usuario->getAcciones($user, 4)));
							// }

							// if(in_array($modulo, $arrPerm))
							// if(hasPermission($modulo))
								return $this->view->render($response, "$args[name].phtml", $params);
							// else
							// 	return $this->renderer->render($response, '403.phtml', $params);
						// }
							
						return $this->view->render($response, '403.phtml', $params);
					} catch (Throwable | Exception $e) {
						return $this->renderer->render($response, '404.phtml', $params);
					}
				//}
				// return $this->renderer->render($response, "$args[name].phtml", $args);
			}
			elseif($args['name']!='login') {
				return $this->response->withRedirect(URL_ROOT.'/login');
			} else {
				return $this->renderer->render($response, 'login.phtml', $args);
			}
		});

		$app->get('/almacen/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'almacen/' ".$args['page']);

			$modulo = 5;
			$_SESSION['alm'] = $modulo;
			$user = $_SESSION['usuario']->id_usuario;
			$perm = $this->model->usuario->getAcciones($user, $modulo);
			$arrPerm = getPermisos($perm);

			$params = array('vista' => ucfirst($args['page']), 'permisos' => $arrPerm, 'todo' => $this, 'perm' => $perm);
			return $this->renderer->render($response, 'alm_'.$args['page'].'.phtml', $params);
		});

		$app->get('/comunidades/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'comunidad/' ".$args['page']);
			
			$modulo = 6;
			$_SESSION['alm'] = $modulo;
			$user = $_SESSION['usuario']->id_usuario;
			$perm = $this->model->usuario->getAcciones($user, $modulo);
			$arrPerm = getPermisos($perm);

			$params = array('vista' => ucfirst($args['page']), 'permisos' => $arrPerm, 'todo' => $this, 'perm' => $perm);
			return $this->renderer->render($response, 'com_'.$args['page'].'.phtml', $params);
		});

		$app->get('/produccion/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'produccion/' ".$args['page']);

			$modulo = 7;
			$_SESSION['alm'] = $modulo;
			$user = $_SESSION['usuario']->id_usuario;
			$perm = $this->model->usuario->getAcciones($user, $modulo);
			$arrPerm = getPermisos($perm);

			$params = array('vista' => ucfirst($args['page']), 'permisos' => $arrPerm, 'todo' => $this, 'perm' => $perm);
			return $this->renderer->render($response, 'prod_'.$args['page'].'.phtml', $params);
		});

		$app->get('/tiendita/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'tiendita/' ".$args['page']);

			$modulo = 8;
			$_SESSION['alm'] = $modulo;
			$user = $_SESSION['usuario']->id_usuario;
			$perm = $this->model->usuario->getAcciones($user, $modulo);
			$arrPerm = getPermisos($perm);

			$params = array('vista' => ucfirst($args['page']), 'permisos' => $arrPerm, 'todo' => $this, 'perm' => $perm);
			return $this->renderer->render($response, 'tien_'.$args['page'].'.phtml', $params);
		});

		$app->get('/reportes/{alm}/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'reportes/' ".$args['alm'].'/ '.$args['page']);

			$arrMod = array('almacen' => 5, 'comunidad' => 6, 'produccion' => 7, 'tiendita' => 8);
			$modulo = $arrMod[$args['alm']];
			$_SESSION['alm'] = $modulo;
			$user = $_SESSION['usuario']->id_usuario;
			$perm = $this->model->usuario->getAcciones($user, $modulo);

			$ruta = $request->getUri()->getPath();
			$existe = array_search($ruta, array_column($perm, 'url'));

			if($existe === false){
				return $this->view->render($response, '403.phtml', []);
			}

			$params = array('vista' => 'Reporte '.str_replace('_',' ',ucfirst($args['page'])).' '.ucfirst($args['alm']), 'todo' => $this, 'perm' => $perm);
			return $this->renderer->render($response, 'rpt'.substr($args['alm'],0,1).'_'.$args['page'].'.phtml', $params);
		});

		$app->get('/codigo_barras/[{page}]', function(Request $request, Response $response, array $args) {
			$this->logger->info("Slim-Skeleton 'codigo_barras/' ".$args['page']);
			return $this->renderer->render($response, 'codigo_barras.phtml');
		});
	};

	function getPermisos($arrPerm) {
		$res = array();
		foreach($arrPerm as $perm) {
			$res[] = $perm->id;
		}
		return $res;
	}

	function hasPermission($mod) {
		$hasPerm = false;
		foreach($_SESSION['permisos'] as $modulo) {
			if($modulo->id == $mod) {
				$hasPerm = true;
				break;
			}
		}
		return $hasPerm;
	}
?>