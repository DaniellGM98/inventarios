<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class DetsalidaModel {
		private $db;
		private $table = 'det_salida';
		private $tableS = 'salida';
		private $tableProd = 'cat_producto';
		private $tableCli = 'cat_cliente';
		private $tableProv = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todas las det_salida
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso")
				->where("CONCAT_WS(' ', $this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.fecha_modificacion DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener det_salida por salida
		public function getBySalida($id) {
			$salida = $this->db
				->from($this->table)
				->innerJoin('cat_producto ON id_producto = fk_producto')
				->innerJoin('cat_proveedor ON id_proveedor = fk_proveedor')
				->select(null)->select("$this->table.id_det_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, 
								cat_producto.codigo_barras, cat_producto.descripcion AS producto, CONCAT(nombre, ' ', apellidos) AS proveedor")
				->where('fk_salida', $id)
				->where($this->table.'.estado', 1)
				->fetchAll();
			if($salida) {
				$this->response->result = $salida;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Editar det_salida
		public function editBySalida($data, $id, $idProducto) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_salida', $id)
					->where('fk_producto', $idProducto)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model det_salida $ex");
			}
			return $this->response;
		}

		// Agregar det_salida
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
				$this->response->SetResponse(false, "catch: Add model det_salida $ex");
			}
			return $this->response;
		}

		// Obtener salidas por id
        public function getById($id) {
			return $this->db->getPdo()->query(
				"SELECT fk_salida, fk_producto, cantidad, (SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM salida WHERE id_salida = fk_salida) AS fecha FROM det_salida WHERE id_det_salida = $id;"
			)->fetch();
		}

		// Eliminar det_salida
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_det_salida', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Obtener det_salida por salida
		public function getPesoTotal($salida) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(peso),3) as peso_total FROM det_salida WHERE fk_salida = $salida and estado=1"
			)->fetch();
		}

		// Obtener reporte entre fechas
        public function getRpt($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT $this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion, $this->tableS.fk_cliente,
				 DATE_FORMAT($this->tableS.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
				 CONCAT($this->tableCli.nombre, ' ', $this->tableCli.apellidos) as cliente, 
				(SELECT SUM(replace($this->tableS.peso_total,',','')) FROM $this->tableS WHERE 
				DATE_FORMAT($this->tableS.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableS.estado = 1) as peso_total, 
				(SELECT CONCAT(nombre, ' ', apellidos) FROM $this->tableProv WHERE id_proveedor = $this->tableProd.fk_proveedor) AS proveedor , fecha
				FROM $this->table, $this->tableProd, $this->tableS, $this->tableCli
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_salida = $this->tableS.id_salida
				AND $this->tableS.fk_cliente = $this->tableCli.id_cliente
				AND DATE_FORMAT($this->tableS.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableS.estado = 1
				AND $this->table.estado = 1
				ORDER BY $this->tableS.fecha;"
			)->fetchAll();
		}

		// Obtener reporte por proveedor entre fechas
        public function getRptProv($inicio, $fin, $prov) {
			$provv="";
			if($prov!='0'){
				$provv = " $this->tableProd.fk_proveedor = ".$prov." AND ";
			}
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->tableS.fecha,'%d-%m-%Y') AS fecha, $this->tableProd.descripcion AS producto, 
						$this->tableProd.peso AS pesou, $this->table.cantidad, $this->table.peso, CONCAT($this->tableCli.nombre, ' ', $this->tableCli.apellidos) AS cliente, 
						CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) AS proveedor 
					FROM $this->table, $this->tableProd, $this->tableS, $this->tableCli, $this->tableProv 
					WHERE 
					$provv
					$this->table.fk_salida = $this->tableS.id_salida 
					AND $this->table.fk_producto = $this->tableProd.id_producto 
					AND $this->tableCli.id_cliente = $this->tableS.fk_cliente 
					AND $this->tableProv.id_proveedor = $this->tableProd.fk_proveedor 
					AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN '$inicio' AND '$fin' 
					AND $this->tableS.estado = 1
					AND $this->table.estado = 1
					ORDER BY proveedor, producto, cliente, fecha
				;"
			)->fetchAll();
		}

		// Obtener reporte por clave entre fechas
        public function getRptCve($inicio, $fin, $cve) {
			$clave="";
			if($cve!='0'){
				$clave = " AND clave = '$cve' ";
			}
			return $this->db->getPdo()->query(
				"SELECT clave, CONCAT(nombre,' ',apellidos) AS proveedor, 
				SUM(REPLACE(peso,',','')) AS peso_total  
				FROM $this->table, $this->tableProv, $this->tableS 
				WHERE id_salida = fk_salida AND 
				id_proveedor = (SELECT fk_proveedor FROM $this->tableProd WHERE id_producto = fk_producto) 
				AND $this->tableS.estado = 1 
				AND $this->table.estado = 1
				$clave 
				AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				GROUP BY proveedor 
				ORDER BY clave, proveedor;"
			)->fetchAll();
		}

		// Obtener reporte por cliente entre fechas
        public function getRptCli($inicio, $fin, $cliente) {
			$clientee="";
			if($cliente!='0'){
				$clientee = " $this->tableS.fk_cliente = ".$cliente." AND ";
			}
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->tableS.fecha,'%d-%m-%Y') AS fecha, $this->tableProd.descripcion AS producto, 
				$this->tableProd.peso AS pesou, $this->table.cantidad, $this->table.peso, CONCAT($this->tableCli.nombre, ' ', $this->tableCli.apellidos) AS cliente, 
				CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) AS proveedor 
				FROM $this->table, $this->tableProd, $this->tableS, $this->tableCli, $this->tableProv 
				WHERE 
				$clientee
				$this->table.fk_salida = $this->tableS.id_salida 
				AND $this->table.fk_producto = $this->tableProd.id_producto 
				AND $this->tableCli.id_cliente = $this->tableS.fk_cliente 
				AND $this->tableProv.id_proveedor = $this->tableProd.fk_proveedor 
				AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableS.estado = 1
				AND $this->table.estado = 1
				ORDER BY cliente, producto, proveedor, fecha
				;"
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
