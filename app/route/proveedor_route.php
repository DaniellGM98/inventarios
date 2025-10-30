<?php
	use App\Lib\Response;
	require_once './core/defines.php';
    
	$app->group('/proveedor/', function() {

		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de proveedor');
		});

        // Ruta para agregar un proveedor
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            unset($parsedBody['id']);
            $proveedor = $this->model->proveedor->add($parsedBody);
            if($proveedor->response) {
                $id_proveedor = $proveedor->result;
                $proveedor->log = $this->model->seg_log->add('Agrega proveedor', $id_proveedor, 'cat_proveedor');
            } else {
                $proveedor->data=$parsedBody; 
                $proveedor->state = $this->model->transaction->regresaTransaccion(); 
                return $response->withJson($proveedor); 
            }
			$proveedor->state = $this->model->transaction->confirmaTransaccion();
            return $response->withJson($proveedor->SetResponse(true,"Id registro de proveedor: $id_proveedor"));
		});

        // Obtener claves de proveedores
        $this->get('getClaves/', function($request, $response, $arguments) {
			return $response->withJson($this->model->proveedor->getClaves());
		});

        // Ruta para obtener todos los proveedores
        $this->get('getAll/', function($request, $response, $arguments) {
			return $response->withJson($this->model->proveedor->getAll());
		});

        // Ruta para modificar un proveedor
		$this->put('edit/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            unset($parsedBody['id']);
			$id_proveedor = $arguments['id'];
            $infoProveedor = $this->model->proveedor->get($id_proveedor)->result;
			$areTheSame = true;
            foreach($infoProveedor as $field => $value) {
                if(isset($dataProveedor[$field]) && $dataProveedor[$field] != $value) {
                    $areTheSame = false;
                    break;
                }
            }
			$proveedor = $this->model->proveedor->edit($parsedBody, $id_proveedor);
            if($proveedor->response || $areTheSame){
                $proveedor->SetResponse(true);
				//$proveedor->log = 
                $add = $this->model->seg_log->add('Actualiza información proveedor', $id_proveedor, 'cat_proveedor');
                if(!$add->response){
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($add); 
				}
            }else{
                $proveedor->data=$parsedBody;
                $proveedor->orgData = $infoProveedor;
                $proveedor->state = $this->model->transaction->regresaTransaccion();
                return $response->withJson($proveedor);
            }
			$proveedor->state = $this->model->transaction->confirmaTransaccion();
            return $response->withJson($proveedor->SetResponse(true));
		});

        // Ruta para dar de baja un proveedor
		$this->put('del/{id}', function($request, $response, $arguments) {
            $this->model->transaction->iniciaTransaccion();
            $del = $this->model->proveedor->del($arguments['id']);
            if($del){
                $seg_log = $this->model->seg_log->add('Baja de proveedor',$arguments['id'], 'cat_proveedor'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
            }
            $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del);
		});

        // Obtener proveedor por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->proveedor->get($arguments['id']));
		});

        // Buscar proveedor
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->proveedor->find($arguments['busqueda']));
		});

        // Obtener claves
        $this->get('getCveProv/', function($request, $response, $arguments) {
			return $response->withJson($this->model->proveedor->getCveProv());
		});

        // Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->proveedor->findBy($args['f'], $args['v'])));			
		});


		
	});
?>