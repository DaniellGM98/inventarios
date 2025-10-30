<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class ClienteModel {
		private $db;
		private $table = 'cat_cliente'; 
		private $tableU = 'cat_usuario'; 
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todos los clientes
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				// ->select(null)->select("id_cliente, nombre, apellidos, direccion, telefono, email, CONCAT_WS(' ', nombre, apellidos) as nomcom")
				->where("CONCAT_WS(' ', id_cliente, nombre, apellidos, direccion, telefono, email) LIKE '%$busqueda%'")
				->where("estado", 1)
				->limit("$inicial, $limite")
				->orderBy("CONCAT_WS(' ', nombre, apellidos)")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
                ->where("CONCAT_WS(' ', id_cliente, nombre, apellidos, direccion, telefono, email) LIKE '%$busqueda%'")
				->where('estado', 1)
                ->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Agregar cliente
		public function add($data) {
            $data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model cliente $ex");
			}
			return $this->response;
		}

        // Obtener cliente por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				// ->select(null)->select("nombre, apellidos, direccion, telefono, email")
				->where('id_cliente', $id)
				->fetch();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

        // Editar cliente
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_cliente', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model cliente $ex");
			}
			return $this->response;
		}

        // Eliminar cliente
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_cliente', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

        // Buscar producto
		public function find($busqueda) {
			$productos = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', nombre, apellidos, direccion, telefono, email) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetchAll();
			$this->response->result = $productos;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', nombre, apellidos, direccion, telefono, email) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
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
