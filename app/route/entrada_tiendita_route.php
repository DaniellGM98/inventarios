<?php
	use App\Lib\Response;

	$app->group('/entrada_tiendita/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de entrada_tiendita');
		});

        // Obtener todas las entrada_tiendita
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->entrada_tiendita->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Obtener entradas_tiendita por cajero entre fechas
		$this->get('getByDate/{inicio}/{fin}/{cajero}', function($request, $response, $arguments) {
			return $response->withJson($this->model->entrada_tiendita->getByDate($arguments['inicio'], $arguments['fin'], $arguments['cajero']));
		});

        // Cancelar entrada_tiendita
		$this->put('cancel/{entrada}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$infoEntrada = $this->model->entrada_tiendita->get($arguments['entrada'])->result->fecha;
				$fk_cajero = $this->model->entrada_tiendita->get($arguments['entrada'])->result->fk_cajero;
				$fechaEntrada = substr($infoEntrada, 0,10);
				$detEntrad = $this->model->det_entrada_tiendita->getByEntrada($arguments['entrada']);
				$count=count($detEntrad);
				for($x=0;$x<$count;$x++){
					$cant = $detEntrad[$x]->cantidad;
					$prod = $detEntrad[$x]->fk_producto;
					$checkStock = $this->model->producto->get($prod);
					if($cant <= $checkStock->result->stock_tiendita) {
						$stocktienditarest = $this->model->producto->stockTienditaRest($cant, $prod);
						if(!$stocktienditarest->response) {
							$stocktienditarest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($stocktienditarest); 
						}
						$entradasrest = $this->model->kardex_tiendita->entradasRest($cant, $prod, $fechaEntrada, $fk_cajero);
						if(!$entradasrest->response) {
							$entradasrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($entradasrest); 
						}
						$this->model->kardex_tiendita->arreglaKardex($prod, $fechaEntrada, $fk_cajero);
					}else{
						$this->response = new Response();
						$this->response->state=$this->model->transaction->regresaTransaccion();
						return $response->withJson($this->response->setResponse(false, 'No se puede cancelar la entrada, stock insuficiente'));
					}
				}
				$CancelaEntrada = $this->model->entrada_tiendita->del($arguments['entrada']);
				if($CancelaEntrada){
					$seg_log = $this->model->seg_log->add('Cancelar entrada_tiendita',$arguments['entrada'], 'entrada_tiendita'); 
							if(!$seg_log->response) {
								$seg_log->state=$this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
				}
				$CancelaEntrada->state=$this->model->transaction->confirmaTransaccion();
				return $response->withJson($CancelaEntrada);
			}
		});

        // Agregar entrada_tiendita
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$fecha = date("Y-m-d");
			$_peso_total = $parsedBody['peso_total'];
			$_cajero = $parsedBody['cajero'];
			$data = [
				'peso_total'=>$_peso_total,
				'fk_cajero'=>$_cajero
			];
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$resEntrada = $this->model->entrada_tiendita->add($data);
				$_fk_entrada = $resEntrada->result;
				if($_fk_entrada){
					$seg_log = $this->model->seg_log->add('Agregar entrada_tiendita',$_fk_entrada, 'entrada_tiendita'); 
							if(!$seg_log->response) {
								$this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
				}
				$detalles = $parsedBody['detalles'];
				$cont=1;
				foreach($detalles as $detalle) {
					$_fk_producto = $detalle['id_producto'];
					if(intval($_fk_producto) <= 0){
						$respuesta=new Response();
						$respuesta->state=$this->model->transaction->regresaTransaccion(); 
						$respuesta->SetResponse(false,"Producto $cont incorrecto");
						return $response->withJson($respuesta);
					}
					$cont++;
					$_cantidad = $detalle['cantidad'];
					$_peso = $detalle['peso'];
					$stock = $this->model->kardex_tiendita->kardexByDate($fecha, $_fk_producto, $_cajero);
					if($stock=='0'){
						$_inicial = '0';
						$_final = '0';
						$kardexinicial = $this->model->kardex_tiendita->kardexInicial($fecha, $_fk_producto, $_cajero);
						if($kardexinicial!='0'){
							$_inicial = $kardexinicial;
						}
						$kardexfinal = $this->model->kardex_tiendita->kardexFinal($fecha, $_fk_producto, $_cajero);
						if($kardexfinal!='0'){
							$_final = $kardexfinal;
						}
						if($_final == '0') { $_final = $_inicial; }
						$dataKardex = [
							'fk_producto'=>$_fk_producto,
							'inicial'=>$_inicial,
							'entradas'=>'0',
							'salidas'=>'0',
							'final'=>$_final,
							'fk_cajero'=>$_cajero
						];
						$new_kardex = $this->model->kardex_tiendita->add($dataKardex);
						if(!$new_kardex->response) {
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($new_kardex); 
						}
					}
					$edit_producto = $this->model->producto->stockTienditaSum($_cantidad, $_fk_producto);
					if($edit_producto->response){
						$edit_kardex = $this->model->kardex_tiendita->entradasSum($_cantidad, $_fk_producto, $fecha, $_cajero);
						if($edit_kardex->response){
							$edit_next_kardex = $this->model->kardex_tiendita->inicialfinalSum($_cantidad, $_fk_producto, $fecha, $_cajero);
							if($edit_next_kardex->response){
								$this->model->kardex_tiendita->arreglaKardex($_fk_producto, $fecha, $_cajero);
								$dataDetalleKardex = [
									'fk_entrada'=>$_fk_entrada,
									'fk_producto'=>$_fk_producto, 
									'cantidad'=>$_cantidad, 
									'peso'=>$_peso
								];
								$add_detalle = $this->model->det_entrada_tiendita->add($dataDetalleKardex);
							}else{
								$this->model->transaction->regresaTransaccion();
								return $response->withJson($edit_next_kardex); 
							}
						}else{
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($edit_kardex);
						}
					}else{
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($edit_producto);
					}
				}
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($resEntrada);
			}
		});

		// Editar entrada_tiendita
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$_peso_total = $parsedBody['peso_total'];
			$_cajero = $parsedBody['cajero'];
			$dataEntrada = [ 
				'peso_total'=>$_peso_total,
				'fk_cajero'=>$_cajero
			];
			$entrada = $this->model->entrada_tiendita->edit($dataEntrada, $entrada_id);
			$detalles = $parsedBody['detalles'];
			foreach($detalles as $detalle) {
				$dataDetEntrada = [
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso']
				];
                $entradaa = $this->model->det_entrada_tiendita->editByEntrada($dataDetEntrada, $entrada_id, $detalle['id_producto']);
				if($entradaa->response){
					$seg_log = $this->model->seg_log->add('Actualización información entrada_tiendita', $entrada_id, 'entrada_tiendita', 1);
					if(!$seg_log->response) {
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
					}
					$entrada->SetResponse(true, 'Entrada_tiendita actualizada');
				}else{
					$entrada->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($entradaa); 
				} 
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($entradaa);
		});

		// Editar estado de entrada_tiendita
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$dataEntrada = [
				'estado'=>$parsedBody['estado']
			];
			$entrada = $this->model->entrada_tiendita->editEstado($dataEntrada, $entrada_id);
			if($entrada->response) {
				$seg_log = $this->model->seg_log->add('Actualización información entrada_tiendita', $entrada_id, 'entrada_tiendita', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$entrada->SetResponse(true, 'Entrada_tiendita actualizada');
			}else{
				$this->model->transaction->regresaTransaccion(); 
				return $response->withJson($entrada); 
			} 
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($entrada);
		});

		// Eliminar entrada_tiendita
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$entrada_id = $arguments['id'];
			$del_entrada = $this->model->entrada_tiendita->del($entrada_id); 
			if($del_entrada->response) {	
				$seg_log = $this->model->seg_log->add('Baja de entrada_tiendita', $entrada_id, 'entrada_tiendita'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			} else { $del_entrada->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_entrada); 
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_entrada);
		});

		// Obtener entrada_tiendita por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->entrada_tiendita->get($arguments['id']);
			$resultado->detalles = $this->model->det_entrada_tiendita->getByEntrada($arguments['id']);
			return $response->withJson($resultado);
		});

		// Agregar detalle de entrada_tiendita
		$this->post('addDetalle/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $request->getParsedBody();
            $dataDetalles = [
				'fk_entrada'=>$parsedBody['fk_entrada'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso']
			];
			$add=$this->model->det_entrada_tiendita->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle entrada_tiendita',$parsedBody['fk_entrada'], 'det_entrada_tiendita'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($add);
		});

		// Obtener det_entrada_tiendita por entrada_tiendita
		$this->get('getDetalles/{entrada}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_tiendita->get($arguments['entrada']));
		});

		// Eliminar detalle entrada_tiendita
		$this->put('delDetalleEntrada/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$del_det_entrada = $this->model->det_entrada_tiendita->getById($arguments['id']);
				$cant = $del_det_entrada->cantidad;
				$fecha = substr($del_det_entrada->fecha,0,10);
				$entrada = $del_det_entrada->fk_entrada;
				$prod = $del_det_entrada->fk_producto;
				$_cajero = $del_det_entrada->cajero;
				$checkStock = $this->model->producto->get($prod);
				if($cant <= $checkStock->result->stock_tiendita) {
					$del_detentrada = $this->model->det_entrada_tiendita->del($arguments['id']);
					if($del_detentrada->response){
						$stocktienditarest = $this->model->producto->stockTienditaRest($cant, $prod);
						if(!$stocktienditarest->response) {
							$stocktienditarest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($stocktienditarest); 
						}
						$entradasrest = $this->model->kardex_tiendita->entradasRest($cant, $prod, $fecha, $_cajero);
						if(!$entradasrest->response) {
							$entradasrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($entradasrest); 
						}
						$this->model->kardex_tiendita->arreglaKardex($prod, $fecha, $_cajero);
						$PesoTotal = $this->model->det_entrada_tiendita->getPesoTotal($entrada)->peso_total;
						$data = [
							'peso_total'=>$PesoTotal
						];
						$edit = $this->model->entrada_tiendita->edit($data, $entrada);
						if($edit->response) {
							$seg_log = $this->model->seg_log->add('Baja de det_entrada_tiendita', $arguments['id'], 'det_entrada_tiendita'); 
							if(!$seg_log->response) {
								$seg_log->state = $this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
						}else{
							$edit->state=$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($edit); 
						}
					}else{
						$del_detentrada->state=$this->model->transaction->regresaTransaccion(); 
						return $response->withJson($del_detentrada); 
					}
				}else{
					$this->response = new Response();
					$this->response->state=$this->model->transaction->regresaTransaccion();
					return $response->withJson($this->response->setResponse(false, 'No se puede cancelar la entrada, stock insuficiente'));
				}
				$del_detentrada->result = null;
				$del_detentrada->state=$this->model->transaction->confirmaTransaccion();
				return $response->withJson($del_detentrada);
			}
		});

		// Obtener reporte por proveedor entre fechas
		$this->get('rptProv/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_tiendita->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		});

		// Obtener reporte por clave entre fechas
		$this->get('rptCve/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_tiendita->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']));
		});

		// Obtener reporte por clave entre fechas (pdf)
		$this->get('rptCve/print/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada_tiendita->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']);
			$titulo = "CONCENTRADO DE ENTRADAS [Tiendita]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['clave'] = $arguments['cve'];
			return $this->view->render($response, 'rptTieEntradas.php', $params);
		});

		// Obtener todas las det_entrada_tiendita
		$this->get('getAllDetalles/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->det_entrada_tiendita->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->entrada_tiendita->findBy($args['f'], $args['v'])));			
		});
	});
?>