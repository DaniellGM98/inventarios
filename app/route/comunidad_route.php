<?php
	use App\Lib\Response;
	require_once './core/defines.php';
    
	$app->group('/comunidad/', function() {

		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de comunidad');
		});

        // Obtener todos las comunidades
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->comunidad->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Ruta para agregar una comunidad
		$this->post('add/', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            $dataComunidad = [
                'comunidad'=>$parsedBody['comunidad']
            ];
            $comunidad = $this->model->comunidad->add($dataComunidad);
            if($comunidad->response) {
                $id_comunidad = $comunidad->result;
                $comunidad->log = $this->model->seg_log->add('Agrega comunidad', $id_comunidad, 'cat_comunidad');
            } else {
                $comunidad->data=$dataComunidad; 
                $comunidad->state = $this->model->transaction->regresaTransaccion(); 
                return $response->withJson($comunidad); 
            }
			$comunidad->state = $this->model->transaction->confirmaTransaccion();
            return $response->withJson($comunidad->SetResponse(true,"Id registro de comunidad: $id_comunidad"));
		});

        // Eliminar comunidad
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$comunidad_id = $arguments['id'];
			$del_comunidad = $this->model->comunidad->del($comunidad_id); 
			if($del_comunidad->response) {	
				$seg_log = $this->model->seg_log->add('Baja de comunidad', $comunidad_id, 'cat_comunidad'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			} else { $del_comunidad->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_comunidad); 
			}
			$del_comunidad->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_comunidad);
		});

        // Editar comunidad
		$this->put('edit/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$comunidad_id = $arguments['id'];
			$dataComunidad = [ 
				'comunidad'=>$parsedBody['comunidad']
			];
			$infoComunidad = $this->model->comunidad->get($comunidad_id)->result; 
			$areTheSame = true;
			foreach($dataComunidad as $name => $value) { 
				if($infoComunidad->$name != $value) { 
					$areTheSame = false; break; 
				}
			}
			$comunidad = $this->model->comunidad->edit($dataComunidad, $comunidad_id); 
			if($comunidad->response || $areTheSame) {
				$seg_log = $this->model->seg_log->add('Actualización información comunidad', $comunidad_id, 'cat_comunidad', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$comunidad->SetResponse(true, 'Comunidad actualizada');
			}else{
				$comunidad->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($comunidad); 
			}
			$comunidad->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($comunidad);
		});

        // Obtener comunidad por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			return $response->withJson($this->model->comunidad->get($arguments['id']));
		});

        // Buscar comunidad
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->comunidad->find($arguments['busqueda']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
						->write(json_encode($this->model->comunidad->findBy($args['f'], $args['v'])));			
		});
	});
?>