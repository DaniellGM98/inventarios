<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class EntradaComunidadModel {
		private $db;
		private $table = 'entrada_comunidad';
		private $tableDE = 'det_entrada_comunidad';
        private $tableP = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todas las entradas_comunidad
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fecha, $this->table.peso_total, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf")
                ->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fecha, $this->table.peso_total) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.fecha DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fecha, $this->table.peso_total) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Obtener entradas_comunidad entre fechas
        public function getByDate($inicio, $fin) {
            return $this->db->getPdo()->query(
				"SELECT $this->table.id_entrada, $this->table.fecha, $this->table.peso_total, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, (SELECT SUM($this->tableDE.cantidad) FROM $this->tableDE WHERE $this->tableDE.fk_entrada = $this->table.id_entrada) AS num_prod
				FROM $this->table WHERE $this->table.estado = 1 AND DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') ORDER BY $this->table.fecha DESC;"
			)->fetchAll();
		}

        // Obtener entrada_comunidad por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fecha, $this->table.peso_total")
				->where('id_entrada', $id)
				->fetch();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Eliminar entrada_comunidad
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_entrada', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Agregar entrada_comunidad
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
				$this->response->SetResponse(false, "catch: Add model entrada_comunidad $ex");
			}
			return $this->response;
		}

		// Editar entrada_comunidad
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_entrada', $id)
					->execute();
				if($this->response->result){ 
					return $this->response->SetResponse(true); 
				}else{
					return $this->response->SetResponse(false, 'No se actualizo el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model entrada_comunidad $ex");
			}
		}

		// Editar estado de entrada_comunidad
		public function editEstado($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_entrada', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model entrada_comunidad $ex");
			}
			return $this->response;
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

        /*  

        

        

		// Editar estado por tipo de entrada
		public function editEstadoByEntrada($terminacion, $data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($terminacion, $data)
					->where('id_entrada', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model entrada $ex");
			}
			return $this->response;
		}

		

		// Buscar entrada
		public function find($busqueda) {
			$productos = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fk_proveedor, 
				CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor")
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fk_proveedor, $this->table.fecha, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetchAll();
			$this->response->result = $productos;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fk_proveedor, $this->table.fecha, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		} */

	}
?>
