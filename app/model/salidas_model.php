<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class SalidaModel {
		private $db;
		private $table = 'salida';
        private $tableC = 'cat_cliente';
        private $tableU = 'cat_usuario';
		private $tableDS = 'det_salida';
        private $tableP = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todas las salidas
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.fecha, $this->table.peso_total, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, CONCAT($this->tableC.nombre, ' ', $this->tableC.apellidos) as cliente")
				->innerJoin("$this->tableC ON $this->tableC.id_cliente = $this->table.fk_cliente")
				->where("CONCAT_WS(' ', $this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.fecha, $this->table.peso_total, $this->table.estado) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.fecha DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->innerJoin("$this->tableC ON $this->tableC.id_cliente = $this->table.fk_cliente")
				->where("CONCAT_WS(' ', $this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.fecha, $this->table.peso_total, $this->table.estado) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

        // Obtener salidas por fecha
        public function getByDate($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT $this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.peso_total, CONCAT_WS(' ', 
						CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, fecha,
						CONCAT($this->tableC.nombre, ' ', $this->tableC.apellidos) as cliente, 
						(SELECT CASE $this->table.fk_cajero WHEN 0 THEN '' ELSE (SELECT CONCAT($this->tableU.nombre, ' ', $this->tableU.apellidos) FROM $this->tableU WHERE $this->tableU.id_usuario = $this->table.fk_cajero) END) AS cajero
				FROM $this->table, $this->tableC 
				WHERE $this->tableC.id_cliente = $this->table.fk_cliente 
				AND $this->table.estado = 1
				AND DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				ORDER BY $this->table.fecha DESC;"
			)->fetchAll();
		}

        // Agregar salida
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
				$this->response->SetResponse(false, "catch: Add model salida $ex");
			}
			return $this->response;
		}

        // Editar salida
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_salida', $id)
					->execute();
				if($this->response->result){ 
					return $this->response->SetResponse(true); 
				}else{
					return $this->response->SetResponse(false, 'No se actualizo el registro salida');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model salida $ex");
			}
		}

        // Obtener salida por id
		public function get($id) {
			$salida = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.fecha, $this->table.peso_total, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf")
				->where('id_salida', $id)
				->fetch();
			if($salida) {
				$this->response->result = $salida;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

         // Eliminar salida
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_salida', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

        // Buscar salida
		public function find($busqueda) {
			$salidas = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_salida, $this->table.fk_cliente, $this->table.fk_cajero, $this->table.fecha, $this->table.peso_total, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf, CONCAT($this->tableC.nombre, ' ', $this->tableC.apellidos) as cliente")
				->innerJoin("$this->tableC ON $this->tableC.id_cliente = $this->table.fk_cliente")
				->where("CONCAT_WS(' ', $this->table.fk_cliente, $this->table.fecha, $this->table.peso_total, $this->table.estado) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->fetchAll();
			$this->response->result = $salidas;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', $this->table.fk_cliente, $this->table.fecha, $this->table.peso_total, $this->table.estado) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		}

		// Obtener reporte por clave de proveedor entre fechas
        public function fk_entrada($terminacion, $field, $_peso_total, $value, $_cajero) {
			$data['fecha'] = date('Y-m-d H:i:s');
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			$data['peso_total'] = $_peso_total;
			if($terminacion == 'entrada_tiendita') $data['fk_cajero'] = $_cajero;
			if($field!=""){
				$data[$field] = $value;
			}
			try{
				$fk_entrada = $this->db
					->insertInto($terminacion, $data)
					->execute();
				if($fk_entrada!=0) { 
					$this->response->id_entrada = $fk_entrada;
					$this->response->SetResponse(true); 
				}else{
					$this->response->SetResponse(false, 'No se agrego el registro');
				}
			}catch(\PDOException $ex){
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model entrada $ex");
			}
			return $this->response;
		}

		// Obtener stock por kardex, producto, fecha, condicion
        public function stockByKardex($terminacion, $_fk_producto, $fecha, $condicion, $_cajero) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada_comunidad'
							?	'kardex_comunidad'
							:	'kardex'));
			return $this->db->getPdo()->query(
				"SELECT * FROM $kardexBy WHERE fecha='$fecha' AND fk_producto=$_fk_producto $condicion;"
			)->fetchAll();
			
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
