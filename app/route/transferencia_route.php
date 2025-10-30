<?php
	use App\Lib\Response;

	$app->group('/transferencia/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de transferencia');
		});

        // Agregar transferencia
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$_fecha = date("Y-m-d");
			$_origen =  $parsedBody['fk_origen'];
            $_destino =  $parsedBody['fk_destino'];
            $_producto =  $parsedBody['fk_producto'];
            $_cantidad =  $parsedBody['cantidad'];
            // $_usuario =  $parsedBody['fk_usuario'];
			$stockCaja = $this->model->producto->getStock($_producto, $_origen)->final;
			if($stockCaja < $_cantidad){
				$this->model->transaction->regresaTransaccion();
				$this->response->setResponse(false,'No hay suficiente stock');
				return $response->withJson($this->response);
			}
			$data = [
				'fk_origen'=>$_origen,
                'fk_destino'=>$_destino,
                'fk_producto'=>$_producto,
                'cantidad'=>$_cantidad,
                'fk_usuario'=> $_SESSION['usuario']->id_usuario
			];
			$resulttransfer = $this->model->transferencia->add($data);
            $_transferencia_id = $resulttransfer->result;
			if($resulttransfer->response){
				$seg_log = $this->model->seg_log->add('Agregar transferencia',$_transferencia_id, 'transferencia_tiendita'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$cajeros = array( 
				array(
				'cajero'=>$_origen, 
				'entradas'=>0, 
				'salidas'=>$_cantidad), 
				array(
					'cajero'=>$_destino, 
					'entradas'=>$_cantidad, 
					'salidas'=>0) 
				);
			foreach($cajeros as $cajero) {
				$_cajero = $cajero['cajero'];
				$_entradas = $cajero['entradas'];
				$_salidas = $cajero['salidas'];
				$stock = $this->model->kardex_tiendita->kardexByDate($_fecha, $_producto, $_cajero);
				if($stock=='0'){
					$_inicial = '0';
					$_final = '0';
					$kardexinicial = $this->model->kardex_tiendita->kardexInicial($_fecha, $_producto, $_cajero);
					if($kardexinicial!='0'){
						$_inicial = $kardexinicial;
					}
					$kardexfinal = $this->model->kardex_tiendita->kardexFinal($_fecha, $_producto, $_cajero);
					if($kardexfinal!='0'){
						$_final = $kardexfinal;
					}
					if($_final == '0') { $_final = $_inicial; }
					$dataKardex = [
						'fk_producto'=>$_producto,
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
				$edit_kardex = $this->model->kardex_tiendita->entradasSalidasFinal($_entradas, $_salidas, $_producto, $_fecha, $_cajero);
				if($edit_kardex->response) {
					$this->model->kardex_tiendita->arreglaKardex($_producto, $_fecha, $_cajero);
					$edit_next_kardex = $this->model->kardex_tiendita->entradasSalidasFinalM($_entradas, strval($_salidas), $_producto, $_fecha, $_cajero);
				}else{
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($edit_kardex);
				}
			}
            $this->model->transaction->confirmaTransaccion();
			return $response->withJson($resulttransfer);
		});

		// Obtener transferencias entre fechas
		$this->get('getByDate/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->transferencia->getByDate($arguments['inicio'], $arguments['fin']));
		});

		// Obtener transferencias entre fechas (pdf)
		$this->get('rptTransferencias/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->transferencia->getByDate($arguments['inicio'], $arguments['fin']);
			$transferencias = $resultado;
			$titulo = "REPORTE DE TRANSFERENCIAS";
			$params = array('vista' => $titulo);
        	$params['registros'] = $transferencias;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			return $this->view->render($response, 'rptTransferencias.php', $params);			
		});

		// Obtener transferencia por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->transferencia->get($arguments['id']));
		});

		// Cancelar transferencia
		$this->put('cancel/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->response = new Response();
			$this->model->transaction->iniciaTransaccion();
			$reg = $this->model->transferencia->get($arguments['id'])->result;
				$_origen = $reg->fk_origen;
				$_destino = $reg->fk_destino;
				$_producto = $reg->fk_producto;
				$_cantidad = $reg->cantidad;
				$_fecha = substr($reg->fecha, 0, 10);
			$stockCaja = $this->model->producto->getStock($_producto, $_destino)->final;
			if($stockCaja < $_cantidad){
				$this->model->transaction->regresaTransaccion();
				$this->response->setResponse(false,'No hay suficiente stock');
				return $response->withJson($this->response);
			}
			$CancelaTransferencia = $this->model->transferencia->del($arguments['id']);
			if($CancelaTransferencia->response){
				$seg_log = $this->model->seg_log->add('Cancelar transferencia',$arguments['id'], 'transferencia_tiendita'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
				$this->model->kardex_tiendita->arreglaKardex($_producto, $_fecha, $_origen);
				$addkardextrans = $this->model->kardex_tiendita->addKardexTrans($_producto, $_cantidad, $_origen, 1, $_fecha);
				if(!$addkardextrans->response){
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($addkardextrans); 
				}
				$this->model->kardex_tiendita->arreglaKardex($_producto, $_fecha, $_destino);
				$addkardextrans2 = $this->model->kardex_tiendita->addKardexTrans($_producto, $_cantidad, $_destino, -1, $_fecha);
				if(!$addkardextrans2->response){
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($addkardextrans2); 
				}
			}
			$CancelaTransferencia->result = null;
			$CancelaTransferencia->message = 'Baja de '.$arguments['id'];
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($CancelaTransferencia);
		});

		// Editar transferencia
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$transferencia_id = $arguments['id'];
			$dataTransferencia = [ 
				'fk_usuario'=>$parsedBody['fk_usuario'],
				'fk_origen'=>$parsedBody['fk_origen'],
				'fk_destino'=>$parsedBody['fk_destino'],
				'fk_producto'=>$parsedBody['fk_producto'],
				'cantidad'=>$parsedBody['cantidad'],
			];
			$transferenciaEdit = $this->model->transferencia->edit($dataTransferencia, $transferencia_id);
			if($transferenciaEdit){
				$seg_log = $this->model->seg_log->add('Editar transferencia',$arguments['id'], 'transferencia_tiendita'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($transferenciaEdit);
		});

        
		/* // Obtener reporte por proveedor entre fechas
		$this->get('rptProv/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada_comunidad->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		}); */
       

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->entrada_comunidad->findBy($args['f'], $args['v'])));			
		});
	});
?>