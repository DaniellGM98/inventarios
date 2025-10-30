<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;
use Envms\FluentPDO\Literal;

	class KardexComunidadModel {
		private $db;
		private $table = 'kardex_comunidad';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Arregla kardex
		public function arreglaKardex($producto, $fecha) {
			$arreglaKardex = $this->db
                ->from($this->table)
                ->where('fk_producto', $producto)
                ->where("fecha >= ?", $fecha)
                ->orderBy("fecha")
                ->fetchAll();
			$inicial = -1; 
			$entradas = 0; 
			$salidas = 0; 
			$final = 0;
			$count=count($arreglaKardex);
			for($x=0;$x<$count;$x++){	
				$entradas = intval($arreglaKardex[$x]->entradas);
				$salidas = intval($arreglaKardex[$x]->salidas);				
				if($inicial == -1){
					$inicial = intval($arreglaKardex[$x]->inicial);
					$final = intval($arreglaKardex[$x]->final);
				}else{
					$inicial = $final;
					$final = $inicial + $entradas - $salidas;
					$dataKard = [
						'inicial'=>strval($inicial),
						'final'=>strval($final)
					];
					$this->edit($dataKard, $arreglaKardex[$x]->id_kardex);
				}
			}
		}

		// Editar kardex_comunidad
		public function edit($data, $id) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_kardex', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex_comunidad $ex");
			}
			return $this->response;
		}

		// Obtener kardex_comunidad entre fechas
        public function getKardex($producto, $inicio, $fin) {
			$this->arreglaKardex($producto, $inicio);
			return $this->db->getPdo()->query(
				"SELECT id_kardex, fk_producto, fecha, inicial, entradas, salidas, final, 
				DATE_FORMAT(fecha,'%d-%m-%Y') AS fechaf, 
				(SELECT peso FROM cat_producto WHERE id_producto = fk_producto) AS peso 
				FROM kardex_comunidad 
				WHERE fk_producto = $producto 
				AND fecha BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				ORDER BY fecha;"
			)->fetchAll();
		}

        // Resta entradas, final
		public function entradasRest($cantidad, $fk_producto, $fecha) {
			/* $entrada = $this->getEntradaById($fk_producto, $fecha);
			$entradass = $entrada-$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha);
			$finall = $fina-$cantidad; */
			$data = [
				'entradas'=> new Literal('(entradas - '.$cantidad.')'),
				'final'=> new Literal('(final - '.$cantidad.')')
			];
			$EntradasRest = $this->editByProducto($data, $fk_producto, $fecha)->result;
			$this->response->result = $EntradasRest;
			return $this->response->SetResponse(true);
		}

        // Obtener entrada por producto y fecha
		public function getEntradaById($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("entradas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->entradas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Obtener final por producto y fecha
		public function getFinalById($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto y fecha
		public function getFinalByIdMFecha($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha > ?', $fecha)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Editar kardex_comunidad por producto
		public function editByProducto($data, $fk_producto, $fecha) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_producto', $fk_producto)
					->where('fecha', $fecha)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex_comunidad $ex");
			}
			return $this->response;
		}

        // Obtener kardex_comunidad por fecha
		public function kardexByDate($fecha, $fk_producto) {
			return $this->db
				->from($this->table)
                ->select(null)->select('COUNT(*) Total')
				->where('fecha', $fecha)
                ->where('fk_producto', $fk_producto)
				->fetch();
			/* if($kardexByDate->Total!=0) {
				$this->response->result = $kardexByDate;
				return $this->response->SetResponse(true);
			}else{
				$this->response->SetResponse(false, 'No existe el registro');
				return $this->response;
			} */
		}

        // Obtener inicial de kardex_comunidad por fecha
		public function kardexInicial($fecha, $fk_producto) {
			$kardexInicial = $this->db
				->from($this->table)
                ->select(null)->select('final as inicial')
				->where('fecha < ?', $fecha)
                ->where('fk_producto', $fk_producto)
                ->orderBy("fecha DESC")
                ->limit("1")
				->fetch();
			if($kardexInicial) {
				return $kardexInicial->inicial;
				/* $this->response->result = $kardexInicial->inicial;
				return $this->response->SetResponse(true); */
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

        // Obtener final de kardex_comunidad por fecha
		public function kardexFinal($fecha, $fk_producto) {
			$kardexFinal = $this->db
				->from($this->table)
                ->select(null)->select('inicial as final')
				->where('fecha > ?', $fecha)
                ->where('fk_producto', $fk_producto)
                ->orderBy("fecha")
                ->limit("1")
				->fetch();
			if($kardexFinal) {
				return $kardexFinal->final;
				/* $this->response->result = $kardexFinal->final;
				return $this->response->SetResponse(true); */
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

        // Agregar kardex_comunidad
		public function add($data) {
            $data['fecha'] = date('Y-m-d');
            $data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model kardex_comunidad $ex");
			}
			return $this->response;
		}

        // Suma entradas
		public function entradasSum($cantidad, $fk_producto, $fecha) {
			/* $entrad = $this->getEntradaById($fk_producto, $fecha);
			$entradaa = $entrad+$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha);
			$finall = $fina+$cantidad; */
			$data = [
				'entradas'=> new Literal('(entradas + '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			return $entradasSum = $this->editByProducto($data, $fk_producto, $fecha);
			//$this->response->result = $entradasSum;
			//return $this->response->SetResponse(true);
		}

        // Suma inicial y final en kardex_comunidad
		public function inicialfinalSum($cantidad, $fk_producto, $fecha) {
			/* $inicia = $this->getInicialByIdFecha($fk_producto, $fecha);
			$iniciall = $inicia+$cantidad;
			$fina = $this->getFinalByIdMFecha($fk_producto, $fecha);
			$finall = $fina+$cantidad; */
			$data = [
				'inicial'=> new Literal('(inicial + '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			$EntradasRest = $this->editByProductoFecha($data, $fk_producto, $fecha)->result;
			$this->response->result = $EntradasRest;
			return $this->response->SetResponse(true);
		}

        // Obtener inicial por producto y fecha
		public function getInicialById($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("inicial")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->inicial;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener inicial por producto y fecha
		public function getInicialByIdFecha($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("inicial")
				->where('fk_producto', $fk_producto)
				->where('fecha > ?', $fecha)
				->fetch();
			if($producto) {
				return $producto->inicial;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Editar kardex_comunidad por producto y fecha
		public function editByProductoFecha($data, $id, $fecha) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_producto', $id)
					->where('fecha > ?', $fecha)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex $ex");
			}
			return $this->response;
		}

		// Resta salidas
		public function salidasRest($cantidad, $fk_producto, $fecha) {
			/* $salida = $this->getSalidaById($fk_producto, $fecha);
			$salidass = $salida-$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha);
			$finall = $fina+$cantidad; */
			$data = [
				'salidas'=> new Literal('(salidas - '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			$SalidasRest = $this->editByProducto($data, $fk_producto, $fecha)->result;
			$this->response->result = $SalidasRest;
			return $this->response->SetResponse(true);
		}

		// Obtener salida por producto y fecha
		public function getSalidaById($fk_producto, $fecha) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("salidas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->salidas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener inicial de kardex_comunidad por fecha y tipo de kardex
		public function kardexInicialByKardex($terminacion, $fecha, $fk_producto, $condicion) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada'
							?	'kardex'
							:	'kardex'));
			return $this->db->getPdo()->query("SELECT final FROM $kardexBy WHERE fecha<'$fecha' AND fk_producto='$fk_producto'$condicion ORDER BY fecha DESC LIMIT 1;"
			)->fetchAll();
		}

		// Obtener final de kardex_comunidad por fecha y tipo de kardex
		public function kardexFinalByKardex($terminacion, $fecha, $fk_producto, $condicion) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada'
							?	'kardex'
							:	'kardex'));
			return $this->db->getPdo()->query("SELECT inicial FROM $kardexBy WHERE fecha>'$fecha' AND fk_producto='$fk_producto'$condicion ORDER BY fecha LIMIT 1;"
			)->fetchAll();
		}

		// Agregar kardex_comunidad por tipo de kardex
		public function addByKardex($data, $value, $terminacion) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada'
							?	'kardex'
							:	'kardex'));
			if($value!=""){
				$data['fk_cajero'] = $value;
			}
            $data['fecha'] = date('Y-m-d');
            $data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($kardexBy, $data)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model kardex_comunidad $ex");
			}
			return $this->response;
		}

		// Sumar salidas
		public function salidasSum($cantidad, $fk_producto, $fecha) {
			/* $salida = $this->getSalidaById($fk_producto, $fecha);
			$salidass = $salida+$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha);
			$finall = $fina-$cantidad; */
			$data = [
				'salidas'=> new Literal('(salidas + '.$cantidad.')'),
				'final'=> new Literal('(final - '.$cantidad.')')
			];
			$SalidasRest = $this->editByProducto($data, $fk_producto, $fecha)->result;
			$this->response->result = $SalidasRest;
			return $this->response->SetResponse(true);
		}

		// Editar kardex_comunidad por tipo de kardex
		public function editByKardex($terminacion, $fecha, $_fk_producto, $_cantidad, $condicion, $_cajero) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada'
							?	'kardex'
							:	'kardex'));
			if($condicion!=""){
				/* $entrad = $this->getEntradaByIdFechaCajero($kardexBy, $_fk_producto, $fecha, $_cajero);
				$entradaa = $entrad+$_cantidad;
				$fina = $this->getFinalByIdFechaCajero($kardexBy, $_fk_producto, $fecha, $_cajero);
				$finall = $fina+$_cantidad; */
				$data = [
					'entradas'=> new Literal('(entradas + '.$_cantidad.')'),
					'final'=> new Literal('(final + '.$_cantidad.')')
				];
				$entradasSum = $this->editByProductoFechaCajero($kardexBy, $data, $_fk_producto, $fecha, $_cajero)->result;
				$this->response->result = $entradasSum;
				return $this->response->SetResponse(true);
			}else{
				/* $entrad = $this->getEntradaByIdFecha($kardexBy, $_fk_producto, $fecha);
				$entradaa = $entrad+$_cantidad;
				$fina = $this->getFinalByIdFecha($kardexBy, $_fk_producto, $fecha);
				$finall = $fina+$_cantidad; */
				$data = [
					'entradas'=> new Literal('(entradas + '.$_cantidad.')'),
					'final'=> new Literal('(final + '.$_cantidad.')')
				];
				return $this->editByProductoFechaKardex($kardexBy, $data, $_fk_producto, $fecha);
			}
		}

		// Obtener entrada por producto, fecha y cajero
		public function getEntradaByIdFechaCajero($kardexBy, $fk_producto, $fecha, $_cajero) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("entradas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $_cajero)
				->fetch();
			if($producto) {
				return $producto->entradas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener entrada por producto, fecha
		public function getEntradaByIdFecha($kardexBy, $fk_producto, $fecha) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("entradas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->entradas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto, fecha y cajero
		public function getFinalByIdFechaCajero($kardexBy, $fk_producto, $fecha, $_cajero) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $_cajero)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto, fecha
		public function getFinalByIdFecha($kardexBy, $fk_producto, $fecha) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Editar kardex_comunidad por producto, fecha y cajero
		public function editByProductoFechaCajero($kardexBy, $data, $_fk_producto, $fecha, $_cajero) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($kardexBy, $data)
					->where('fk_producto', $_fk_producto)
					->where('fecha', $fecha)
					->where('fk_cajero', $_cajero)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex $ex");
			}
			return $this->response;
		}

		// Editar kardex por producto, fecha y tipo de kardex
		public function editByProductoFechaKardex($kardexBy, $data, $id, $fecha) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($kardexBy, $data)
					->where('fk_producto', $id)
					->where('fecha', $fecha)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex $ex");
			}
			return $this->response;
		}

		// Suma inicial y final pr tipo de kardex, fecha y cajero
		public function inicialfinalSumByFechaCajero($terminacion, $fecha, $_fk_producto, $_cantidad, $condicion, $_cajero) {
			$kardexBy = $terminacion=='entrada_tiendita'
				?	'kardex_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'kardex_produccion'
					:	($terminacion=='entrada'
							?	'kardex'
							:	'kardex'));
			if($condicion!=""){
				/* $inicia = $this->getInicialByIdFechaKardexCajero($kardexBy, $_fk_producto, $fecha, $_cajero);
				$iniciall = $inicia+$_cantidad;
				$fina = $this->getFinalByIdFechaKardexCajero($kardexBy, $_fk_producto, $fecha, $_cajero);
				$finall = $fina+$_cantidad; */
				$data = [
					'inicial'=> new Literal('(inicial + '.$_cantidad.')'),
					'final'=> new Literal('(final + '.$_cantidad.')')
				];
				$EntradasRest = $this->editByProductoFechaMCajero($kardexBy, $data, $_fk_producto, $fecha, $_cajero);
				return $EntradasRest;
			}else{
				/* $inicia = $this->getInicialByIdFechaKardex($kardexBy, $_fk_producto, $fecha);
				$iniciall = $inicia+$_cantidad;
				$fina = $this->getFinalByIdFechaKardex($kardexBy, $_fk_producto, $fecha);
				$finall = $fina+$_cantidad; */
				$data = [
					'inicial'=> new Literal('(inicial + '.$_cantidad.')'),
					'final'=> new Literal('(final + '.$_cantidad.')')
				];
				$EntradasRest = $this->editByProductoFechaMKardex($kardexBy, $data, $_fk_producto, $fecha);
				return $EntradasRest;
			}
		}

		// Obtener inicial por producto, fecha, tipo de kardex y cajero
		public function getInicialByIdFechaKardexCajero($kardexBy, $fk_producto, $fecha, $_cajero) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("inicial")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $_cajero)
				->fetch();
			if($producto) {
				return $producto->inicial;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener inicial por producto, fecha y tipo de kardex
		public function getInicialByIdFechaKardex($kardexBy, $fk_producto, $fecha) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("inicial")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->inicial;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto, fecha, tipo de kardex y cajero
		public function getFinalByIdFechaKardexCajero($kardexBy, $fk_producto, $fecha, $_cajero) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $_cajero)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto, fecha y tipo de kardex
		public function getFinalByIdFechaKardex($kardexBy, $fk_producto, $fecha) {
			$producto = $this->db
				->from($kardexBy)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Editar kardex_comunidad por producto, fecha mayor y cajero
		public function editByProductoFechaMCajero($kardexBy, $data, $_fk_producto, $fecha, $_cajero) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($kardexBy, $data)
					->where('fk_producto', $_fk_producto)
					->where('fecha > ?', $fecha)
					->where('fk_cajero', $_cajero)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex $ex");
			}
			return $this->response;
		}

		// Editar kardex_comunidad por producto, fecha y tipo de kardex
		public function editByProductoFechaMKardex($kardexBy, $data, $id, $fecha) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($kardexBy, $data)
					->where('fk_producto', $id)
					->where('fecha > ?', $fecha)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex $ex");
			}
			return $this->response;
		}

		// Resta inicial y final en kardex
		public function inicialfinalRest($cantidad, $fk_producto, $fecha) {
			/* $inicia = $this->getInicialByIdFecha($fk_producto, $fecha);
			$iniciall = $inicia-$cantidad;
			$fina = $this->getFinalByIdMFecha($fk_producto, $fecha);
			$finall = $fina-$cantidad; */
			$data = [
				'inicial'=> new Literal('(inicial - '.$cantidad.')'),
				'final'=> new Literal('(final - '.$cantidad.')')
			];
			$EntradasRest = $this->editByProductoFecha($data, $fk_producto, $fecha);
			return $EntradasRest;
			//$this->response->result = $EntradasRest;
			//return $this->response->SetResponse(true);
		}

		// Obtener kardex comunidad entre fechas
        public function getComunidad($producto, $inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT  fecha,  inicial,  entradas,  salidas,  final, DATE_FORMAT( fecha,'%d-%m-%Y') AS fechaf
				,(SELECT peso FROM cat_producto WHERE id_producto = fk_producto) AS peso 
				FROM $this->table 
				WHERE fk_producto = $producto

				AND /* fecha BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d') */

				DATE_FORMAT( fecha, '%Y-%m-%d') >= '$inicio' AND DATE_FORMAT( fecha, '%Y-%m-%d') <= '$fin'

				ORDER BY fecha ASC;"
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
