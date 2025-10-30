<?php
	use App\Lib\Response;

	$app->group('/salida_produccion/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de salida_produccion');			
		});

        // Obtener todas las salida_produccion
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->salida_produccion->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Obtener salida_produccion por fecha
		$this->get('getByDate/{inicio}/{final}', function($request, $response, $arguments) {
			return $response->withJson($this->model->salida_produccion->getByDate($arguments['inicio'], $arguments['final']));
		});

        // Cancelar salida_produccion
		$this->put('cancel/{salida}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$infoSalida = $this->model->salida_produccion->get($arguments['salida'])->result->fecha;
				$fechaSalida = substr($infoSalida, 0,10);
				$detSalid = $this->model->det_salida_produccion->getBySalida($arguments['salida'])->result;
				$count=count($detSalid);
				for($x=0;$x<$count;$x++){
					$cant = $detSalid[$x]->cantidad;
					$prod = $detSalid[$x]->fk_producto;
					$stockproduccionsum = $this->model->producto->stockProduccionSum($cant, $prod);
					if(!$stockproduccionsum->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($stockproduccionsum); 
					}
					$salidasrest = $this->model->kardex_produccion->salidasRest($cant, $prod, $fechaSalida);
					if(!$salidasrest->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($salidasrest); 
					}
					$this->model->kardex_produccion->arreglaKardex($prod, $fechaSalida);
				}
				$CancelaSalida=$this->model->salida_produccion->del($arguments['salida']);
				if($CancelaSalida){
					$seg_log = $this->model->seg_log->add('Cancelar salida_produccion',$arguments['salida'], 'salida_produccion'); 
							if(!$seg_log->response) {
								$this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
				}
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($CancelaSalida);
			}
		});

        // Agregar salida_produccion
		$this->post('add/', function($request, $response, $arguments) {
			date_default_timezone_set('America/Mexico_City');
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            $fecha=date("Y-m-d");
            $_fk_cliente = $parsedBody['fk_cliente'];
            $_peso_total = $parsedBody['peso_total'];
            $_nota = $parsedBody['nota_salida'];
            $data = [
				'destino'=>$_fk_cliente,
				'peso_total'=>$_peso_total,
				'nota_salida'=>$_nota
			];
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$resSalida =  $this->model->salida_produccion->add($data);
				$_fk_salida = $resSalida->result;
				if($_fk_salida){
					$seg_log = $this->model->seg_log->add('Agregar salida_produccion',$_fk_salida, 'salida_produccion'); 
							if(!$seg_log->response) {
								$this->model->transaction->regresaTransaccion(); 
								return $response->withJson($seg_log);
							}
				}
				if(!$_fk_salida){
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($_fk_salida);
				}

				//--> entrada a comunidad si cliente es COMUNIDADES
				if($_fk_cliente=="COMUNIDADES"){    
					$data = [
						'peso_total'=>$_peso_total
					];
					$resEntrada = $this->model->entrada_comunidad->add($data);
					$_fk_entrada = $resEntrada->result;
					if($_fk_entrada){
						$seg_log = $this->model->seg_log->add('Agregar entrada_comunidad', $_fk_entrada, 'entrada_comunidad'); 
								if(!$seg_log->response) {
									$this->model->transaction->regresaTransaccion(); 
									return $response->withJson($seg_log);
								}
					}
				}
				//<-- entrada a comunidad si cliente es COMUNIDADES

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
					$produc = $this->model->producto->get($_fk_producto);
					if($produc->result->stock_produccion < $_cantidad){
						$this->model->transaction->regresaTransaccion();
						$produc->setResponse(false,'No hay suficiente stock para '.$produc->result->descripcion);
						unset($produc->result);
						return $response->withJson($produc);
					}
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
					$edit_producto = $this->model->producto->stockProduccionRest($_cantidad, $_fk_producto);
					if($edit_producto->response) {
						$edit_kardex = $this->model->kardex_produccion->salidasSum($_cantidad, $_fk_producto, $fecha);
						if($edit_kardex->response) {
							$dataDetEntr = [
								'fk_salida'=>$_fk_salida,
								'fk_producto'=>$_fk_producto,
								'cantidad'=>$_cantidad,
								'peso'=>$_peso
							];
							$add_detalle = $this->model->det_salida_produccion->add($dataDetEntr);
							if(!$add_detalle->response) {
								$this->model->transaction->regresaTransaccion();
								return $response->withJson($add_detalle); 
							}else{
								$edit_next_kardex = $this->model->kardex_produccion->inicialfinalRest($_cantidad, $_fk_producto, $fecha);
								if($edit_next_kardex->response) {
									$this->model->kardex_produccion->arreglaKardex($_fk_producto, $fecha);   
								}
							}
						}else{
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($edit_kardex);
						}
					}else{
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($edit_producto);
					}

					//--> detalles entrada a comunidad si cliente es COMUNIDADES
					if($_fk_cliente=="COMUNIDADES"){
						if(intval($_fk_producto) <= 0){
							$respuesta2 = new Response();
							$respuesta2 -> state = $this->model->transaction->regresaTransaccion(); 
							$respuesta2 -> SetResponse(false,"Producto $cont incorrecto");
							return $response->withJson($respuesta2);
						}
						$stock2 = $this->model->kardex_comunidad->kardexByDate($fecha, $_fk_producto);
						if($stock2->Total <= 0){
							$_inicial2 = '0';
							$_final2 = '0';
							$kardexinicial2 = $this->model->kardex_comunidad->kardexInicial($fecha, $_fk_producto);
							if($kardexinicial2!='0'){
								$_inicial2 = $kardexinicial2;
							}
							$kardexfinal2 = $this->model->kardex_comunidad->kardexFinal($fecha, $_fk_producto);
							if($kardexfinal2!='0'){
								$_final2 = $kardexfinal2;
							}
							if($_final2 == '0') { 
								$_final2 = $_inicial2; 
							}

							$dataKardex2 = [
								'fk_producto'=>$_fk_producto,
								'inicial'=>$_inicial2,
								'entradas'=>'0',
								'salidas'=>'0',
								'final'=>$_final2
							];
							$new_kardex2 = $this->model->kardex_comunidad->add($dataKardex2);
							if(!$new_kardex2->response) {
								$this->model->transaction->regresaTransaccion();
								return $response->withJson($_fk_producto); 
							}
						}
						$edit_producto2 = $this->model->producto->stockComunidadSum($_cantidad, $_fk_producto);
						if($edit_producto2->response){
							$edit_kardex2 = $this->model->kardex_comunidad->entradasSum($_cantidad, $_fk_producto, $fecha);
							if($edit_kardex2->response){
								$edit_next_kardex2 = $this->model->kardex_comunidad->inicialfinalSum($_cantidad, $_fk_producto, $fecha);
								if($edit_next_kardex2->response){
									$this->model->kardex_comunidad->arreglaKardex($_fk_producto, $fecha);
									$dataDetalleKardex2 = [
										'fk_entrada'=>$_fk_entrada,
										'fk_producto'=>$_fk_producto, 
										'cantidad'=>$_cantidad, 
										'peso'=>$_peso
									];
									$add_detalle = $this->model->det_entrada_comunidad->add($dataDetalleKardex2);
								}else{
									$this->model->transaction->regresaTransaccion();
									return $response->withJson($edit_next_kardex2); 
								}
							}else{
								$this->model->transaction->regresaTransaccion();
								return $response->withJson($edit_kardex2); 
							}
						}else{
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($edit_producto2); 
						}
					}
					//<-- detalles entrada a comunidad si cliente es COMUNIDADES
					
				}
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($resSalida);
			}
		});

        // Editar salida_produccion
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$salida_id = $arguments['id'];
            $_fk_cliente = $parsedBody['fk_cliente'];
            $_peso_total = $parsedBody['peso_total'];
			$_nota = $parsedBody['nota_salida'];            
            $dataSalida = [
				'destino'=>$_fk_cliente,
				'peso_total'=>$_peso_total,
				'nota_salida'=>$_nota
			];
            $salida = $this->model->salida_produccion->edit($dataSalida, $salida_id);
            $detalles = $parsedBody['detalles'];
            foreach($detalles as $detalle) {
				$dataDetSalida = [
					'fk_producto'=>$detalle['id_producto'],
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso']
				];
                $salida2 = $this->model->det_salida_produccion->editBySalida($dataDetSalida, $salida_id, $detalle['id_producto']);
				if(!$salida2->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($salida2);
				}
                if($salida->response) {
                    $seg_log = $this->model->seg_log->add('Actualización información salida_produccion', $salida_id, 'salida_produccion', 1);
                    if(!$seg_log->response) {
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                        return $response->withJson($seg_log);
                    }
                    $salida->SetResponse(true, 'Salida_produccion actualizada');
                }else{
                    $salida->state = $this->model->transaction->regresaTransaccion(); 
                    return $response->withJson($salida); 
                }
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($salida);
		});

        // Editar estado de salida_producion
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$salida_id = $arguments['id'];
			$dataSalida = [
				'estado'=>$parsedBody['estado']
			];
			$salida = $this->model->salida_produccion->edit($dataSalida, $salida_id);
			if($salida->response) {
				$seg_log = $this->model->seg_log->add('Actualización información salida_producion', $salida_id, 'salida_producion', 1);
				if(!$seg_log->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$salida->SetResponse(true, 'Salida_producion actualizada');
			}else{
				$this->model->transaction->regresaTransaccion(); 
				return $response->withJson($salida); 
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($salida);
		});

        // Obtener salida_produccion por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->salida_produccion->get($arguments['id']);
			$resultado->detalles = $this->model->det_salida_produccion->getBySalida($arguments['id'])->result;
			return $response->withJson($resultado);
		});

        // Eliminar salida_produccion
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$salida_id = $arguments['id'];
			$del_salida = $this->model->salida_produccion->del($salida_id);
			if($del_salida->response) {
				$seg_log = $this->model->seg_log->add('Baja de salida_produccion', $salida_id, 'salida_produccion'); 
				if(!$seg_log->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{ 
                $this->model->transaction->regresaTransaccion();
				return $response->withJson($del_salida);
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_salida);
		});

        // Buscar salida_produccion
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->salida_produccion->find($arguments['busqueda']));
		});

        // Agregar detalle de salida_produccion
		$this->post('addDetalle/', function($request, $response, $arguments) {
            $parsedBody = $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();
            $dataDetalles = [
				'fk_salida'=>$parsedBody['fk_salida'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso']
			];
			$add = $this->model->det_salida_produccion->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle salida_produccion',$parsedBody['fk_salida'], 'det_salida_produccion'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($add);
		});

        // Obtener det_salida_produccion por salida_produccion
		$this->get('getDetalles/{salida}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida_produccion->getBySalida($arguments['salida']));
		});

        // Eliminar salida_produccion
		$this->put('delDetalleSalida/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$del_det_salida = $this->model->det_salida_produccion->getById($arguments['id']);
				$cant = $del_det_salida->cantidad;
				$fecha = substr($del_det_salida->fecha,0,10);
				$salida = $del_det_salida->fk_salida;
				$prod = $del_det_salida->fk_producto;
				$del_detsalida = $this->model->det_salida_produccion->del($arguments['id']);
				if($del_detsalida->response){
					$stockproduccionsum = $this->model->producto->stockProduccionSum($cant, $prod);
					if(!$stockproduccionsum->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($stockproduccionsum); 
					}
					$salidasrest = $this->model->kardex_produccion->salidasRest($cant, $prod, $fecha);
					if(!$salidasrest->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($salidasrest); 
					}
					$this->model->kardex_produccion->arreglaKardex($prod, $fecha);
					$PesoTotal = $this->model->det_salida_produccion->getPesoTotal($salida)->peso_total;
					$data = [
						'peso_total'=>$PesoTotal
					];
					$edit = $this->model->salida_produccion->edit($data, $salida); 
					if($edit->response) {
						$seg_log = $this->model->seg_log->add('Baja de det_salida_produccion', $arguments['id'], 'det_salida_produccion'); 
						if(!$seg_log->response) {
							$seg_log->state = $this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
					}else{
						$this->model->transaction->regresaTransaccion(); 
						return $response->withJson($edit); 
					}
				}else{
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($del_detsalida); 
				}
				$del_detsalida->result = null;            
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($del_detsalida);
			}
		});

        // Obtener reporte entre fechas
		$this->get('rpt/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida_produccion->getRpt($arguments['inicio'], $arguments['fin']));
		});

		// Obtener reporte entre fechas (pdf)
		$this->get('rpt/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$salidas = $this->model->det_salida_produccion->getRpt($arguments['inicio'], $arguments['fin']);
			$titulo = "REPORTE DE SALIDAS [Produccion]";
			$params = array('vista' => $titulo);
        	$params['registros'] = $salidas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			return $this->view->render($response, 'rptProdSalidas.php', $params);
		});

		// Obtener reporte por proveedor entre fechas
		$this->get('rptProv/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida_produccion->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		});

		// Obtener reporte por cliente entre fechas
		$this->get('rptCli/{inicio}/{fin}/{cliente}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida_produccion->getRptCli($arguments['inicio'], $arguments['fin'], $arguments['cliente']));
		});

        // Obtener todas las det_salida_produccion
		$this->get('getAllDetalles/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->det_salida_produccion->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->salida_produccion->findBy($args['f'], $args['v'])));			
		});
	});
?>