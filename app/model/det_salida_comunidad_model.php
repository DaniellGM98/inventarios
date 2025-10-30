<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class DetsalidaComunidadModel {
		private $db;
		private $table = 'det_salida_comunidad';
		private $tableS = 'salida';
		private $tableSC = 'salida_comunidad';
		private $tableProd = 'cat_producto';
		private $tableProv = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener det_salida_comunidad por salida_comunidad
		public function getBySalida($id) {
			$salida = $this->db
				->from($this->table)
				->innerJoin('cat_producto ON id_producto = fk_producto')
				->innerJoin('cat_proveedor ON id_proveedor = fk_proveedor')
				->select(null)->select("$this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, 
							$this->table.cantidad, $this->table.peso, $this->table.importe, 
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

        // Agregar det_salida_comunidad
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
				$this->response->SetResponse(false, "catch: Add model det_salida_comunidad $ex");
			}
			return $this->response;
		}

		// Editar det_salida_comunidad
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
				$this->response->SetResponse(false, "catch: Edit model det_salida_comunidad $ex");
			}
			return $this->response;
		}

		// Obtener salida_comunidad por id
        public function getById($id) {
			return $this->db->getPdo()->query(
				"SELECT fk_salida, fk_producto, cantidad, (SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM salida_comunidad WHERE id_salida = fk_salida) AS fecha FROM det_salida_comunidad WHERE id_det_salida = $id;"
			)->fetch();
		}

		// Eliminar det_salida_comunidad
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

		// Obtener det_salida_comunidad por salida_comunidad
		public function getPesoTotal($salida) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(peso),3) as peso_total FROM det_salida_comunidad WHERE fk_salida = $salida and estado=1"
			)->fetch();
		}

		public function getTotal($salida) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(importe),2) as total FROM det_salida_comunidad WHERE fk_salida = $salida and estado=1"
			)->fetch();
		}

		// Obtener reporte entre fechas
        public function getRpt($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT $this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->table.importe, $this->tableProd.descripcion, 
				DATE_FORMAT($this->tableSC.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
				$this->tableSC.comunidad as cliente, 
				$this->tableSC.folio, 
				(SELECT SUM(replace($this->tableSC.peso_total,',','')) FROM $this->tableSC 
				WHERE DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableSC.estado = 1) as peso_total,
				(SELECT SUM(replace($this->tableSC.total,',','')) FROM $this->tableSC WHERE DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableSC.estado = 1) as imp_total, 
				(SELECT CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) FROM $this->tableProv WHERE $this->tableProv.id_proveedor = $this->tableProd.fk_proveedor) AS proveedor , fecha
				FROM $this->table, $this->tableProd, $this->tableSC
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_salida = $this->tableSC.id_salida
				AND DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableSC.estado = 1
				AND $this->table.estado = 1
				ORDER BY $this->tableSC.fecha, $this->tableSC.comunidad;"
			)->fetchAll();
		}

		// Obtener reporte por proveedor entre fechas
        public function getRptProv($inicio, $fin, $prov) {
			$provv="";
			if($prov!='0'){
				$provv = " $this->tableProd.fk_proveedor = ".$prov." AND ";
			}
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->tableSC.fecha,'%d-%m-%Y') AS fecha, $this->tableProd.descripcion AS producto, 
				$this->tableProd.peso AS pesou, $this->table.cantidad, $this->table.peso, comunidad AS cliente, 
				CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) AS proveedor 
				FROM $this->table, $this->tableProd, $this->tableSC, $this->tableProv 
				WHERE
				$provv
				$this->table.fk_salida = $this->tableSC.id_salida AND $this->table.fk_producto = $this->tableProd.id_producto AND 
				$this->tableProv.id_proveedor = $this->tableProd.fk_proveedor AND DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableSC.estado = 1 AND $this->table.estado = 1
					ORDER BY proveedor, producto, cliente, fecha
				;"
			)->fetchAll();
		}

		// Obtener reporte por cliente entre fechas
        public function getRptCli($inicio, $fin, $cli) {
			$clii="";
			if($cli!='0'){
				$clii = " $this->tableSC.comunidad = '$cli' AND ";
			}
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->tableSC.fecha,'%d-%m-%Y') AS fecha, $this->tableProd.descripcion AS producto, 
					$this->tableProd.peso AS pesou, $this->table.cantidad, $this->table.peso, comunidad AS cliente, 
						CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) AS proveedor 
					FROM $this->table, $this->tableProd, $this->tableSC, $this->tableProv 
					WHERE 
					$clii
					$this->table.fk_salida = $this->tableSC.id_salida 
					AND $this->table.fk_producto = $this->tableProd.id_producto 
					AND $this->tableProv.id_proveedor = $this->tableProd.fk_proveedor 
					AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') 
					AND $this->tableSC.estado = 1
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
