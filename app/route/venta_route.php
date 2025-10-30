<?php
	use App\Lib\Response;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	$app->group('/venta/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de venta_tiendita');			
		});

        // Obtener todas las ventas
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->venta->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

		// Obtener todos det_venta
		$this->get('getAllDetVenta/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->venta->getAllDetVenta($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

		// Obtener ventas por fecha
		$this->get('getByDate/{inicio}/{final}/{user}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->getByDate($arguments['inicio'], $arguments['final'], $arguments['user']);
			return $response->withJson($resultado);
		});

		// Obtener ventas por fecha (xlsx)
		$this->get('getByDate/xlsx/{inicio}/{final}/', function($request, $response, $arguments){
			ini_set('memory_limit','256M');
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
			$arrFe = explode('-', $arguments['inicio']);
			$subtitulo = "del ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]];
			if($arguments['inicio'] != $arguments['final']){
				$arrFe = explode('-', $arguments['final']);
				$subtitulo .= " al ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]]." del ".$arrFe[0];
			}else{
				$subtitulo .= " del ".$arrFe[0];
			}

			$sheet->setCellValue("A1", "REPORTE DE VENTAS EN TIENDITA ".$subtitulo);
			$sheet->setCellValue("A2", 'PRODUCTO');
			$sheet->setCellValue("B2", 'PESO UNI');
			$sheet->setCellValue("C2", "PRECIO U");
			$sheet->setCellValue("D2", 'CANTIDAD');
			$sheet->setCellValue("E2", 'IMPORTE');

			$resultado = $this->model->venta->getByDate($arguments['inicio'], $arguments['final'], '0');
			foreach($resultado as $reg){
				$reg->detalles = $this->model->det_venta->getDetalle($reg->id_venta);
			}		

			//echo json_encode($resultado); exit;

    		$prods = 0;
    		$total = 0;

			$fila = 3;
			foreach($resultado as $res){
				$sheet->setCellValue("A".$fila, 'Ticket '.$res->id_venta.'        '.$res->fechaf.'        '.$res->vendedor.'        '.$res->beneficiario);
				$fila++;

				$prods += $res->num_prod;
        		$total += $res->total;

				foreach($res->detalles as $result){
					$sheet->setCellValue("A".$fila, $result->descripcion);
					$sheet->setCellValue("B".$fila, $result->peso);
					$sheet->setCellValue("C".$fila, $result->precio);					
					$sheet->setCellValue("D".$fila, $result->cantidad);
					$sheet->setCellValue("E".$fila, number_format($result->importe, 2,'.', ','));
					$fila++;
				}

				$sheet->setCellValue("A".$fila, $res->num_prod.' productos');
				$sheet->setCellValue("D".$fila, 'TOTAL');
				$sheet->setCellValue("E".$fila, '$ '.number_format($res->total, 2,'.', ','));
				$fila++;
			}
			$sheet->setCellValue("A".$fila, $prods.' productos');
			$sheet->setCellValue("D".$fila, 'TOTAL');
			$sheet->setCellValue("E".$fila, '$ '.number_format($total, 2,'.', ','));
			/* $writer = new Csv($spreadsheet);
			$writer->setUseBOM(true);
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"Ventascajero_".date('YmdHi').".csv\"");
			$writer->save('php://output'); */
			$writer = new Xlsx($spreadsheet);
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=\"Ventascajero_".date('YmdHi').".xlsx\"");
			$writer->save('php://output');
		});

		// Obtener cancelados por fecha
		$this->get('getCancelados/{inicio}/{final}/{user}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getCancelados($arguments['inicio'], $arguments['final'], $arguments['user']));
		});

		// Cancelar venta
		$this->put('cancel/{venta}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$infoVenta = $this->model->venta->get($arguments['venta'])->result;
				$fecha = substr($infoVenta->fecha, 0,10);
				$cajero = $infoVenta->fk_usuario;
				$detVent = $this->model->det_venta->getByVenta($arguments['venta'])->result;
				$count=count($detVent);
				for($x=0;$x<$count;$x++){
					$cant = $detVent[$x]->cantidad;
					$prod = $detVent[$x]->fk_producto;
					$stocktienditasum = $this->model->producto->stockTienditaSum($cant, $prod);
					if(!$stocktienditasum->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($stocktienditasum); 
					}
					$salidasrest = $this->model->kardex_tiendita->salidasRest($cant, $prod, $fecha, $cajero);
					if(!$salidasrest->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($salidasrest); 
					}
					$this->model->kardex_tiendita->arreglaKardex($prod, $fecha, $cajero);
				}
				$CancelaVenta=$this->model->venta->del($arguments['venta']);
				if($CancelaVenta->response){
					$seg_log = $this->model->seg_log->add('Cancelar venta', $arguments['venta'], 'venta_tiendita'); 
						if(!$seg_log->response) {
							$seg_log->state = $this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
				}else{
					$CancelaVenta->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($CancelaVenta);
				}
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($CancelaVenta);
			}
		});

		// Agregar venta
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$parsedBody = $request->getParsedBody();
				$fecha = date("Y-m-d");
				$_peso_total = $parsedBody['peso_total'];
				$tipo = $parsedBody['tipo'];
				$institucion = '';//$parsedBody['institucion'];
				$_usuario = $parsedBody['fk_usuario'];
				$_total = $parsedBody['total'];
				$beneficiario = $parsedBody['beneficiario'];
				$data = [
					'peso_total'=>$_peso_total,
					'tipo'=>$tipo,
					'institucion'=>$institucion,
					'fk_usuario'=>$_usuario,
					'total'=>$_total,
					'beneficiario'=>$beneficiario
				];
				$resVenta =  $this->model->venta->add($data);
				$_fk_venta = $resVenta->result;
				if($_fk_venta){
					$seg_log = $this->model->seg_log->add('Agregar venta', $_fk_venta, 'venta_tiendita'); 
						if(!$seg_log->response) {
							$seg_log->state = $this->model->transaction->regresaTransaccion(); 
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
					$_importe = $detalle['importe'];
					$produc = $this->model->producto->get($_fk_producto);
					$stockCaja = $this->model->producto->getStock($_fk_producto, $_usuario)->final;
					if($stockCaja < $_cantidad){
						$produc->state=$this->model->transaction->regresaTransaccion();
						$produc->setResponse(false,'No hay suficiente stock para '.$produc->result->descripcion);
						unset($produc->result);
						return $response->withJson($produc);
					}
					$detdata = [
						'fk_venta'=>$_fk_venta,
						'fk_producto'=>$_fk_producto,
						'cantidad'=>$_cantidad,
						'peso'=>$_peso,
						'importe'=>$_importe
					];
					$result = $this->model->det_venta->add($detdata);
					if($result->response){
						$edit_stock = $this->model->producto->stockTienditaRest($_cantidad, $_fk_producto);
						if($edit_stock->response) {
							$add_kardex = $this->model->kardex_tiendita->addKardex($_fk_producto, $_cantidad, $_usuario, '-1');
							if($add_kardex->response) {
								$this->model->kardex_tiendita->arreglaKardex($_fk_producto, date('Y-m-d'), $_usuario);
							}else{
								$add_kardex->state=$this->model->transaction->regresaTransaccion();
								return $response->withJson($add_kardex);
							}
						}else{
							$edit_stock->state=$this->model->transaction->regresaTransaccion();
							return $response->withJson($edit_stock);
						}
					}else{
						$result->state=$this->model->transaction->regresaTransaccion();
						return $response->withJson($result);
					}
				}
				$resVenta->state=$this->model->transaction->confirmaTransaccion();
				return $response->withJson($resVenta);
			}
		});

		// Editar venta
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$venta_id = $arguments['id'];
            $_peso_total = $parsedBody['peso_total'];
			$tipo = $parsedBody['tipo'];
			$institucion = $parsedBody['institucion'];
			$_usuario = $parsedBody['usuario'];
			$_total = $parsedBody['total'];
			$beneficiario = $parsedBody['beneficiario'];
            $dataVenta = [
				'peso_total'=>$_peso_total,
				'tipo'=>$tipo,
                'institucion'=>$institucion,
                'fk_usuario'=>$_usuario,
				'total'=>$_total,
				'beneficiario'=>$beneficiario
			];
            $venta = $this->model->venta->edit($dataVenta, $venta_id);
			$detalles = $parsedBody['detalles'];
			foreach($detalles as $detalle) {
				$dataDetVenta = [
					'fk_producto'=>$detalle['id_producto'],
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso'],
                    'importe'=>$detalle['importe']
				];
                $venta2 = $this->model->det_venta->editByVenta($dataDetVenta, $venta_id, $detalle['id_producto']);
				if(!$venta2->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($venta2);
				}
				if($venta->response) {
					$seg_log = $this->model->seg_log->add('Actualización información venta', $venta_id, 'venta', 1);
					if(!$seg_log->response) {
						$seg_log->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($seg_log);
					}
					$venta->SetResponse(true, 'Venta actualizada');
				}else{
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($venta); 
				}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($venta);
		});

		// Editar estado de venta
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$venta_id = $arguments['id'];
			$dataVenta = [
				'estado'=>$parsedBody['estado']
			];
			$venta = $this->model->venta->edit($dataVenta, $venta_id);
			if($venta->response) {
				$seg_log = $this->model->seg_log->add('Actualización información venta', $venta_id, 'venta', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$venta->SetResponse(true, 'Venta actualizada');
			}else{
				$venta->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($venta); 
			}
			$venta->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($venta);
		});

		// Editar tipo de venta
		$this->post('editTipo/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$venta_id = $arguments['id'];
			$dataVenta = [
				'tipo'=>$parsedBody['tipo']
			];
			$venta = $this->model->venta->edit($dataVenta, $venta_id);
			if($venta->response) {
				$seg_log = $this->model->seg_log->add('Cambia tipo venta', $venta_id, 'venta', 1);
				if(!$seg_log->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$venta->SetResponse(true, 'Venta actualizada');
			}else{
				$this->model->transaction->regresaTransaccion(); 
				return $response->withJson($venta); 
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($venta);
		});

		// Obtener venta por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->get($arguments['id']);
			$resultado->detalles = $this->model->det_venta->getByVenta($arguments['id'])->result;
			return $response->withJson($resultado);
		});

		// Eliminar venta
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$venta_id = $arguments['id'];
			$del_venta = $this->model->venta->del($venta_id);
			if($del_venta->response) {
				$seg_log = $this->model->seg_log->add('Baja de venta', $venta_id, 'venta'); 
				if(!$seg_log->response) {
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{ 
				$this->model->transaction->regresaTransaccion();
				return $response->withJson($del_venta);
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_venta);
		});

		// Obtener usuarios passSupervisor
		$this->get('getPassSupervisor/{password}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getPassSupervisor($arguments['password']));
		});

		// Agregar detalle de venta
		$this->post('addDetalle/', function($request, $response, $arguments) {
            $parsedBody = $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();
            $dataDetalles = [
				'fk_venta'=>$parsedBody['fk_venta'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso'],
				'importe'=>$parsedBody['importe']
			];
			$add = $this->model->det_venta->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle venta', $parsedBody['fk_venta'], 'det_venta_tiendita'); 
					if(!$seg_log->response) {
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
					}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($add);
		});

		// Obtener det_venta por venta
		$this->get('getDetalles/{venta}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_venta->getByVenta($arguments['venta']));
		});

		// Eliminar venta
		$this->put('delDetalleVenta/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			if(!isset($_SESSION['usuario'])){
				$respuesta=new Response();
				$respuesta->state=$this->model->transaction->regresaTransaccion(); 
				$respuesta->SetResponse(false,"Expiro sesión");
				$respuesta->sesion = 0;
				return $response->withJson($respuesta);
			}else{
				$del_det_venta = $this->model->det_venta->getById($arguments['id']);
				$cant = $del_det_venta->cantidad;
				$fecha = substr($del_det_venta->fecha,0,10);
				$cajero = $del_det_venta->cajero;
				$venta = $del_det_venta->fk_venta;
				$prod = $del_det_venta->fk_producto;
				$del_detventa = $this->model->det_venta->del($arguments['id']);
				if($del_detventa->response){
					$stocktienditasum = $this->model->producto->stockTienditaSum($cant, $prod);
					if(!$stocktienditasum->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($stocktienditasum); 
					}
					$salidasrest = $this->model->kardex_tiendita->salidasRest($cant, $prod, $fecha, $cajero);
					if(!$salidasrest->response){
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($salidasrest); 
					}
					$this->model->kardex_tiendita->arreglaKardex($prod, $fecha, $cajero);
					$PesoTotal = $this->model->det_venta->getPesoTotal($venta)->peso_total;
					$Total = $this->model->det_venta->getTotal($venta)->total;
					$data = [
						'peso_total'=>$PesoTotal,
						'total'=>$Total
					];
					$edit = $this->model->venta->edit($data, $venta); 
					if($edit->response) {
						$seg_log = $this->model->seg_log->add('Baja de det_venta', $arguments['id'], 'det_venta'); 
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
					return $response->withJson($del_detventa); 
				}	
				$del_detventa->result = null;			
				$this->model->transaction->confirmaTransaccion();
				return $response->withJson($del_detventa);
			}
		});

		// Obtener beneficiario por credencial
		$this->get('getBeneficiario/{cred}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getBeneficiario($arguments['cred']));
		}); 

		// Obtener tickets
		$this->get('getTickets/{inicio}/{fin}/{cajero}', function($request, $response, $arguments) {
			$totaltickets = 0;
			$totalmermas = 0;
			$totalcancel = 0;
			if($arguments['cajero']=='0'){
				$resultado = $this->model->usuario->getCajerosActivos();
				foreach($resultado->result as $reg){
					$tickets = $this->model->venta->getTickets($arguments['inicio'], $arguments['fin'], $reg->id_usuario);
					$reg->tickets = $tickets->total;
					$totaltickets = $totaltickets + intval($tickets->total);
					$mermas = $this->model->venta->getMermas($arguments['inicio'], $arguments['fin'], $reg->id_usuario);
					$reg->mermas = $mermas->total;
					$totalmermas = $totalmermas + intval($mermas->total);
					$reg->cancelados = $this->model->venta->getCancelados($arguments['inicio'], $arguments['fin'], $reg->id_usuario)->cancelados;
					$totalcancel = $totalcancel+intval($reg->cancelados);					
				}
				$resultado->tickets = strval($totaltickets);
				$resultado->mermas = strval($totalmermas);
				$resultado->cancelados = strval($totalcancel);
				return $response->withJson($resultado);
			}else{
				$resultado = $this->model->usuario->getCajeroActivo($arguments['cajero']);
				foreach($resultado->result as $reg){
					$tickets = $this->model->venta->getTickets($arguments['inicio'], $arguments['fin'], $reg->id_usuario);
					$reg->tickets = $tickets->total;
					$totaltickets = $totaltickets + intval($tickets->total);
					$mermas = $this->model->venta->getMermas($arguments['inicio'], $arguments['fin'], $reg->id_usuario);
					$reg->mermas = $mermas->total;
					$totalmermas = $totalmermas + intval($mermas->total);
					$reg->cancelados = $this->model->venta->getCancelados($arguments['inicio'], $arguments['fin'], $reg->id_usuario)->cancelados;
					$totalcancel = $totalcancel+intval($reg->cancelados);
				}	
				$resultado->tickets = strval($totaltickets);
				$resultado->mermas = strval($totalmermas);
				$resultado->cancelados = strval($totalcancel);
				return $response->withJson($resultado);
			}
		});

		/* // Obtener reporte de ventas entre fechas
		$this->get('rptVen/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getRptVen($arguments['inicio'], $arguments['fin']));
		}); */

		// Obtener reporte de ventas entre fechas FINAL
		$this->get('rptVentasFinal/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->getRptVentasFinal($arguments['inicio'], $arguments['fin']);
			$array1 = array();
			foreach($resultado as $reg){
				$array1 [] = $reg->fk_producto;
				$prod = $this->model->producto->get($reg->fk_producto)->result;
				$reg->producto = $prod->descripcion;
				$reg->pesou = $prod->peso;
				$reg->precio = $prod->precio;
				$reg->donador = $this->model->venta->getDonadorByVenta($reg->fk_producto)->donador;
				$getinicialbyventa = $this->model->venta->getInicialByVenta($reg->fk_producto, $reg->fecha);
				$reg->inicial = $getinicialbyventa->inicial;
				$entradass = $this->model->venta->getEntradasByVenta($reg->fk_producto, $reg->fecha);
				if($entradass == null){
					$reg->entradas = '0';
				}else{
					$reg->entradas = $entradass->entradas;
				}
				$ventass = $this->model->venta->getVentasByVenta($reg->fk_producto, $reg->fecha);
				if($ventass->ventas == null){
					$reg->ventas = '0';
				}else{
					$reg->ventas = $ventass->ventas;
				}
				if($ventass->ventast == null){
					$reg->ventast = '0';
				}else{
					$reg->ventast = $ventass->ventast;
				}
				$mermass = $this->model->venta->getMermasByVenta($reg->fk_producto, $reg->fecha);
				if($mermass->mermas == null){
					$reg->mermas = '0';
				}else{
					$reg->mermas = $mermass->mermas;
				}
				$trabb = $this->model->venta->getTrabByVenta($reg->fk_producto, $reg->fecha);
				if($trabb->trab == null){
					$reg->trab = '0';
				}else{
					$reg->trab = $trabb->trab;
				}
				$donacionn = $this->model->venta->getDonacionByVenta($reg->fk_producto, $reg->fecha);
				if($donacionn->donacion == null){
					$reg->donacion = '0';
				}else{
					$reg->donacion = $donacionn->donacion;
				}
				$instt = $this->model->venta->getInstByVenta($reg->fk_producto, $reg->fecha);
				if($instt->inst == null){
					$reg->inst = '0';
				}else{
					$reg->inst = $instt->inst;
				}
				$finall = $getinicialbyventa->final;
				if($finall == null){
					$reg->final = '0';
				}else{
					$reg->final = $finall;
				}
			}
			$fecha = date("d-m-Y");
			$fecha2 = date("Y-m-d");
			if($arguments['inicio'] == $fecha2 && $arguments['fin'] == $fecha2){

				$array2 = array();

				$exist = $this->model->producto->getByCajero(35, null)->result;

				foreach($exist as $reg){
					if(!in_array($reg["id_producto"], $array1)){

						$array2 [] = $reg["id_producto"];

						$prod = $this->model->producto->get($reg["id_producto"])->result;

						$inicialFinal = $this->model->venta->getInvInicialEntradas($reg["id_producto"]);

						$objecto = new stdClass();

						$objecto->fechaf = $fecha;
						$objecto->fk_producto = $reg["id_producto"];
						$objecto->fecha = $fecha2;
						$objecto->todas = $reg["stock_tiendita"];
						$objecto->producto = $prod->descripcion;
						$objecto->pesou = $prod->peso;
						$objecto->precio = $prod->precio;
						$objecto->donador = $this->model->venta->getDonadorByVenta($reg["id_producto"])->donador;
						$objecto->inicial = $inicialFinal->inicial;
						$objecto->entradas = $inicialFinal->entradas;
						$objecto->ventas = "0";
						$objecto->ventast = "00.00";
						$objecto->mermas = "0";
						$objecto->trab = "0";
						$objecto->donacion = "0";
						$objecto->inst = "0";
						$objecto->final = $reg["stock_tiendita"];

						$resultado[] = $objecto;

					}
				}
			}	
			return $response->withJson($resultado);
		});

		// Obtener reporte de ventas entre fechas FINAL (pdf)
		$this->get('rptVentasFinal/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->getRptVentasFinal($arguments['inicio'], $arguments['fin']);

			$array1 = array();

			foreach($resultado as $reg){

				$array1 [] = $reg->fk_producto;

				$prod = $this->model->producto->get($reg->fk_producto)->result;
				$reg->producto = $prod->descripcion;
				$reg->pesou = $prod->peso;
				$reg->precio = $prod->precio;
				$reg->donador = $this->model->venta->getDonadorByVenta($reg->fk_producto)->donador;
				$getinicialbyventa = $this->model->venta->getInicialByVenta($reg->fk_producto, $reg->fecha);
				$reg->inicial = $getinicialbyventa->inicial;
				$entradass = $this->model->venta->getEntradasByVenta($reg->fk_producto, $reg->fecha);
				if($entradass == null){
					$reg->entradas = '0';
				}else{
					$reg->entradas = $entradass->entradas;
				}
				$ventass = $this->model->venta->getVentasByVenta($reg->fk_producto, $reg->fecha);
				if($ventass->ventas == null){
					$reg->ventas = '0';
				}else{
					$reg->ventas = $ventass->ventas;
				}
				if($ventass->ventast == null){
					$reg->ventast = '0';
				}else{
					$reg->ventast = $ventass->ventast;
				}
				$mermass = $this->model->venta->getMermasByVenta($reg->fk_producto, $reg->fecha);
				if($mermass->mermas == null){
					$reg->mermas = '0';
				}else{
					$reg->mermas = $mermass->mermas;
				}
				$trabb = $this->model->venta->getTrabByVenta($reg->fk_producto, $reg->fecha);
				if($trabb->trab == null){
					$reg->trab = '0';
				}else{
					$reg->trab = $trabb->trab;
				}
				$donacionn = $this->model->venta->getDonacionByVenta($reg->fk_producto, $reg->fecha);
				if($donacionn->donacion == null){
					$reg->donacion = '0';
				}else{
					$reg->donacion = $donacionn->donacion;
				}
				$instt = $this->model->venta->getInstByVenta($reg->fk_producto, $reg->fecha);
				if($instt->inst == null){
					$reg->inst = '0';
				}else{
					$reg->inst = $instt->inst;
				}
				$finall = $getinicialbyventa->final;
				if($finall == null){
					$reg->final = '0';
				}else{
					$reg->final = $finall;
				}
			}

			$fecha = date("d-m-Y");
			$fecha2 = date("Y-m-d");
			if($arguments['inicio'] == $fecha2 && $arguments['fin'] == $fecha2){

				$array2 = array();

				$exist = $this->model->producto->getByCajero(35, null)->result;

				foreach($exist as $reg){
					if(!in_array($reg["id_producto"], $array1)){

						$array2 [] = $reg["id_producto"];

						$prod = $this->model->producto->get($reg["id_producto"])->result;

						$inicialFinal = $this->model->venta->getInvInicialEntradas($reg["id_producto"]);

						$objecto = new stdClass();

						$objecto->fechaf = $fecha;
						$objecto->fk_producto = $reg["id_producto"];
						$objecto->fecha = $fecha2;
						$objecto->todas = $reg["stock_tiendita"];
						$objecto->producto = $prod->descripcion;
						$objecto->pesou = $prod->peso;
						$objecto->precio = $prod->precio;
						$objecto->donador = $this->model->venta->getDonadorByVenta($reg["id_producto"])->donador;
						$objecto->inicial = $inicialFinal->inicial;
						$objecto->entradas = $inicialFinal->entradas;
						$objecto->ventas = "0";
						$objecto->ventast = "00.00";
						$objecto->mermas = "0";
						$objecto->trab = "0";
						$objecto->donacion = "0";
						$objecto->inst = "0";
						$objecto->final = $reg["stock_tiendita"];

						$resultado[] = $objecto;

					}
				}
			}	

			// print_r($resultado); exit;

			$ventas = $resultado;
			$titulo = "REPORTE DE VENTAS EN TIENDITA";
			$params = array('vista' => $titulo);
        	$params['registros'] = $ventas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			return $this->view->render($response, 'rptVentas.php', $params);
		});

		// Obtener reporte por tickets de ventas entre fechas (pdf)
		$this->get('rptVentasTickets/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->getByDate($arguments['inicio'], $arguments['fin'], '0');
			foreach($resultado as $reg){
				$reg->detalles = $this->model->det_venta->getDetalle($reg->id_venta);
			}
			$ventas = $resultado;
			$titulo = "REPORTE DE VENTAS POR TICKET";
			$params = array('vista' => $titulo);
        	$params['registros'] = $ventas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			return $this->view->render($response, 'rptVentasByTicket.php', $params);			
		});

		// Obtener reporte por tickets de ventas entre fechas (pdf)
		$this->get('rptVentasTotales/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->venta->getByDate($arguments['inicio'], $arguments['fin'], '0');
			$ventas = $resultado;
			$titulo = "REPORTE TOTAL DE VENTAS";
			$params = array('vista' => $titulo);
        	$params['registros'] = $ventas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			//return $response->withJson($ventas);
			return $this->view->render($response, 'rptVentasTotal.php', $params);
		});

		// Obtener reporte entre fechas ordenado por fecha
		$this->get('rptDate/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getRptDate($arguments['inicio'], $arguments['fin']));
		});

		// Obtener reporte entre fechas
		$this->get('rptDatee/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getRptDatee($arguments['inicio'], $arguments['fin']));
		});

		// Obtener reporte entre fechas 
		$this->get('rptVentasCajero/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->venta->getRptVentasCajero($arguments['inicio'], $arguments['fin']));
		});

		// xlsx Existencias almacen
		$this->get('rptVentasCajero/xlsx/{inicio}/{fin}', function($request, $response, $arguments){
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$sheet->setCellValue("A1", 'Fecha');
			$sheet->setCellValue("B1", 'Cajero');
			$sheet->setCellValue("C1", "Producto");
			$sheet->setCellValue("D1", 'Cantidad');
			$sheet->setCellValue("E1", 'Peso');
			$sheet->setCellValue("F1", 'Importe');

			$resultado = $this->model->venta->getRptVentasCajero($arguments['inicio'], $arguments['fin']);			

			$fila = 2;
			foreach($resultado as $res){
				$sheet->setCellValue("A".$fila, $res->fechaf);
				$sheet->setCellValue("B".$fila, $res->cajero);
				$sheet->setCellValue("C".$fila, $res->producto);
				$sheet->setCellValue("D".$fila, $res->cant);
				$sheet->setCellValue("E".$fila, $res->peso);
				$sheet->setCellValue("F".$fila, number_format($res->importe),2);
				$fila++;
			}
			/* $writer = new Csv($spreadsheet);
			$writer->setUseBOM(true);
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"Ventascajero_".date('YmdHi').".csv\"");
			$writer->save('php://output'); */
			$writer = new Xlsx($spreadsheet);
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=\"Ventascajero_".date('YmdHi').".xlsx\"");
			$writer->save('php://output');
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->salida_comunidad->findBy($args['f'], $args['v'])));			
		}); 

		$this->get('ticket/{id}', function($req, $res, $args){
			$venta = $this->model->venta->get($args['id'])->result;
			$detalles = $this->model->det_venta->getByVenta($args['id'])->result;

			foreach ($detalles as $item) {
				$contenido = $this->model->producto_paquete->get($item->fk_producto, $item->fecha_modificacion);
				if($contenido->response){
					$item->contenido = "(".$contenido->result->contenido.")";
				}else{
					$item->contenido = "";
				}
			}

			$params = array('vista' => 'Ticket #'.$args['id'], 'venta' => $venta, 'detalles' => $detalles);
			return $this->view->render($res, 'ticket.phtml', $params);
		});
	});
?>