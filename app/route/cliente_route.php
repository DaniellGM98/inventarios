<?php
	use App\Lib\Response;

	$app->group('/cliente/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de cliente');
		});

        // Obtener todos los clientes
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->cliente->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Agregar cliente
        $this->post('add/', function ($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$data = [
				'nombre'=>$parsedBody['nombre'], 
				'apellidos'=>$parsedBody['apellidos'], 
				'direccion'=>$parsedBody['direccion'], 
				'telefono'=>$parsedBody['telefono'], 
				'email'=>$parsedBody['email']
			];
			$cliente = $this->model->cliente->add($data);
			if($cliente->response){
				$cliente_id = $cliente->result;
				$seg_log = $this->model->seg_log->add('Registro nuevo cliente', $cliente_id, 'cat_cliente'); 
				if(!$seg_log->response){
						$seg_log->state = $this->model->transaction->regresaTransaccion(); return $response->withJson($seg_log);
				}
			}else{
				$cliente->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($cliente); 
			}
			$cliente->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($cliente);
		});

        // Editar cliente
		$this->put('edit/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$cliente_id = $arguments['id'];
			$dataCliente = [ 
				'nombre'=>$parsedBody['nombre'], 
				'apellidos'=>$parsedBody['apellidos'], 
				'direccion'=>$parsedBody['direccion'], 
				'telefono'=>$parsedBody['telefono'], 
				'email'=>$parsedBody['email']
			];
			$infoCliente = $this->model->cliente->get($cliente_id)->result; 
			$areTheSame = true;
			foreach($dataCliente as $name => $value) { 
				if($infoCliente->$name != $value) { 
					$areTheSame = false; break; 
				}
			}
			$cliente = $this->model->cliente->edit($dataCliente, $cliente_id); 
			if($cliente->response || $areTheSame) {
				$seg_log = $this->model->seg_log->add('Actualización información cliente', $cliente_id, 'cat_cliente', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$cliente->SetResponse(true, 'Cliente actualizado');
			}else{
				$cliente->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($cliente); 
			}
			$cliente->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($cliente);
		});

        // Eliminar cliente
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$cliente_id = $arguments['id'];
			$del_cliente = $this->model->cliente->del($cliente_id); 
			if($del_cliente->response) {	
				$seg_log = $this->model->seg_log->add('Baja de cliente', $cliente_id, 'cat_cliente'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			} else { $del_cliente->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_cliente); 
			}
			$del_cliente->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_cliente);
		});

        // Obtener cliente por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->cliente->get($arguments['id']));
		});
        
        // Buscar cliente
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->cliente->find($arguments['busqueda']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->cliente->findBy($args['f'], $args['v'])));			
		});

	});
?>