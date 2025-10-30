<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;
use Envms\FluentPDO\Literal;

	class VentaModel {
		private $db;
		private $table = 'venta_tiendita';
        private $tableC = 'cat_cliente';
        private $tableU = 'cat_usuario';
		private $tableT = 'cat_titular';
		private $tableDV = 'det_venta_tiendita';
        private $tableProv = 'cat_proveedor';
		private $tableProd = 'cat_producto';
		private $tableKT = 'kardex_tiendita';
		private $tableDET = 'det_entrada_tiendita';
		private $tableET = 'entrada_tiendita';
		private $tableS = 'salida';
		private $tableBene = 'baceh2022_beneficiarios';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener todas las venta_tiendita
		public function getAll($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_venta, $this->table.fk_usuario, $this->table.fecha, $this->table.peso_total, $this->table.tipo, $this->table.institucion, $this->table.total, $this->table.beneficiario, $this->table.version, CONCAT_WS(' ', CONCAT_WS(' ', DATE_FORMAT($this->table.fecha, '%d-%m-%Y')), SUBSTRING($this->table.fecha, 12, 20)) as fechaf")
                ->where("CONCAT_WS(' ', $this->table.id_venta, $this->table.fk_usuario, $this->table.fecha, $this->table.peso_total, $this->table.tipo, $this->table.institucion, $this->table.total, $this->table.beneficiario, $this->table.version) LIKE '%$busqueda%'")
                ->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->table.fecha DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->table.id_venta, $this->table.fk_usuario, $this->table.fecha, $this->table.peso_total, $this->table.tipo, $this->table.institucion, $this->table.total, $this->table.beneficiario, $this->table.version) LIKE '%$busqueda%'")
				->where("$this->table.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener todas las det_venta_tiendita
		public function getAllDetVenta($pagina, $limite, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->tableDV)
				->select(null)->select("$this->tableDV.id_det_venta, $this->tableDV.fk_venta, $this->tableDV.fk_producto, $this->tableDV.cantidad, $this->tableDV.peso, $this->tableDV.importe")
                ->where("CONCAT_WS(' ', $this->tableDV.id_det_venta, $this->tableDV.fk_venta, $this->tableDV.fk_producto, $this->tableDV.cantidad, $this->tableDV.peso, $this->tableDV.importe) LIKE '%$busqueda%'")
                ->where("$this->tableDV.estado", 1)
				->limit("$inicial, $limite")
				->orderBy("$this->tableDV.fecha_modificacion DESC")
				->fetchAll();
			$this->response->total = $this->db
				->from($this->tableDV)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', $this->tableDV.id_det_venta, $this->tableDV.fk_venta, $this->tableDV.fk_producto, $this->tableDV.cantidad, $this->tableDV.peso, $this->tableDV.importe) LIKE '%$busqueda%'")
				->where("$this->tableDV.estado", 1)
				->limit("$inicial, $limite")
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener ventas por fecha
        public function getByDate($inicio, $fin, $user) {
			$userr="TRUE";
			if($user!='0'){
				$userr = " fk_usuario = $user ";
			}

			$resultado = $this->db
							->from($this->table)->disableSmartJoin()
							->innerJoin('cat_usuario ON id_usuario = fk_usuario')
							->select("DATE_FORMAT($this->table.fecha, '%d-%m-%Y %H:%i:%s') as fechaf, 
									(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_venta = id_venta) AS num_prod, username, CONCAT(nombre,' ',apellidos) AS vendedor")
							->where("$this->table.estado", 1)
							->where("$this->table.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59'")
							->where($userr)
							->orderBy('fecha DESC')
							->fetchAll();
			//$this->response->result = $resultado;
			return $resultado;
		}

		// Obtener tickets
        public function getTickets($inicio, $fin, $cajero) {
			$userr="";
			if($cajero!='0'){
				$userr = "fk_usuario = $cajero";
			}
			$res = $this->db
					->from($this->table)->disableSmartJoin()
					->select("COUNT(*) AS total")
					->innerJoin("$this->tableU ON $this->tableU.id_usuario = $this->table.fk_usuario")
					->where("$this->table.estado", 1)
					->where("$this->table.tipo", 1)
					->where("$this->table.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59'")
					->where($userr)
					->orderBy("$this->table.fecha DESC")
					->fetch();
			return $res;
		}

		// Obtener mermas
        public function getMermas($inicio, $fin, $cajero) {
			$userr="";
			if($cajero!='0'){
				$userr = "fk_usuario = $cajero";
			}
			$res = $this->db
					->from($this->table)->disableSmartJoin()
					->select("COUNT(*) AS total")
					->innerJoin("$this->tableU ON $this->tableU.id_usuario = $this->table.fk_usuario")
					->where("$this->table.estado", 1)
					->where("$this->table.tipo", 2)
					->where("$this->table.fecha BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59'")
					->where($userr)
					->orderBy("$this->table.fecha DESC")
					->fetch();
			return $res;
		}

		// Obtener ventas por fecha
        public function getCancelados($inicio, $fin, $user) {
			$userr="";
			if($user!='0'){
				$userr = " AND fk_usuario = $user ";
			}
			return $this->response->result = $this->db->getPdo()->query(
				"SELECT COUNT(*) AS cancelados
				FROM $this->table 
				WHERE estado = 2 
				AND 
				DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') 
				$userr 
				;"
			)->fetch();
		}

		// Obtener venta por id
		public function get($id) {
			$salida = $this->db
				->from($this->table)->disableSmartJoin()
				->innerJoin('cat_usuario ON id_usuario = fk_usuario')
				->select(null)->select("$this->table.id_venta, $this->table.fk_usuario, $this->table.fecha, $this->table.peso_total, $this->table.tipo, 
										$this->table.institucion, $this->table.total, $this->table.beneficiario, 
										DATE_FORMAT($this->table.fecha, '%d-%m-%Y %H:%i:%s') as fechaf, CONCAT_WS(' ',nombre,apellidos) AS vendedor")
				->where('id_venta', $id)
				->fetch();
			if($salida) {
				$this->response->result = $salida;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Eliminar venta
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_venta', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Agregar venta
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
				$this->response->SetResponse(false, "catch: Add model venta $ex");
			}
			return $this->response;
		}

		// Editar venta
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_venta', $id)
					->execute();
				if($this->response->result != 0) { 
					return $this->response->SetResponse(true);
				} else { 
					return $this->response->SetResponse(false, 'No se actualizo el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model venta $ex");
			}
		}

		// Obtener usuarios passSupervisor
		public function getPassSupervisor($password) {
			$salida = $this->db
				->from($this->tableU)
				->select(null)->select("$this->tableU.id_usuario, $this->tableU.nombre, $this->tableU.apellidos, $this->tableU.email, $this->tableU.tipo, $this->tableU.username")
				->where('password', strrev(md5(sha1($password))))
				->fetch();
			if($salida) {
				$this->response->result = $salida;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener reporte de ventas entre fechas
        public function getRptVen($inicio, $fin) {
			set_time_limit(300);
			ini_set('memory_limit', '256M');
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->table.fecha,'%d-%m-%Y') AS fechaf,
				(SELECT descripcion FROM $this->tableProd WHERE id_producto = fk_producto) AS producto,
				(SELECT peso FROM $this->tableProd WHERE id_producto = fk_producto) AS pesou,
				(SELECT precio FROM $this->tableProd WHERE id_producto = fk_producto) AS precio,
				(SELECT CONCAT(nombre, ' ', apellidos) FROM $this->tableProv WHERE id_proveedor = (SELECT fk_proveedor FROM $this->tableProd WHERE id_producto = fk_producto)) AS donador,
				IFNULL((SELECT SUM(inicial) FROM $this->tableKT WHERE fk_producto = $this->tableDV.fk_producto AND DATE_FORMAT(fecha,'%d-%m-%Y') = DATE_FORMAT($this->table.fecha,'%d-%m-%Y')),'0') AS inicial,
				IFNULL((SELECT SUM(cantidad) FROM $this->tableDET WHERE fk_producto = $this->tableDV.fk_producto AND (SELECT DATE_FORMAT(fecha,'%d-%m-%Y') FROM $this->tableET WHERE id_entrada = $this->tableDET.fk_entrada) =  DATE_FORMAT($this->table.fecha,'%d-%m-%Y') GROUP BY fk_producto), 0) AS entradas,
				SUM($this->tableDV.cantidad) AS todas,

				(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 1 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha,'%Y-%m-%d')) AS ventas,

				(SELECT SUM(importe) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 1 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha,'%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha,'%Y-%m-%d')) AS ventast,

						(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 2 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha, '%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha, '%Y-%m-%d') ) AS mermas, 

						(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 3 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha, '%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha, '%Y-%m-%d') ) AS trab, 

						(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 4 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha, '%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha, '%Y-%m-%d') ) AS donacion, 

						(SELECT SUM(cantidad) FROM $this->tableDV WHERE fk_producto = $this->tableDV.fk_producto 
							AND (SELECT tipo FROM $this->table WHERE id_venta = fk_venta) = 5 AND (SELECT estado FROM $this->table WHERE id_venta = fk_venta) = 1
								AND (SELECT DATE_FORMAT(fecha, '%Y-%m-%d') FROM $this->table WHERE id_venta = fk_venta) = DATE_FORMAT($this->table.fecha, '%Y-%m-%d') ) AS inst, 

						IFNULL((SELECT SUM(final) FROM $this->tableKT WHERE fk_producto = $this->tableDV.fk_producto AND DATE_FORMAT(fecha,'%d-%m-%Y') = DATE_FORMAT($this->table.fecha,'%d-%m-%Y')),'0') AS final

					FROM $this->tableDV, $this->table
					WHERE $this->tableDV.fk_venta = $this->table.id_venta 
					AND $this->table.estado = 1 
					AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')  
					GROUP BY DATE_FORMAT(fecha, '%Y-%m-%d'), fk_producto
					ORDER BY fecha, producto;"
			)->fetchAll();
		}

		// Obtener reporte de ventas entre fechas
        public function getRptVentasFinal($inicio, $fin) {
			set_time_limit(300);
			ini_set('memory_limit', '256M');
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->table.fecha,'%d-%m-%Y') AS fechaf, 
				fk_producto, DATE_FORMAT($this->table.fecha,'%Y-%m-%d') as fecha, SUM($this->tableDV.cantidad) AS todas
					FROM $this->tableDV, $this->table
					WHERE $this->tableDV.fk_venta = $this->table.id_venta 
					AND $this->table.estado = 1 
					AND $this->tableDV.estado = 1
					/* AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')  */ 
					AND fecha BETWEEN '$inicio' AND '$fin 23:59:59'  
					GROUP BY DATE_FORMAT(fecha, '%Y-%m-%d'), fk_producto
					ORDER BY fecha
					/* ORDER BY fecha, producto */;"
			)->fetchAll();
		}
        public function getDonadorByVenta($fk_producto) {
			return $this->db->getPdo()->query(
				"SELECT CONCAT(nombre, ' ', apellidos) as donador FROM $this->tableProv WHERE id_proveedor = (SELECT fk_proveedor FROM $this->tableProd WHERE id_producto = $fk_producto);"
			)->fetch();
		}
		public function getInicialByVenta($fk_producto, $fecha) {
			return $this->db->getPdo()->query(
				"SELECT SUM(inicial) as inicial, SUM(final) as final FROM $this->tableKT WHERE fk_producto = $fk_producto AND DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d');"
			)->fetch();
		}
		public function getInvInicialEntradas($fk_producto) {
			return $this->db->getPdo()->query(
				"SELECT inicial, entradas FROM $this->tableKT WHERE fk_producto = $fk_producto ORDER BY fecha DESC LIMIT 1;"
			)->fetch();
		}
		public function getEntradasByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDET)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS entradas')
				->innerJoin($this->tableET.' ON det_entrada_tiendita.fk_entrada = entrada_tiendita.id_entrada')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('entrada_tiendita.estado', 1)
				->groupBy('fk_producto')
				->fetch();
		}
		public function getVentasByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDV)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS ventas, SUM(importe) AS ventast')
				->innerJoin($this->table.' ON id_venta = det_venta_tiendita.fk_venta')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('venta_tiendita.tipo', 1)
				->where('venta_tiendita.estado', 1)
				->where('det_venta_tiendita.estado', 1)
				->fetch();
		}
		public function getMermasByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDV)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS mermas')
				->innerJoin($this->table.' ON id_venta = det_venta_tiendita.fk_venta')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('venta_tiendita.tipo', 2)
				->where('venta_tiendita.estado', 1)
				->where('det_venta_tiendita.estado', 1)
				->fetch();
		}
		public function getTrabByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDV)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS trab')
				->innerJoin($this->table.' ON id_venta = det_venta_tiendita.fk_venta')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('venta_tiendita.tipo', 3)
				->where('venta_tiendita.estado', 1)
				->where('det_venta_tiendita.estado', 1)
				->fetch();
		}
		public function getDonacionByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDV)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS donacion')
				->innerJoin($this->table.' ON id_venta = det_venta_tiendita.fk_venta')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('venta_tiendita.tipo', 4)
				->where('venta_tiendita.estado', 1)
				->where('det_venta_tiendita.estado', 1)
				->fetch();
		}
		public function getInstByVenta($fk_producto, $fecha) {
			return $this->db
				->from($this->tableDV)->disableSmartJoin()
				->select(null)->select('SUM(cantidad) AS inst')
				->innerJoin($this->table.' ON id_venta = det_venta_tiendita.fk_venta')
				// ->where("DATE_FORMAT(fecha,'%Y-%m-%d') = DATE_FORMAT('$fecha','%Y-%m-%d')")
				->where("fecha BETWEEN '$fecha' AND '$fecha 23:59:59'")
				->where('fk_producto', $fk_producto)
				->where('venta_tiendita.tipo', 5)
				->where('venta_tiendita.estado', 1)
				->where('det_venta_tiendita.estado', 1)
				->fetch();
		}

		// Obtener reporte entre fechas ordenado por fecha
        public function getRptDate($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT $this->tableDV.id_det_venta, $this->tableDV.fk_venta, $this->tableDV.fk_producto, $this->tableDV.cantidad, $this->tableDV.peso, $this->tableDV.importe,
				$this->tableProd.descripcion, $this->tableS.fk_cliente, DATE_FORMAT($this->tableS.fecha, '%d-%m-%Y %H:%i:%s') AS fechaf, 
				(SELECT SUM(replace($this->tableS.peso_total,',','')) FROM $this->tableS WHERE 
				DATE_FORMAT($this->tableS.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') 
				AND $this->tableS.estado = 1) as peso_total 
				FROM $this->tableDV, $this->tableProd, $this->tableS
				WHERE $this->tableDV.fk_producto = $this->tableProd.id_producto
				AND $this->tableDV.fk_venta = $this->tableS.id_salida
				AND 
				DATE_FORMAT($this->tableS.fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				AND $this->tableS.estado = 1
				AND $this->table.estado = 1
				ORDER BY $this->tableS.fecha;
				;"
			)->fetchAll();
		}

		// Obtener reporte entre fechas
        public function getRptDatee($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT($this->table.fecha,'%d-%m-%Y') AS fecha, $this->tableProd.descripcion AS producto, 
				$this->tableProd.peso AS pesou, $this->tableDV.cantidad, $this->tableDV.peso, 
						CONCAT($this->tableProv.nombre, ' ', $this->tableProv.apellidos) AS proveedor 
					FROM $this->tableDV, $this->tableProd, $this->table, $this->tableProv 
						WHERE $this->tableDV.fk_venta = $this->table.id_venta AND $this->tableDV.fk_producto = $this->tableProd.id_producto AND 
						$this->tableProv.id_proveedor = $this->tableProd.fk_proveedor AND $this->table.estado = 1 AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
							ORDER BY proveedor, producto, fecha
				;"
			)->fetchAll();
		}

		// Obtener reporte entre fechas
        public function getRptVentasCajero($inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT DATE_FORMAT( fecha,  '%d/%m/%Y' ) AS fechaf, 
				(SELECT CONCAT( nombre,  ' ', apellidos ) FROM $this->tableU WHERE id_usuario = fk_usuario) AS cajero, 
				(SELECT descripcion FROM $this->tableProd WHERE id_producto = fk_producto) AS producto, 
				SUM( cantidad ) AS cant, SUM( peso ) AS peso, SUM( importe ) AS importe
				FROM $this->tableDV, $this->table
				WHERE $this->tableDV.fk_venta = $this->table.id_venta AND $this->table.estado = 1
				AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') 
				GROUP BY DATE_FORMAT( fecha,  '%Y-%m-%d' ) , fk_usuario, fk_producto
				ORDER BY fecha, cajero, producto
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

		public function getBeneficiario($cred){
			$this->response->result = $this->db
				->from('baceh2022_beneficiarios.cat_titular')
				->select(null)->select('id_titular, id_integrante, CONCAT(nombre," ",apaterno," ",amaterno) AS titular')
				->innerJoin('baceh2022_beneficiarios.cat_integrante ON fk_titular = id_titular AND parentesco = "TITULAR"')
				->where("credencial LIKE '%$cred'")
				//->where('baceh2022_beneficiarios.cat_titular.estatus', 1)
				->fetch();

			if($this->response->result) return $this->response->SetResponse(true);
			else return $this->response->SetResponse(false,'No existe el beneficiario o esta dado de baja');
		} 
	}
?>
