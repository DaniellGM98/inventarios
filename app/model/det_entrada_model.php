<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class DetentradaModel {
		private $db;
		private $table = 'det_entrada';
		private $tableE = 'entrada';
		private $tableProd = 'cat_producto';
		private $tableProv = 'cat_proveedor';
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
				->select(null)->select("$this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso")
				->where("CONCAT_WS(' ', $this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Agregar det_entrada
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
				$this->response->SetResponse(false, "catch: Add model det_entrada $ex");
			}
			return $this->response;
		}

		// Agregar det_entrada por tipo
		public function addTipo($terminacion, $data) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'det_entrada_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'det_entrada_produccion'
					:	($terminacion=='entrada_comunidad'
							?	'det_entrada_comunidad'
							:	'det_entrada'));
            $data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($kardexBy, $data)
					->execute();
				if($this->response->result) { 
					return $this->response->SetResponse(true); }
				else	return $this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, "catch: Add model $kardexBy $ex");
			}
			//return $this->response;
		}

		// Obtener det_entrada por id
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

		// Obtener det_entrada por entrada
		public function getByEntrada($id) {
			$entrada = $this->db
				->from($this->table)
				->innerJoin('cat_producto ON id_producto = fk_producto')
				->select(null)->select("$this->table.id_det_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, cat_producto.codigo_barras, cat_producto.descripcion AS producto")
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

		// Obtener entradas por id
        public function getById($id) {
			return $this->db->getPdo()->query(
				"SELECT fk_entrada, fk_producto, cantidad,
				(SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM entrada WHERE id_entrada = fk_entrada) AS fecha 
				FROM det_entrada WHERE id_det_entrada = $id;"
			)->fetch();
		}

		// Eliminar det_entrada
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

		// Obtener det_entrada por entrada
		public function getPesoTotal($entrada) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(peso),3) as peso_total FROM det_entrada WHERE fk_entrada = $entrada and estado=1"
			)->fetch();
		}

		// Editar det_entrada
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
				$this->response->SetResponse(false, "catch: Edit model det_entrada $ex");
			}
			return $this->response;
		}

		// Buscar entrada
		public function find($busqueda) {
			$productos = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso")
				->where("CONCAT_WS(' ', $this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetchAll();
			$this->response->result = $productos;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', $this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		}

		// Obtener reporte por clave de proveedor entre fechas
        public function getRpt($inicio, $fin, $clave) {
			$clav="";
			if($clave!='Todas'){
				$clav="AND $this->tableProv.clave = '$clave'";
			}
			return $this->db->getPdo()->query(
				"SELECT
				$this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion, $this->tableE.nota_entrada, $this->tableE.fk_proveedor, $this->tableE.valor, DATE_FORMAT($this->tableE.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, (SELECT SUM(replace($this->tableE.peso_total,',','')) FROM $this->tableE 
				WHERE DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') >= '$inicio' AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') <= '$fin' 
				AND $this->tableE.estado = 1) as peso_total, fecha
				FROM $this->table, $this->tableProd, $this->tableE, $this->tableProv
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_entrada = $this->tableE.id_entrada
				AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') >= '$inicio' AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') <= '$fin' 
				AND $this->tableE.fk_proveedor = $this->tableProv.id_proveedor
				AND $this->tableE.estado = 1
				AND $this->table.estado = 1
				$clav
				ORDER BY $this->tableE.nota_entrada;"
			)->fetchAll();
		}

		// Obtener reporte por proveedor entre fechas
        public function getRptProv($inicio, $fin, $prov) {
			$prove="";
			$prove2="";
			if($prov!=0){
				$prove = " $this->tableE.fk_proveedor = $prov AND ";
				$prove2 = " AND $this->tableE.fk_proveedor = $prov ";
			}
			return $this->db->getPdo()->query(
				"SELECT
				$this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion, $this->tableE.nota_entrada, $this->tableE.fk_proveedor, $this->tableProd.peso as pesou, DATE_FORMAT($this->tableE.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, 
				(SELECT SUM(replace($this->tableE.peso_total,',','')) FROM $this->tableE WHERE 
				$prove
				DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableE.estado = 1) as peso_total, fecha
				FROM $this->table, $this->tableProd, $this->tableE, $this->tableProv
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_entrada = $this->tableE.id_entrada
				AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableE.fk_proveedor = $this->tableProv.id_proveedor
				$prove2
				AND $this->tableE.estado = 1
				AND $this->table.estado = 1
				ORDER BY $this->table.fk_entrada"
			)->fetchAll();
		}

		// Obtener reporte por clave entre fechas
        public function getRptCve($inicio, $fin, $clave) {
			$clavee="";
			if($clave!='0'){
				$clavee = " AND $this->tableProv.clave = '$clave' ";
			}
			return $this->db->getPdo()->query(
				"SELECT $this->tableProv.clave, CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, 
				SUM(replace($this->tableE.peso_total,',','')) as peso_total
				FROM $this->tableE, $this->tableProv
				WHERE $this->tableProv.id_proveedor = $this->tableE.fk_proveedor
				AND $this->tableE.estado = 1
				AND $this->tableProv.estado = 1
				AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				$clavee
				GROUP BY proveedor
				ORDER BY $this->tableProv.clave, proveedor"
			)->fetchAll();
		}

		// Obtener det_entrada por nota_entrada
        public function getByNota($fk_proveedor, $inicio, $fin, $nota_entrada) {
			$prov="";
			$provv="";
			if($fk_proveedor!='0'){
				$prov = " $this->tableE.fk_proveedor = '$fk_proveedor' AND";
				$provv = " AND $this->tableE.fk_proveedor = '$fk_proveedor' ";
			}
			return $this->db->getPdo()->query(
				"SELECT
				$this->table.id_det_entrada, $this->table.fk_entrada, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->tableProd.descripcion, $this->tableE.nota_entrada, $this->tableE.fk_proveedor, $this->tableProd.peso as pesou, DATE_FORMAT($this->tableE.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) as proveedor, 
				(SELECT SUM(replace($this->tableE.peso_total,',','')) FROM $this->tableE WHERE 
				$prov
				DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') >= '$inicio' AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') <= '$fin' AND $this->tableE.estado = 1) as peso_total
				FROM $this->table, $this->tableProd, $this->tableE, $this->tableProv
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_entrada = $this->tableE.id_entrada
				AND $this->tableE.fk_proveedor = $this->tableProv.id_proveedor 
				AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') >= '$inicio' AND DATE_FORMAT($this->tableE.fecha, '%Y-%m-%d') <= '$fin' 
				$provv
				AND $this->tableE.estado = 1
				AND $this->table.estado = 1
				AND $this->tableE.nota_entrada = '$nota_entrada' ORDER BY $this->table.fk_entrada"
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
