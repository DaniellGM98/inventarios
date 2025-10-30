<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class EntradaModel {
		private $db;
		private $table = 'entrada';
		private $tableDE = 'det_entrada';
        private $tableP = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todas las entrada
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fk_proveedor, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, CONCAT_WS(' ', $this->tableP.nombre, $this->tableP.apellidos) as proveedor, IF($this->table.tipo = 1, 'Donación', 'Compra') as tipoEnt")
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fk_proveedor, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.nota_entrada")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("CONCAT_WS(' ', $this->table.id_entrada, $this->table.fk_proveedor, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Obtener entradas entre fechas
        public function getByDate($inicio, $fin) {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_entrada, $this->table.fk_proveedor, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, CONCAT_WS(' ', $this->tableP.nombre, $this->tableP.apellidos) as proveedor, IF($this->table.tipo = 1, 'Donación', 'Compra') as tipoEnt, fecha")
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')")
				->where("$this->table.estado", 1)
				->orderBy("$this->table.nota_entrada")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')")
				->where("$this->table.estado", 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Obtener entrada por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				// ->select(null)->select("$this->table.fk_proveedor, $this->table.fecha, $this->table.nota_entrada, $this->table.peso_total, $this->table.tipo, $this->table.valor")
				->where('id_entrada', $id)
				->fetch();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

        // Agregar entrada
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
				$this->response->SetResponse(false, "catch: Add model entrada $ex");
			}
			return $this->response;
		}

        // Editar entrada
		public function edit($data, $id) {
			//$data['fecha'] = date('Y-m-d H:i:s');
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
				$this->response->SetResponse(false, "catch: Edit model entrada $ex");
			}
			return $this->response;
		}

		// Editar estado de entrada
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
				$this->response->SetResponse(false, "catch: Edit model entrada $ex");
			}
			return $this->response;
		}

		// Editar estado por tipo de entrada
		public function editEstadoByEntrada($terminacion, $data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($terminacion, $data)
					->where('id_entrada', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro '.$terminacion);
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model entrada $ex");
			}
			return $this->response;
		}

		// Eliminar entrada
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
