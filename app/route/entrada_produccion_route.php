<?php
	use App\Lib\Response;

	$app->group('/entrada_produccion/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de entrada_produccion');
		});

        // Obtener todas las entrada_produccion
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->entrada_produccion->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Obtener entrada_produccion entre fechas
		$this->get('getByDate/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->entrada_produccion->getByDate($arguments['inicio'], $arguments['fin']));
		});

        // Cancelar entrada_produccion
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
				$infoEntrada = $this->model->entrada_produccion->get($arguments['entrada'])->result->fecha;
				$fechaEntrada = substr($infoEntrada, 0,10);
				$detEntrad = $this->model->det_entrada_produccion->getByEntrada($arguments['entrada']);
				$count=count($detEntrad);
				for($x=0;$x<$count;$x++){
					$cant = $detEntrad[$x]->cantidad;
					$prod = $detEntrad[$x]->fk_producto;
					$checkStock = $this->model->producto->get($prod);
					if($cant <= $checkStock->result->stock_produccion) {
						$stockproduccionrest = $this->model->producto->stockProduccionRest($cant, $prod);
						if(!$stockproduccionrest->response){
							$stockproduccionrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($stockproduccionrest); 
						}
						$entradasrest = $this->model->kardex_produccion->entradasRest($cant, $prod, $fechaEntrada);
						if(!$entradasrest){
							$entradasrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($entradasrest); 
						}
						$this->model->kardex_produccion->arreglaKardex($prod, $fechaEntrada);
					}else{
						$this->response = new Response();
						$this->response->state=$this->model->transaction->regresaTransaccion();
						return $response->withJson($this->response->setResponse(false, 'No se puede cancelar la entrada, stock insuficiente'));
					}
				}
				$CancelaEntrada = $this->model->entrada_produccion->del($arguments['entrada']); 
				if($CancelaEntrada){
					$seg_log = $this->model->seg_log->add('Cancelar entrada_produccion', $arguments['entrada'], 'entrada_produccion'); 
							if(!$seg_log->response) {
								$seg_log->state=$this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
				}
				$CancelaEntrada->state=$this->model->transaction->confirmaTransaccion();
				return $response->withJson($CancelaEntrada);
			}
		});

        // Agregar entrada_produccion
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            $fecha = date("Y-m-d");
            $_peso_total = $parsedBody['peso_total'];
            $data = [
				'peso_total'=>$_peso_total
			];
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$resEntrada = $this->model->entrada_produccion->add($data);
				$_fk_entrada = $resEntrada->result;
				if($_fk_entrada){
					$seg_log = $this->model->seg_log->add('Agregar entrada_produccion', $_fk_entrada, 'entrada_produccion'); 
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
					$stock = $this->model->kardex_produccion->kardexByDate($fecha, $_fk_producto);
					if($stock=='0'){
						$_inicial = '0';
						$_final = '0';
						$kardexinicial = $this->model->kardex_produccion->kardexInicial($fecha, $_fk_producto);
						if($kardexinicial!='0'){
							$_inicial = $kardexinicial;
						}
						$kardexfinal = $this->model->kardex_produccion->kardexFinal($fecha, $_fk_producto);
						if($kardexfinal!='0'){
							$_final = $kardexfinal;
						}
						if($_final == '0') { $_final = $_inicial; }
						$dataKardex = [
							'fk_producto'=>$_fk_producto,
							'inicial'=>$_inicial,
							'entradas'=>'0',
							'salidas'=>'0',
							'final'=>$_final
						];
						$new_kardex = $this->model->kardex_produccion->add($dataKardex);
						if(!$new_kardex->response) {
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($new_kardex); 
						}
					}
					$edit_producto = $this->model->producto->stockProduccionSum($_cantidad, $_fk_producto);
					if($edit_producto->response){
						$edit_kardex = $this->model->kardex_produccion->entradasSum($_cantidad, $_fk_producto, $fecha);
						if($edit_kardex->response){
							$edit_next_kardex = $this->model->kardex_produccion->inicialfinalSum($_cantidad, $_fk_producto, $fecha);
							if($edit_next_kardex->response){
								$this->model->kardex_produccion->arreglaKardex($_fk_producto, $fecha);
								$dataDetalleKardex = [
									'fk_entrada'=>$_fk_entrada,
									'fk_producto'=>$_fk_producto, 
									'cantidad'=>$_cantidad, 
									'peso'=>$_peso
								];
								$add_detalle = $this->model->det_entrada_produccion->add($dataDetalleKardex);
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

        // Editar entrada_produccion
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$dataEntrada = [ 
				'peso_total'=>$parsedBody['peso_total']
			];
			$entrada = $this->model->entrada_produccion->edit($dataEntrada, $entrada_id);
            $detalles = $parsedBody['detalles'];
            foreach($detalles as $detalle) {
                $dataDetEntrada = [
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso']
				];
                $entradaa = $this->model->det_entrada_produccion->editByEntrada($dataDetEntrada, $entrada_id, $detalle['id_producto']);
                if($entradaa->response){
                    $seg_log = $this->model->seg_log->add('Actualización información entrada_produccion', $entrada_id, 'entrada_produccion', 1);
                    if(!$seg_log->response) {
                        $seg_log->state = $this->model->transaction->regresaTransaccion(); 
                        return $response->withJson($seg_log);
                    }
                    $entrada->SetResponse(true, 'Entrada_produccion actualizada');
                }else{
                    $entrada->state = $this->model->transaction->regresaTransaccion(); 
                    return $response->withJson($entradaa); 
                } 
            }
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($entrada);
		});

        // Editar estado de entrada_produccion
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$dataEntrada = [
				'estado'=>$parsedBody['estado']
			];
			$entrada = $this->model->entrada_produccion->editEstado($dataEntrada, $entrada_id);
			if($entrada->response) {
				$seg_log = $this->model->seg_log->add('Actualización información entrada_produccion', $entrada_id, 'entrada_produccion', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$entrada->SetResponse(true, 'Entrada_produccion actualizada');
			}else{
				$this->model->transaction->regresaTransaccion(); 
				return $response->withJson($entrada); 
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($entrada);
		});

        // Eliminar entrada_produccion
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$entrada_id = $arguments['id'];
			$del_entrada = $this->model->entrada_produccion->del($entrada_id); 
			if($del_entrada->response) {	
				$seg_log = $this->model->seg_log->add('Baja de entrada_produccion', $entrada_id, 'entrada_produccion'); 
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

        // Obtener entrada_produccion por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->entrada_produccion->get($arguments['id']);
			$resultado->detalles = $this->model->det_entrada_produccion->getByEntrada($arguments['id']);
			return $response->withJson($resultado);
		});

        // Agregar detalle de entrada_produccion
		$this->post('addDetalle/', function($request, $response, $arguments) {
            $parsedBody = $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();
            $dataDetalles = [
				'fk_entrada'=>$parsedBody['fk_entrada'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso']
			];
			$add = $this->model->det_entrada_produccion->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle entrada_produccion',$parsedBody['fk_entrada'], 'det_entrada_produccion'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($add);
		});

        // Obtener det_entrada_produccion por entrada_produccion
		$this->get('getDetalles/{entrada}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_produccion->get($arguments['entrada']));
		});

		// Eliminar entrada_produccion
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
				$del_det_entrada = $this->model->det_entrada_produccion->getById($arguments['id']);
				$cant = $del_det_entrada->cantidad;
				$fecha = substr($del_det_entrada->fecha,0,10);
				$entrada = $del_det_entrada->fk_entrada;
				$prod = $del_det_entrada->fk_producto;
				$checkStock = $this->model->producto->get($prod);
				if($cant <= $checkStock->result->stock_produccion) {
					$del_detentrada = $this->model->det_entrada_produccion->del($arguments['id']);
					if($del_detentrada->response){
						$stockproduccionrest = $this->model->producto->stockProduccionRest($cant, $prod);
						if(!$stockproduccionrest->response){
							$stockproduccionrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($stockproduccionrest); 
						}
						$entradasrest = $this->model->kardex_produccion->entradasRest($cant, $prod, $fecha);
						if(!$entradasrest){
							$entradasrest->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($entradasrest); 
						}
						$this->model->kardex_produccion->arreglaKardex($prod, $fecha);
						$PesoTotal = $this->model->det_entrada_produccion->getPesoTotal($entrada)->peso_total;
						$data = [
							'peso_total'=>$PesoTotal
						];
						$edit = $this->model->entrada_produccion->edit($data, $entrada);
						if($edit->response) {
							$seg_log = $this->model->seg_log->add('Baja de det_entrada_produccion', $arguments['id'], 'det_entrada_produccion'); 
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

		/* // Obtener reporte por proveedor entre fechas
		$this->get('rpt/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_produccion->getRpt($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		}); */

		// Obtener reporte por clave entre fechas (pdf)
		$this->get('rpt/print/{inicio}/{fin}/{cve_prov}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada->getRpt($arguments['inicio'], $arguments['fin'], $arguments['cve_prov']);
			$titulo = "CONCENTRADO DE ENTRADAS";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['cveprov'] = $arguments['cve_prov'];
			$params['finicio'] = $arguments['inicio'];
			$params['ffin'] = $arguments['fin'];
			return $this->view->render($response, 'rptEntradas.php', $params);
		});

		// Obtener reporte por clave entre fechas
		$this->get('rptCve/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_produccion->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']));
		});

		// Obtener reporte por clave entre fechas (pdf)
		$this->get('rptCve/print/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada_produccion->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']);
			$titulo = "CONCENTRADO DE ENTRADAS [Produccion]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['clave'] = $arguments['cve'];
			return $this->view->render($response, 'rptProdEntradas.php', $params);
		});

        // Obtener todas las det_entrada_produccion
		$this->get('getAllDetalles/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->det_entrada_produccion->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->entrada_produccion->findBy($args['f'], $args['v'])));			
		});
	});
?>