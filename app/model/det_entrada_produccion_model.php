<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class DetentradaProduccionModel {
		private $db;
		private $table = 'det_entrada_produccion';
		private $tableE = 'entrada';
		private $tableEP = 'entrada_produccion';
		private $tableProd = 'cat_producto';
		private $tableProv = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener det_entrada por entrada
		public function getByEntrada($id) {
			$entrada = $this->db
				->from($this->table)
				->innerJoin('cat_producto ON id_producto = fk_producto')
				->innerJoin('cat_proveedor ON id_proveedor = fk_proveedor')
				->select(null)->select("$this->table.id_det_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, 
								cat_producto.codigo_barras, cat_producto.descripcion AS producto, CONCAT(nombre, ' ', apellidos) AS proveedor")
				->where('fk_entrada', $id)
				->where($this->table.'.estado', 1)
				->fetchAll();
			if($entrada) {
				return $entrada;
				/* $this->response->result = $entrada;
				return $this->response->SetResponse(true); */
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Agregar det_entrada_produccion
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
				$this->response->SetResponse(false, "catch: Add model det_entrada_produccion $ex");
			}
			return $this->response;
		}

        // Editar det_entrada_produccion
		public function editByEntrada($data, $id, $idProducto) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_entrada', $id)
					->where('fk_producto', $idProducto)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model det_entrada_produccion $ex");
			}
			return $this->response;
		}

        // Obtener det_entrada_produccion por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				->select(null)->select("$this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion")
				->innerJoin("$this->tableProd ON $this->tableProd.id_producto = $this->table.fk_producto")
				->where("$this->table.fk_entrada", $id)
				->fetchAll();
			if($usuario) {
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener entradas_produccion por id
        public function getById($id) {
			return $this->response->result = $this->db->getPdo()->query(
				"SELECT fk_entrada, fk_producto, cantidad, (SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM entrada_produccion WHERE id_entrada = fk_entrada) AS fecha 
				FROM det_entrada_produccion 
				WHERE id_det_entrada = $id;"
			)->fetch();
		}

		// Eliminar det_entrada_produccion
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_det_entrada', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Obtener det_entrada_produccion por entrada_produccion
		public function getPesoTotal($entrada) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(peso),3) as peso_total FROM det_entrada_produccion WHERE fk_entrada = $entrada and estado=1"
			)->fetch();
		}

		/* // Obtener reporte por proveedor entre fechas
        public function getRpt($inicio, $fin, $prov) {
			return $this->response->result = $this->db->getPdo()->query(
				"SELECT $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso,
						$this->tableProd.descripcion, $this->tableE.nota_entrada, $this->tableE.fk_proveedor, 
						DATE_FORMAT($this->tableE.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
						CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, 
						(SELECT SUM(replace($this->tableEP.peso_total,',','')) FROM $this->tableEP 
						WHERE $this->tableEP.fk_proveedor = $prov 
								AND DATE_FORMAT($this->tableEP.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
								AND $this->tableEP.estado = 1)
						 as peso_total 
				FROM $this->table, $this->tableProd, $this->tableE, $this->tableProv
				WHERE $this->table.fk_producto = $this->tableProd.id_producto 
				AND $this->table.fk_entrada = $this->tableE.id_entrada
				AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') >= $inicio AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') <= $fin
				AND $this->tableE.fk_proveedor = $this->tableProv.id_proveedor
				AND $this->tableE.fk_proveedor = $prov
				AND $this->tableE.estado = 1
				ORDER BY CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos);"
			)->fetchAll();
		} */

		// Obtener reporte por clave entre fechas
        public function getRptCve($inicio, $fin, $cve) {
			$clave="";
			if($cve != 'Todas'){
				$clave = " AND $this->tableProv.clave = '$cve' ";
			}
			return $this->db->getPdo()->query(
				"SELECT $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, 
				$this->tableProd.descripcion, $this->tableProd.fk_proveedor, 
				DATE_FORMAT($this->tableEP.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
				CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, 
				(SELECT SUM(replace($this->tableEP.peso_total,',','')) FROM $this->tableEP 
				WHERE DATE_FORMAT($this->tableEP.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') 
				AND $this->tableEP.estado = 1) as peso_total , fecha
				FROM $this->table, $this->tableProd, $this->tableEP, $this->tableProv
				WHERE $this->table.fk_producto = $this->tableProd.id_producto 
				AND $this->table.fk_entrada = $this->tableEP.id_entrada
				AND DATE_FORMAT($this->tableEP.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableProd.fk_proveedor = $this->tableProv.id_proveedor
				AND $this->tableEP.estado = 1
				AND $this->table.estado = 1
				$clave
				ORDER BY $this->tableEP.fecha
				;"
			)->fetchAll();
		}

		// Obtener todas las det_entrada_produccion
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion")
				->innerJoin("$this->tableProd ON $this->tableProd.id_producto = $this->table.fk_producto")
                ->where("CONCAT_WS(' ', $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->innerJoin("$this->tableProd ON $this->tableProd.id_producto = $this->table.fk_producto")
				->where("CONCAT_WS(' ', $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
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
