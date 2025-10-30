<?php
	//use Slim\App;

	//return function (App $app) {
		$container = $app->getContainer();

		// view renderer
		$container['renderer'] = function ($c) {
			$settings = $c->get('settings')['renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// rpt renderer
		$container['rpt_renderer'] = function ($c) {
			$settings = $c->get('settings')['rpt_renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// monolog
		$container['logger'] = function ($c) {
			$settings = $c->get('settings')['logger'];
			$logger = new \Monolog\Logger($settings['name']);
			$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
			$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
			return $logger;
		};

		// Database
			$container['db'] = function($c) {
				$connectionString = $c->get('settings')['connectionString'];
				
				$pdo = new PDO($connectionString['dns'], $connectionString['user'], $connectionString['pass']);

				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

				return new \Envms\FluentPDO\Query($pdo);
				
			};
			
			// Register component view 
			$container['view'] = function ($container) {
				return new \Slim\Views\PhpRenderer('../templates/');
			};

			// Models
			$container['model'] = function($c) {
				return (object)[

					'usuario' => new App\Model\UsuarioModel($c->db),
					'seg_sesion' => new App\Model\SegSesionModel($c->db),
					'seg_log' => new App\Model\SegLogModel($c->db),
					'seg_permiso' => new App\Model\SegPermisoModel($c->db), //
					'seg_modulo' => new App\Model\SegModuloModel($c->db), //
					'seg_accion' => new App\Model\SegAccionModel($c->db), //
					'seg_pwd_recover' => new App\Model\SegPwdRecoverModel($c->db), //
					'proveedor' => new App\Model\ProveedorModel($c->db),
					'transaction' => new App\Lib\Transaction($c->db),
					'producto' => new App\Model\ProductoModel($c->db),
					'producto_paquete' => new App\Model\ProductoPaqueteModel($c->db),
					'cliente' => new App\Model\ClienteModel($c->db),
					'entrada' => new App\Model\EntradaModel($c->db),
					'kardex' => new App\Model\KardexModel($c->db),
					'det_entrada' => new App\Model\DetentradaModel($c->db),
					'comunidad' => new App\Model\ComunidadModel($c->db),
					'salida' => new App\Model\SalidaModel($c->db),
					'det_salida' => new App\Model\DetsalidaModel($c->db),
					'entrada_comunidad' => new App\Model\EntradaComunidadModel($c->db),
					'det_entrada_comunidad' => new App\Model\DetentradaComuidadModel($c->db),
					'kardex_comunidad' => new App\Model\KardexComunidadModel($c->db),
					'salida_comunidad' => new App\Model\SalidaComunidadModel($c->db),
					'det_salida_comunidad' => new App\Model\DetsalidaComunidadModel($c->db),
					'entrada_produccion' => new App\Model\EntradaProduccionModel($c->db),
					'det_entrada_produccion' => new App\Model\DetentradaProduccionModel($c->db),
					'kardex_produccion' => new App\Model\KardexProduccionModel($c->db),
					'seg_permiso' => new App\Model\SegPermisoModel($c->db),
					'seg_modulo' => new App\Model\SegModuloModel($c->db),
					'seg_accion' => new App\Model\SegAccionModel($c->db),
					'salida_produccion' => new App\Model\SalidaProduccionModel($c->db),
					'det_salida_produccion' => new App\Model\DetsalidaProduccionModel($c->db),
					'entrada_tiendita' => new App\Model\EntradaTienditaModel($c->db),
					'det_entrada_tiendita' => new App\Model\DetentradaTienditaModel($c->db),
					'kardex_tiendita' => new App\Model\KardexTienditaModel($c->db),
					'venta' => new App\Model\VentaModel($c->db),
					'det_venta' => new App\Model\DetVentaModel($c->db),
					'transferencia' => new App\Model\TransferenciaModel($c->db),
				];
			};
	//};
?>