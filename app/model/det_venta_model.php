<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class DetVentaModel {
		private $db;
		private $table = 'det_venta_tiendita';
		private $tableS = 'salida';
		private $tableSC = 'salida_comunidad';
		private $tableProd = 'cat_producto';
		private $tableProv = 'cat_proveedor';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

        // Obtener det_venta por venta
		public function getByVenta($id) {
			$venta = $this->db
				->from($this->table)
				->innerJoin('cat_producto ON id_producto = fk_producto')
				->innerJoin('cat_proveedor ON id_proveedor = fk_proveedor')
				->select(null)->select("$this->table.id_det_venta, $this->table.fk_venta, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->table.importe, $this->table.fecha_modificacion,
				cat_producto.codigo_barras, cat_producto.descripcion AS producto, CONCAT(nombre, ' ', apellidos) AS proveedor, cat_producto.precio")
				->where('fk_venta', $id)
				->where($this->table.'.estado', 1)
				->fetchAll();
			if($venta) {
				$this->response->result = $venta;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener det_venta por venta
		public function getDetalle($fk_venta) {
			return $this->db->getPdo()->query(
				"SELECT $this->table.*, $this->tableProd.descripcion, $this->tableProd.precio 
				FROM $this->table, $this->tableProd
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_venta = $fk_venta;"
			)->fetchAll();
		}

        // Agregar det_venta
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
				$this->response->SetResponse(false, "catch: Add model det_venta $ex");
			}
			return $this->response;
		}

		// Editar det_venta
		public function editByVenta($data, $id, $idProducto) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_venta', $id)
					->where('fk_producto', $idProducto)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model det_venta $ex");
			}
			return $this->response;
		}

		// Obtener venta por id
        public function getById($id) {
			return $this->db->getPdo()->query(
				"SELECT fk_venta, fk_producto, cantidad, 
				(SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM venta_tiendita WHERE id_venta = fk_venta) AS fecha, 
				(SELECT fk_usuario FROM venta_tiendita WHERE id_venta = fk_venta) AS cajero 
			FROM det_venta_tiendita WHERE id_det_venta = $id;"
			)->fetch();
		}

		// Eliminar det_venta
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_det_venta', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Obtener det_venta por venta
		public function getPesoTotal($venta) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(peso),3) as peso_total FROM det_venta_tiendita WHERE fk_venta = $venta and estado=1"
			)->fetch();
		}

		public function getTotal($venta) {
			return $this->db->getPdo()->query(
				"SELECT round(SUM(importe),2) as total FROM det_venta_tiendita WHERE fk_venta = $venta and estado=1"
			)->fetch();
		}

		/* 

        

		

		

		

		

		// Obtener reporte entre fechas
        public function getRpt($inicio, $fin) {
			return $this->response->result = $this->db->getPdo()->query(
				"SELECT $this->table.id_det_salida, $this->table.fk_salida, $this->table.fk_producto, $this->table.cantidad, $this->table.peso, $this->table.importe, $this->tableProd.descripcion, 
				DATE_FORMAT($this->tableSC.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
				$this->tableSC.comunidad as cliente, 
				$this->tableSC.folio, 
				(SELECT SUM(replace($this->tableSC.peso_total,',','')) FROM $this->tableSC 
				WHERE DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableSC.estado = 1) as peso_total,
				(SELECT SUM(replace($this->tableSC.total,',','')) FROM $this->tableSC WHERE DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') AND $this->tableSC.estado = 1) as imp_total, 
				(SELECT CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) FROM $this->tableProv WHERE $this->tableProv.id_proveedor = $this->tableProd.fk_proveedor) AS proveedor 
				FROM $this->table, $this->tableProd, $this->tableSC
				WHERE $this->table.fk_producto = $this->tableProd.id_producto
				AND $this->table.fk_salida = $this->tableSC.id_salida
				AND DATE_FORMAT($this->tableSC.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableSC.estado = 1
				ORDER BY $this->tableSC.fecha, $this->tableSC.comunidad;"
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
		} */
	}
?>
