<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class ProveedorModel {
		private $db;
		private $table = 'cat_proveedor'; 
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Agregar proveedor
		public function add($data) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result!=0) {
					$this->response->SetResponse(true, 'Id del registro: '.$this->response->result);
				}
				else {
					$this->response->SetResponse(false, 'No se inserto el registro');
				}
			}catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model proveedor");
			}
			return $this->response;
		}

		// Obtener claves de proveedores
		public function getClaves() {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("clave")
				->groupBy('clave')
				->orderBy('clave')
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(DISTINCT clave) Total')
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Ruta para obtener todos los proveedores
		public function getAll($status = 1) {
			$this->response->result = $this->db
				->from($this->table)
				// ->select(null)->select("id_proveedor, nombre, apellidos, rfc, direccion, telefono, email, clave, CONCAT_WS(' ', nombre, apellidos) as nomcom")
				->where(intval($status)==1? "estado = 1": 'TRUE')
				->orderBy('nombre')
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where(intval($status)==1? "estado = 1": 'TRUE')
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener los datos de proveedor por medio del ID
		public function get($id){
			$this->response->result = $this->db
				->from($this->table)
				// ->select(null)->select("nombre, apellidos, rfc, direccion, telefono, email, clave")
				->where('id_proveedor', $id)
				->fetch();
			if($this->response->result)	$this->response->SetResponse(true);
			else	$this->response->SetResponse(false,'No existe el registro');
			return $this->response;
		}

		// Modificar un proveedor
		public function edit($data, $id){
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_proveedor', $id)
					->execute();
				if($this->response->result!=0)	$this->response->SetResponse(true);
				else { $this->response->SetResponse(false, 'No se edito el registro'); }
			}catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model proveedor");
			}
			return $this->response;
		}

		// Eliminar un proveedor
		public function del($id) {
			try{
				$set = array('estado' => 2, 'fecha_modificacion' => date("Y-m-d H:i:s"));
				$this->response->result = $this->db
					->update($this->table)
					->set($set)
					->where('id_proveedor', $id)
					->execute();
				if($this->response->result!=0)	$this->response->SetResponse(true);
				else { $this->response->SetResponse(false, 'No se dio de baja el registro'); }
			}catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "Catch: Del model proveedor");
			}
			return $this->response;
		}

		// Buscar proveedor
		public function find($busqueda) {
			$proveedores = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', nombre, apellidos, rfc, direccion, telefono, email) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetchAll();
			$this->response->result = $proveedores;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', nombre, apellidos, rfc, direccion, telefono, email) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		}

		// Obtener claves de proveedores
		public function getCveProv() {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("clave")
				->where('estado',1)
				->groupBy('clave')
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(DISTINCT clave) Total')
				->where('estado',1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}
		
		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				->where('estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
		

		
	}
?>