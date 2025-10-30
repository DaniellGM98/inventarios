<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response,
		Envms\FluentPDO\Literal;
	require_once './core/defines.php';

	class ProductoPaqueteModel {
		private $db;
		private $table = 'cat_producto_paquete';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener producto_paquete por id
		public function get($id, $fecha) {
			$producto = $this->db
				->from($this->table)
				->where('fk_producto', $id)
				->where('fecha <= ?', $fecha)
				->orderBy('fecha DESC')
				->limit(1)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}else{
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Agregar producto_paquete
		public function add($data) {
			date_default_timezone_set('America/Mexico_City');
            $data['fecha'] = date('Y-m-d H:i:s');
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result) { 
					$this->response->SetResponse(true); 
				}else{
					$this->response->SetResponse(false, 'No se agrego el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model producto_paquete $ex");
			}
			return $this->response;
		}

		// Editar producto_paquete
		// public function edit($data, $id) {
		// 	date_default_timezone_set('America/Mexico_City');
		// 	$data['fecha_modificacion'] = date('Y-m-d H:i:s');
		// 	try{
		// 		$this->response->result = $this->db
		// 			->update($this->table, $data)
		// 			->where('id_producto_paquete', $id)
		// 			->execute();
		// 		if($this->response->result) { 
		// 			$this->response->SetResponse(true); 
		// 		}else{
		// 			$this->response->SetResponse(false, 'No se actualizo el registro');
		// 		}
		// 	} catch(\PDOException $ex) {
		// 		$this->response->errors = $ex;
		// 		$this->response->SetResponse(false, "catch: Edit model producto_paquete $ex");
		// 	}
		// 	return $this->response;
		// }

		// Eliminar producto_paquete
		public function del($id){
			date_default_timezone_set('America/Mexico_City');
			$set = array('estado' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('fk_producto', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		/* //Consulta directa
		$this->response->result = $this->db->getPdo()->query(
					"SELECT
						$this->tableKT.fk_producto
						
						IFNULL((SELECT SUM(cantidad) FROM $this->tableDE WHERE producto_id=$this->table.id AND ($this->tableDE.caducidad IS NULL || DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m%-%d'), INTERVAL 10 DAY) < $this->tableDE.caducidad)), 0) AS stockSinCaducar,
						
					FROM producto 
					WHERE $this->table.status = 1
					GROUP BY $this->table.id
					ORDER BY $orden
					LIMIT $inicial, $limite;"
				)->fetchAll(); */
	}
?>