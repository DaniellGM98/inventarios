<?php
	use App\Lib\Response;

	$app->group('/kardex/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de kardex');
		});

        // Obtener kardex entre fechas
		$this->get('{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->kardex->getKardex($arguments['producto'],$arguments['inicio'], $arguments['fin']));
		});

		// Obtener kardex entre fechas (pdf)
		$this->get('print/{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			$kardex = $this->model->kardex->getKardex($arguments['producto'],$arguments['inicio'], $arguments['fin']);
			$titulo = "KARDEX DE PRODUCTO";
			$params = array('vista' => $titulo);
        	$params['registros'] = $kardex;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['producto'] = $arguments['producto'];
			$params['productostr'] = $this->model->producto->get($arguments['producto'])->result->descripcion;
			return $this->view->render($response, 'rptKardex.php', $params);
		});

        // Obtener kardex comunidad entre fechas
		$this->get('comunidad/{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->kardex_comunidad->getComunidad($arguments['producto'],$arguments['inicio'], $arguments['fin']));
		});

		// Obtener kardex comunidad entre fechas (pdf)
		$this->get('comunidad/print/{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			$kardex = $this->model->kardex_comunidad->getComunidad($arguments['producto'],$arguments['inicio'], $arguments['fin']);
			$titulo = "KARDEX DE PRODUCTO [Comunidad]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $kardex;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['producto'] = $arguments['producto'];
			$params['productostr'] = $this->model->producto->get($arguments['producto'])->result->descripcion;
			return $this->view->render($response, 'rptKardexCom.php', $params);
		});

        // Obtener kardex tiendita entre fechas por cajero
		$this->get('tiendita/{producto}/{inicio}/{fin}/{cajero}', function($request, $response, $arguments) {
			return $response->withJson($this->model->kardex_tiendita->getTiendita($arguments['producto'],$arguments['inicio'], $arguments['fin'], $arguments['cajero']));
		});

		// Obtener kardex tiendita entre fechas por cajero (pdf)
		$this->get('tiendita/print/{producto}/{inicio}/{fin}/{cajero}', function($request, $response, $arguments) {
			$kardex = $this->model->kardex_tiendita->getTiendita($arguments['producto'],$arguments['inicio'], $arguments['fin'], $arguments['cajero']);
			$titulo = "KARDEX DE PRODUCTO [Tiendita]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $kardex;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['producto'] = $arguments['producto'];
			$params['productostr'] = $this->model->producto->get($arguments['producto'])->result->descripcion;
			$params['cajero'] = $arguments['cajero'];
			$cajero = $this->model->usuario->get($arguments['cajero'])->result;
			$params['cajerostr'] = ''.$cajero->apellidos.' '.$cajero->nombre;
			//return $response->withJson($params);
			return $this->view->render($response, 'rptKardexTiendi.php', $params);
		});

        // Obtener kardex produccion entre fechas
		$this->get('produccion/{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->kardex_produccion->getProduccion($arguments['producto'],$arguments['inicio'], $arguments['fin']));
		});

		// Obtener kardex produccion entre fechas (pdf)
		$this->get('produccion/print/{producto}/{inicio}/{fin}', function($request, $response, $arguments) {
			$kardex = $this->model->kardex_produccion->getProduccion($arguments['producto'],$arguments['inicio'], $arguments['fin']);
			$titulo = "KARDEX DE PRODUCTO [Produccion]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $kardex;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['producto'] = $arguments['producto'];
			$params['productostr'] = $this->model->producto->get($arguments['producto'])->result->descripcion;
			return $this->view->render($response, 'rptKardexProd.php', $params);
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->entrada_comunidad->findBy($args['f'], $args['v'])));			
		});
	});
?>