<?php
	use App\Lib\Response,
		App\Lib\MiddlewareToken;
	require_once './core/defines.php';	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Csv;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	$app->group('/producto/', function() {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de producto');
		});

		//Obtener todos los productos
		$this->get('getAll/{pagina}/{limite}[/{tipo}[/{busqueda}]]', function($request, $response, $arguments) {
			$arguments['tipo'] = isset($arguments['tipo'])? $arguments['tipo'] : 0;
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->producto->getAll($arguments['pagina'], $arguments['limite'], $arguments['tipo'], $arguments['busqueda']));
		});

		$this->get('getAllAjax/{inicial}/{limite}/{busqueda}/[{filtros}]', function($request, $response, $arguments) {
			include_once('../public/core/actions.php');
			$inicial = isset($_GET['start'])? $_GET['start']: $arguments['inicial'];
			$limite = isset($_GET['length'])? $_GET['length']: $arguments['limite'];
			$busqueda = isset($_GET['search']['value'])? (strlen($_GET['search']['value'])>0? $_GET['search']['value']: '_'): $arguments['busqueda'];
			$orden = isset($_GET['order'])? $_GET['columns'][$_GET['order'][0]['column']]['data']: 'descripcion';
			if(strpos($orden, 'stock') !== false) $orden = 'CAST('.$orden.' AS SIGNED)';
			$orden .= isset($_GET['order'])? " ".$_GET['order'][0]['dir']: " asc";

			if(count($_GET['order'])>1){
				for ($i=1; $i < count($_GET['order']); $i++) { 
					$orden .= ', '.$_GET['columns'][$_GET['order'][$i]['column']]['data'].' '.$_GET['order'][$i]['dir'];
				}
			}

			$modulo = 3; $user = $_SESSION['usuario']->id_usuario; $perm = $this->model->usuario->getAcciones($user, $modulo); $permisos = getPermisos($perm);
			$acciones = '';
			$acciones .= (in_array(MOD_PRODUCTOS_EDIT, $permisos)? '<a href="#" data-popup="tooltip" title="Editar" class="btnEdit text-primary"><i class="mdi mdi-pencil fa-lg"></i></a> ': '');
			$acciones .= (in_array(MOD_PRODUCTOS_DEL, $permisos)? '<a href="#" data-popup="tooltip" title="Dar de baja" class="btnDel"><i class="mdi mdi-delete fa-lg" style="color:red;"></i></a>': '');
			$productos = $this->model->producto->getAll($inicial, $limite, 0, $busqueda, $orden);
			
			$data = [];
			foreach($productos->result as $prod) {

				$data[] = array(
					"id" => $prod->id_producto, 
					"descripcion" => "<small class=\"descripcion\">$prod->descripcion</small>",
					"proveedor" => "<small class=\"proveedor\">$prod->proveedor</small>",
					"peso" => "<small class=\"peso\">$prod->peso</small>",
					"precio" => "<small class=\"precio\">$prod->precio</small>",
					"codigo_barras" => "<small class=\"codigo_barras\">$prod->codigo_barras</small>",
					"presenta" => "<small class=\"presenta\">$prod->presenta</small>",
					"tipos" => "<small class=\"tipos\">$prod->tipos</small>",
					"stock" => "<small class=\"stock\">$prod->stock</small>",
					"exist" => "<small class=\"exist\">".number_format($prod->exist,3)."</small>",
					"stock_tiendita" => "<small class=\"stock_tiendita\">$prod->stock_tiendita</small>",
					"stock_comunidad" => "<small class=\"stock_comunidad\">$prod->stock_comunidad</small>",
					"stock_produccion" => "<small class=\"stock_produccion\">$prod->stock_produccion</small>",
					"acciones" => $acciones,
				);
			}

			echo json_encode(array(
				'draw'=>$_GET['draw'],
				'data'=>$data,
				'recordsTotal'=>$productos->totalProd,
				'sqlTotal'=>$productos->totalSQL,
				'recordsFiltered'=>$productos->total,
			));
			exit(0);
		});

		$this->get('getAllAjax2/{inicial}/{limite}/{busqueda}/[{filtros}]', function($request, $response, $arguments) {
			include_once('../public/core/actions.php');
			$inicial = isset($_GET['start'])? $_GET['start']: $arguments['inicial'];
			$limite = isset($_GET['length'])? $_GET['length']: $arguments['limite'];
			$busqueda = isset($_GET['search']['value'])? (strlen($_GET['search']['value'])>0? $_GET['search']['value']: '_'): $arguments['busqueda'];
			$orden = isset($_GET['order'])? $_GET['columns'][$_GET['order'][0]['column']]['data']: 'descripcion';
			if(strpos($orden, 'stock') !== false) $orden = 'CAST('.$orden.' AS SIGNED)';
			$orden .= isset($_GET['order'])? " ".$_GET['order'][0]['dir']: " asc";

			if(count($_GET['order'])>1){
				for ($i=1; $i < count($_GET['order']); $i++) { 
					$orden .= ', '.$_GET['columns'][$_GET['order'][$i]['column']]['data'].' '.$_GET['order'][$i]['dir'];
				}
			}

			$modulo = 3; $user = $_SESSION['usuario']->id_usuario; $perm = $this->model->usuario->getAcciones($user, $modulo); $permisos = getPermisos($perm);
			$acciones = '';
			$acciones .= (in_array(MOD_PRODUCTOS_EDIT, $permisos)? '<a href="#" data-popup="tooltip" title="Editar" class="btnEdit text-primary"><i class="mdi mdi-pencil fa-lg"></i></a> ': '');
			$acciones .= (in_array(MOD_PRODUCTOS_DEL, $permisos)? '<a href="#" data-popup="tooltip" title="Dar de baja" class="btnDel"><i class="mdi mdi-delete fa-lg" style="color:red;"></i></a>': '');
			$productos = $this->model->producto->getAll($inicial, $limite, 0, $busqueda, $orden);
			
			$data = [];
			foreach($productos->result as $prod) {

				$data[] = array(
					"id" => $prod->id_producto, 
					"descripcion" => "<small class=\"descripcion\">$prod->descripcion</small>",
					"proveedor" => "<small class=\"proveedor\">$prod->proveedor</small>",
					"peso" => "<small class=\"peso\">$prod->peso</small>",
					"precio" => "<small class=\"precio\">$prod->precio</small>",
					"codigo_barras" => $prod->codigo_barras,
					"presenta" => "<small class=\"presenta\">$prod->presenta</small>",
					"tipos" => "<small class=\"tipos\">$prod->tipos</small>",
					"stock" => "<small class=\"stock\">$prod->stock</small>",
					"exist" => "<small class=\"exist\">".number_format($prod->exist,3)."</small>",
					"stock_tiendita" => "<small class=\"stock_tiendita\">$prod->stock_tiendita</small>",
					"stock_comunidad" => "<small class=\"stock_comunidad\">$prod->stock_comunidad</small>",
					"stock_produccion" => "<small class=\"stock_produccion\">$prod->stock_produccion</small>",
					"acciones" => $acciones,
				);
			}

			echo json_encode(array(
				'draw'=>$_GET['draw'],
				'data'=>$data,
				'recordsTotal'=>$productos->totalProd,
				'sqlTotal'=>$productos->totalSQL,
				'recordsFiltered'=>$productos->total,
			));
			exit(0);
		});

		// Obtener stock por clave (pdf)
		$this->get('rptCodigosBarra/print/{data}/{numero}', function($request, $response, $arguments) {
			$titulo = "Código de Barras";
		
			$dataBase64 = urldecode($arguments['data']);
			$jsonStr = base64_decode($dataBase64);
			$lista = json_decode($jsonStr, true);
		
			$params = [
				'vista' => $titulo,
				'registros' => $lista, 
				'numero' => intval($arguments['numero']) 
			];
			return $this->view->render($response, 'rptCodigosBarra.php', $params);
		});		

		// Obtener productos por cajero y codigo de barras
		$this->get('getByCajero/{cajero}[/{codigo}]', function($request, $response, $arguments) {
			$arguments['codigo'] = isset($arguments['codigo'])? $arguments['codigo']: null;
			return $response->withJson($this->model->producto->getByCajero($arguments['cajero'], $arguments['codigo']));
		});

		// Obtener productos por cajero y codigo de barras (pdf)
		$this->get('rptExistenciasByCajero/print/{cajero}', function($request, $response, $arguments) {
			$existencias = $this->model->producto->getByCajero($arguments['cajero'], '')->result;
			
			$titulo = "Existencias [Tiendita] ";
			$nom = $this->model->usuario->get($arguments['cajero'])->result;
			if($arguments['cajero'] != 0) {
				$titulo .= "Cajero: ".$nom->apellidos." ".$nom->nombre;
			} else {
				$titulo .= "Almacen TIENDITA";
			}
			$params = array('vista' => $titulo);
        	$params['registros'] = $existencias;
			return $this->view->render($response, 'rptExistenciasCajero.php', $params);
		});

		// Obtener productos por cajero (xlsx)
		$this->get('rptExistenciasByCajero/xlsx/{cajero}', function($request, $response, $arguments){
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$titulo = "Existencias [Tiendita] ";
			$nom = $this->model->usuario->get($arguments['cajero'])->result;
			if($arguments['cajero'] != 0) {
				$titulo .= "Cajero: ".$nom->apellidos." ".$nom->nombre;
			} else {
				$titulo .= "Almacen TIENDITA";
			}

			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    		$subtitulo = "Al ".date('d')." de ".$arrMes[intval(date('m'))]." de ".date('Y')." ".date('H:i:s');

			$sheet->setCellValue("A1", $titulo);
			$sheet->setCellValue("E1", $subtitulo);

			$sheet->setCellValue("A2", 'Producto');
			$sheet->setCellValue("B2", 'Total');
			$sheet->setCellValue("C2", "Existencia");

			$existencias = $this->model->producto->getByCajero($arguments['cajero'], '')->result;
			//echo json_encode($existencias); exit;

			$fila = 3;
			foreach($existencias as $res){
				$sheet->setCellValue("A".$fila, $res['descripcion']);
				$sheet->setCellValue("B".$fila, $res['stock_tiendita']);
				$sheet->setCellValue("C".$fila, $res['stock_cajero']);
				$fila++;
			}
			/* $writer = new Csv($spreadsheet);
			$writer->setUseBOM(true);
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"exist_cajero".$arguments['cajero']."_".date('YmdHi').".csv\"");
			$writer->save('php://output'); */
			$writer = new Xlsx($spreadsheet);
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=\"exist_cajero".$arguments['cajero']."_".date('YmdHi').".xlsx\"");
			$writer->save('php://output');
		});

		// Obtener stock tiendita de producto por id
		$this->get('getProductStockTiendita/{id_producto}', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->getProductStockTiendita($arguments['id_producto']));
		});

		// Obtener productos por clave
		$this->get('buscaProd/{codigo}[/{cajero}]', function($request, $response, $arguments) {
			$arguments['cajero'] = isset($arguments['cajero'])? $arguments['cajero']: null;
			return $response->withJson($this->model->producto->buscaProd($arguments['cajero'], $arguments['codigo']));
		});

		// Obtener todos los registros de la tabla cat_producto por proveedor
		$this->get('listarXProveedor/{proveedor}', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->listarXProveedor($arguments['proveedor']));
		});

		// Obtener stock por clave
		$this->get('stockByClave/', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->stockByClave());
		});

		// Obtener stock por clave (pdf)
		$this->get('stockByClave/print/', function($request, $response, $arguments) {
			$stock = $this->model->producto->stockByClave();
			$titulo = "EXISTENCIAS POR CLAVE DE DONADOR";
			$params = array('vista' => $titulo);
        	$params['registros'] = $stock;
			return $this->view->render($response, 'rptExistencias.php', $params);
		});

		// Obtener stock por clave (pdf)
		$this->get('stockByClave2/print/{fecha}', function($request, $response, $arguments) {
			$titulo = "EXISTENCIAS POR CLAVE DE DONADOR";
			$params = array('vista' => $titulo);
			$params['sub'] = $arguments['fecha'];
        	$params['registros'] = [];
			return $this->view->render($response, 'rptExistencias2.php', $params);
		});

		// Agregar producto
		$this->post('add/', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$data = [
				'fk_proveedor'=>$parsedBody['fk_proveedor'], 
				'codigo_barras'=>$parsedBody['codigo_barras'], 
				'descripcion'=>$parsedBody['descripcion'], 
				'presentacion'=>$parsedBody['presentacion'], 
				'tipo'=>$parsedBody['tipo'], 
				'peso'=>$parsedBody['peso'], 
				'precio'=>$parsedBody['precio'],
				'esDespensa'=>$parsedBody['esDespensa'],
				'contenido'=>$parsedBody['contenido']
			];
			$producto = $this->model->producto->add($data);
			if($producto->response){
				$producto_id = $producto->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo producto', $producto_id, 'cat_producto'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); return $response->withJson($seg_log);
				}
				if($parsedBody['esDespensa']==1){
					$data_paquete = [
						'fk_producto'=>$producto_id, 
						'codigo_barras'=>$parsedBody['codigo_barras'], 
						'contenido'=>$parsedBody['contenido']
					];
					$producto_paquete = $this->model->producto_paquete->add($data_paquete);
					if($producto_paquete->response){
						$producto_paquete_id = $producto_paquete->result;
						$seg_log = $this->model->seg_log->add('Registro nuevo producto_paquete', $producto_paquete_id, 'cat_producto_paquete'); 
						if(!$seg_log->response){
								$seg_log->state = $this->model->transaction->regresaTransaccion(); return $response->withJson($seg_log);
						}
					}
				}
			}else{
				$producto->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($producto); 
			}
			$producto->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($producto);
		});

		// Editar producto
		$this->put('edit/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$producto_id = $arguments['id'];
			$dataProducto = [ 
				'fk_proveedor'=>$parsedBody['fk_proveedor'], 
				'codigo_barras'=>$parsedBody['codigo_barras'], 
				'descripcion'=>$parsedBody['descripcion'], 
				'presentacion'=>$parsedBody['presentacion'], 
				'tipo'=>$parsedBody['tipo'],
				'peso'=>$parsedBody['peso'],
				'precio'=>$parsedBody['precio'],
				'esDespensa'=>$parsedBody['esDespensa'],
				'contenido'=>$parsedBody['contenido']
			];
			$infoProducto = $this->model->producto->get($producto_id)->result; 
			$areTheSame = true;
			foreach($dataProducto as $name => $value) { 
				if($infoProducto->$name != $value) { 
					$areTheSame = false; break; 
				}
			}
			$producto = $this->model->producto->edit($dataProducto, $producto_id); 
			if($producto->response || $areTheSame) {
				$seg_log = $this->model->seg_log->add('Actualización información producto', $producto_id, 'cat_producto', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}

				if($parsedBody['esDespensa']==1){
					$del_producto_paquete = $this->model->producto_paquete->del($producto_id);
					if($del_producto_paquete->response){
						$data_paquete = [
							'fk_producto'=>$producto_id, 
							'codigo_barras'=>$parsedBody['codigo_barras'], 
							'contenido'=>$parsedBody['contenido']
						];
						$producto_paquete = $this->model->producto_paquete->add($data_paquete);
						if($producto_paquete->response){
							$producto_paquete_id = $producto_paquete->result;
							$seg_log = $this->model->seg_log->add('Registro alta producto_paquete', $producto_paquete_id, 'cat_producto_paquete'); 
							if(!$seg_log->response){
									$seg_log->state = $this->model->transaction->regresaTransaccion(); return $response->withJson($seg_log);
							}
						}
					}
				}else{
					$del_producto_paquete = $this->model->producto_paquete->del($producto_id);
					if($del_producto_paquete->response){
						$seg_log = $this->model->seg_log->add('Registro baja producto_paquete', $producto_id, 'cat_producto_paquete'); 
							if(!$seg_log->response){
									$seg_log->state = $this->model->transaction->regresaTransaccion(); return $response->withJson($seg_log);
							}
					}
				}

				$producto->SetResponse(true, 'Producto actualizado');
			}else{
				$producto->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($producto); 
			}
			$producto->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($producto);
		});

		// Eliminar producto
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$producto_id = $arguments['id'];
			$del_producto = $this->model->producto->del($producto_id); 
			if($del_producto->response) {	
				$seg_log = $this->model->seg_log->add('Baja de producto', $producto_id, 'cat_producto'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			} else { $del_producto->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_producto); 
			}
			$del_producto->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_producto);
		});

		// Obtener producto por id
		$this->get('get/{id_producto}', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->get($arguments['id_producto']));
		});

		// Buscar producto
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->find($arguments['busqueda']));
		});

		// Obtener pdf
		$this->get('stock/{alm}', function($request, $response, $arguments) {
			$productos = $this->model->producto->getStockByAlm($arguments['alm'])->result;
			$titulo = "EXISTENCIAS DE PRODUCTOS";
			if($arguments['alm'] == 1) $titulo .= ' TIENDITA';
			if($arguments['alm'] == 2) $titulo .= ' COMUNIDADES';
			if($arguments['alm'] == 3) $titulo .= ' PRODUCCION';
			$params = array('vista' => $titulo);
        	$params['registros'] = $productos;
			return $this->view->render($response, 'rptStock.php', $params);
		});

		// xlsx existencias
		$this->get('stock/xlsx/', function($request, $response, $arguments){
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    		$subtitulo = "Al ".date('d')." de ".$arrMes[intval(date('m'))]." de ".date('Y')." ".date('H:i:s');

			$sheet->setCellValue("A1", 'EXISTENCIAS DE PRODUCTOS');
			$sheet->setCellValue("E1", $subtitulo);

			$sheet->setCellValue("A2", 'Producto');
			$sheet->setCellValue("B2", 'Donador');
			$sheet->setCellValue("C2", "Present");
			$sheet->setCellValue("D2", 'Tipo');
			$sheet->setCellValue("E2", 'Peso');
			$sheet->setCellValue("F2", 'Stock');
			$sheet->setCellValue("G2", 'Exist (Kg)');

			$productos = $this->model->producto->getStockByAlm('0')->result;

			$fila = 3;
			foreach($productos as $res){
				$exist = $res->stock * $res->peso;
				$sheet->setCellValue("A".$fila, $res->descripcion);
				$sheet->setCellValue("B".$fila, $res->proveedor);
				$sheet->setCellValue("C".$fila, $res->presenta);
				$sheet->setCellValue("D".$fila, $res->tipos);
				$sheet->setCellValue("E".$fila, $res->peso);
				$sheet->setCellValue("F".$fila, $res->stock);
				$sheet->setCellValue("G".$fila, number_format($exist,3));
				$fila++;
			}
			/* $writer = new Csv($spreadsheet);
			$writer->setUseBOM(true);
			header('Content-Type: text/csv');
			header("Content-Disposition: attachment; filename=\"exist_almacen_".date('YmdHi').".csv\"");
			$writer->save('php://output'); */
			$writer = new Xlsx($spreadsheet);
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=\"exist_almacen_".date('YmdHi').".xlsx\"");
			$writer->save('php://output');
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->producto->findBy($args['f'], $args['v'])));			
		});

		$this->get('getByProv/{codigo}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->producto->getByProv($arguments['codigo'], $arguments['prov']));
		});
		
	//})->add( new MiddlewareToken());
	});
?>