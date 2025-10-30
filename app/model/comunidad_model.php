<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class ComunidadModel {
		private $db;
		private $table = 'cat_comunidad'; 
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Obtener todos las comunidades
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("id_comunidad, comunidad")
				->where("CONCAT_WS(' ', id_comunidad, comunidad) LIKE '%$busqueda%'")
				->limit("$inicial, $limite")
				->where('estado', 1)
				->orderBy("$this->table.comunidad")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', id_comunidad, comunidad) LIKE '%$busqueda%'")
				->limit("$inicial, $limite")
				->where('estado', 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Agregar comunidad
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
				$this->response->SetResponse(false, "catch: Add model comunidad");
			}
			return $this->response;
		}

        // Eliminar comunidad
		public function del($id){
			// $this->response->result = $this->db
			// 	->delete($this->table)
			// 	->where('id_comunidad', $id)
			// 	->execute();
			// if($id!=0){
			// 	return $this->response->SetResponse(true, "Id baja: $id");
			// }else{
			// 	return $this->response->SetResponse(true, "Id incorrecto");
			// }
			$data = array(
				'estado' => 2,
				'fecha_modificacion' => date('Y-m-d H:i:s')
			);
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_comunidad', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model comunidad $ex");
			}
			return $this->response;
		}

        // Editar comunidad
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_comunidad', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model comunidad $ex");
			}
			return $this->response;
		}

        // Obtener los datos de comunidad por medio del ID
		public function get($id){
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("comunidad")
				->where('id_comunidad', $id)
				->fetch();
			if($this->response->result)	$this->response->SetResponse(true);
			else	$this->response->SetResponse(false,'No existe el registro');
			return $this->response;
		}

        // Buscar proveedor
		public function find($busqueda) {
			$proveedores = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', comunidad) LIKE '%$busqueda%'")
				->fetchAll();
			$this->response->result = $proveedores;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', comunidad) LIKE '%$busqueda%'")
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