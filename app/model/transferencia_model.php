<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class TransferenciaModel {
		private $db;
		private $table = 'transferencia_tiendita';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Agregar transferencia
		public function add($data) {
            $data['fecha'] = date('Y-m-d H:i:s');
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model transferencia $ex");
			}
			return $this->response;
		}

		// Obtener transferencias entre fechas
        public function getByDate($inicio, $fin) {
            return $this->db->getPdo()->query(
				"SELECT $this->table.id_transferencia, $this->table.fk_usuario, $this->table.fk_origen, $this->table.fk_destino, $this->table.fk_producto, $this->table.cantidad, $this->table.fecha,
					(SELECT CONCAT_WS(' ', nombre, apellidos) FROM cat_usuario WHERE id_usuario = fk_usuario) AS usuario, 
					COALESCE((SELECT CONCAT_WS(' ', nombre, apellidos) FROM cat_usuario WHERE id_usuario = fk_origen), 'Almacen TIENDITA') AS origen, 
					(SELECT CONCAT_WS(' ', nombre, apellidos) FROM cat_usuario WHERE id_usuario = fk_destino) AS destino, 
					(SELECT descripcion FROM cat_producto WHERE id_producto = fk_producto) AS producto  
					FROM $this->table 
					WHERE status = 1 AND 
						DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				ORDER BY fecha DESC;
				;"
			)->fetchAll();
		}

		// Obtener transferencias por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_transferencia, $this->table.fk_usuario, $this->table.fk_origen, $this->table.fk_destino, $this->table.fk_producto, $this->table.cantidad, $this->table.fecha")
				->where('id_transferencia', $id)
				->fetch();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Eliminar transferencia
		public function del($id){
			$set = array('status' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_transferencia', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Editar transferencia
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_transferencia', $id)
					->execute();
				if($this->response->result){ 
					return $this->response->SetResponse(true); 
				}else{
					return $this->response->SetResponse(false, 'No se actualizo el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model transferencia $ex");
			}
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				->where('status', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>
