<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class EntradaTienditaModel {
		private $db;
		private $table = 'entrada_tiendita';
		private $tableDE = 'det_entrada_tiendita';
        private $tableP = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Obtener todas las entradas_tiendita
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fecha, $this->table.peso_total, $this->table.fk_cajero, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf")
                ->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fecha, $this->table.peso_total, $this->table.fk_cajero) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.fecha DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fecha, $this->table.peso_total, $this->table.fk_cajero) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Obtener entradas_tiendita por cajero entre fechas
        public function getByDate($inicio, $fin, $cajero) {
            $cajeroo="";
			if($cajero!='0'){
				$cajeroo = " AND $this->table.fk_cajero = ".$cajero." ";
			}
            return $this->response->result = $this->db->getPdo()->query(
				"SELECT $this->table.id_entrada, $this->table.fecha, $this->table.peso_total, $this->table.fk_cajero, 
                DATE_FORMAT($this->table.fecha, '%d-%m-%Y %H:%i:%s') as fechaf, 
                (SELECT SUM(cantidad) FROM $this->tableDE WHERE fk_entrada = id_entrada) AS num_prod, 
				(SELECT CONCAT(nombre, ' ', apellidos) FROM cat_usuario WHERE id_usuario = fk_cajero) AS cajero
                FROM $this->table 
                WHERE $this->table.estado = 1 
                AND DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
                $cajeroo
                ORDER BY $this->table.fecha DESC;"
			)->fetchAll();
		}

        // Obtener entrada_tiendita por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fecha, $this->table.peso_total, $this->table.fk_cajero")
				->where('id_entrada', $id)
				->fetch();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

        // Eliminar entrada_tiendita
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

		// Agregar entrada_tiendita
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
				$this->response->SetResponse(false, "catch: Add model entrada_tiendita $ex");
			}
			return $this->response;
		}

		// Editar entrada_tiendita
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
				$this->response->SetResponse(false, "catch: Edit model entrada_tiendita $ex");
			}
		}

		// Editar estado de entrada_tiendita
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
				$this->response->SetResponse(false, "catch: Edit model entrada_tiendita $ex");
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
	}
?>
