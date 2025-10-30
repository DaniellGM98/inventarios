<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;

	class KardexTienditaModel {
		private $db;
		private $table = 'kardex_tiendita';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Arregla kardex
		public function arreglaKardex($producto, $fecha, $cajero) {
			$arreglaKardex = $this->db
                ->from($this->table)
                ->where('fk_producto', $producto)
                ->where("fecha >= ?", $fecha)
				->where('fk_cajero', $cajero)
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

		// Editar kardex_tiendita
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
				$this->response->SetResponse(false, "catch: Edit model kardex_tiendita $ex");
			}
			return $this->response;
		}

		// Obtener kardex_tiendita entre fechas
        public function getKardex($producto, $inicio, $fin, $cajero) {
			$this->arreglaKardex($producto, $inicio, $cajero);
			return $this->db->getPdo()->query(
				"SELECT id_kardex, fk_producto, fecha, inicial, entradas, salidas, final, fk_cajero, 
				DATE_FORMAT(fecha,'%d-%m-%Y') AS fechaf, 
						(SELECT peso FROM cat_producto WHERE id_producto = fk_producto) AS peso 
					FROM kardex_tiendita 
						WHERE fk_cajero = $cajero AND fk_producto = $producto 
						AND fecha BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
						ORDER BY fecha;"
			)->fetchAll();
		}

        // Resta entradas, final
		public function entradasRest($cantidad, $fk_producto, $fecha, $cajero) {
			/* $entrada = $this->getEntradaById($fk_producto, $fecha, $cajero);
			$entradass = $entrada-$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha, $cajero);
			$finall = $fina-$cantidad; */
			$data = [
				'entradas'=> new Literal('(entradas - '.$cantidad.')'),
				'final'=> new Literal('(final - '.$cantidad.')')
			];
			$EntradasRest = $this->editByProducto($data, $fk_producto, $fecha, $cajero)->result;
			$this->response->result = $EntradasRest;
			return $this->response->SetResponse(true);
		}

        // Obtener entrada por producto y fecha
		public function getEntradaById($fk_producto, $fecha, $cajero) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("entradas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
                ->where('fk_cajero', $cajero)
				->fetch();
			if($producto) {
				return $producto->entradas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Obtener final por producto y fecha
		public function getFinalById($fk_producto, $fecha, $cajero) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
                ->where('fk_cajero', $cajero)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

        // Editar kardex_tiendita por producto
		public function editByProducto($data, $fk_producto, $fecha, $cajero) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_producto', $fk_producto)
					->where('fecha', $fecha)
                    ->where('fk_cajero', $cajero)
					->execute();
				if($this->response->result!=0) { 
					$this->response->SetResponse(true);
				} else { 
					$this->response->SetResponse(false, 'No se actualizo el registro');
				}
			}catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model kardex_tiendita $ex");
			}
			return $this->response;
		}

		// Obtener kardex_tiendita por fecha
		public function kardexByDate($fecha, $fk_producto, $cajero) {
			$kardexByDate = $this->db
				->from($this->table)
                ->select(null)->select('COUNT(*) Total')
				->where('fecha', $fecha)
                ->where('fk_producto', $fk_producto)
				->where('fk_cajero', $cajero)
				->fetch();
			if($kardexByDate->Total!=0) {
				/* $this->response->result = $kardexByDate;
				return $this->response->SetResponse(true); */
				return $kardexByDate->Total;
			}else{
				/* $this->response->SetResponse(false, 'No existe el registro');
				return $this->response; */
				return '0';
			}
		}

		// Obtener inicial de kardex_tiendita por fecha
		public function kardexInicial($fecha, $fk_producto, $cajero) {
			$kardexInicial = $this->db
				->from($this->table)
                ->select(null)->select('final as inicial')
				->where('fecha < ?', $fecha)
                ->where('fk_producto', $fk_producto)
				->where('fk_cajero', $cajero)
                ->orderBy("fecha DESC")
                ->limit("1")
				->fetch();
			if($kardexInicial) {
				/* $this->response->result = $kardexInicial->inicial;
				return $this->response->SetResponse(true); */
				return $kardexInicial->inicial;
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

		// Obtener final de kardex_tiendita por fecha
		public function kardexFinal($fecha, $fk_producto, $cajero) {
			$kardexFinal = $this->db
				->from($this->table)
                ->select(null)->select('inicial as final')
				->where('fecha > ?', $fecha)
                ->where('fk_producto', $fk_producto)
                ->orderBy("fecha")
				->where('fk_cajero', $cajero)
                ->limit("1")
				->fetch();
			if($kardexFinal) {
				/* $this->response->result = $kardexFinal->final;
				return $this->response->SetResponse(true); */
				return $kardexFinal->final;
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

		// Agregar kardex_tiendita
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
				$this->response->SetResponse(false, "catch: Add model kardex_tiendita $ex");
			}
			return $this->response;
		}

		// Suma entradas
		public function entradasSum($cantidad, $fk_producto, $fecha, $cajero) {
			/* $entrad = $this->getEntradaById($fk_producto, $fecha, $cajero);
			$entradaa = $entrad+$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha, $cajero);
			$finall = $fina+$cantidad; */
			$data = [
				'entradas'=> new Literal('(entradas + '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			return $entradasSum = $this->editByProducto($data, $fk_producto, $fecha, $cajero);
			//$this->response->result = $entradasSum;
			//return $this->response->SetResponse(true);
		}

		// Suma inicial y final en kardex_tiendita
		public function inicialfinalSum($cantidad, $fk_producto, $fecha, $cajero) {
			/* $inicia = $this->getInicialByIdFecha($fk_producto, $fecha, $cajero);
			$iniciall = $inicia+$cantidad;
			$fina = $this->getFinalByIdMFecha($fk_producto, $fecha, $cajero);
			$finall = $fina+$cantidad; */
			$data = [
				'inicial'=> new Literal('(inicial + '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			$EntradasRest = $this->editByProductoFecha($data, $fk_producto, $fecha, $cajero)->result;
			$this->response->result = $EntradasRest;
			return $this->response->SetResponse(true);
		}

		// Obtener inicial por producto y fecha
		public function getInicialByIdFecha($fk_producto, $fecha, $cajero) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("inicial")
				->where('fk_producto', $fk_producto)
				->where('fk_cajero', $cajero)
				->where('fecha > ?', $fecha)
				->fetch();
			if($producto) {
				return $producto->inicial;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener final por producto y fecha
		public function getFinalByIdMFecha($fk_producto, $fecha, $cajero) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("final")
				->where('fk_producto', $fk_producto)
				->where('fk_cajero', $cajero)
				->where('fecha > ?', $fecha)
				->fetch();
			if($producto) {
				return $producto->final;
			}
			else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Editar kardex_tiendita por producto y fecha
		public function editByProductoFecha($data, $id, $fecha, $cajero) {
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			$this->response->result = $this->db
					->update($this->table, $data)
					->where('fk_producto', $id)
					->where('fk_cajero', $cajero)
					->where('fecha > ?', $fecha)
					->execute();
				if($this->response->result) { 
					return $this->response->SetResponse(true);
				}else{
					return $this->response->SetResponse(false, 'No se actualizo el registro');
				}
		}

		// Resta salidas
		public function salidasRest($cantidad, $fk_producto, $fecha, $cajero) {
			/* $salida = $this->getSalidaById($fk_producto, $fecha, $cajero);
			$salidass = $salida-$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha, $cajero);
			$finall = $fina+$cantidad; */
			$data = [
				'salidas'=> new Literal('(salidas - '.$cantidad.')'),
				'final'=> new Literal('(final + '.$cantidad.')')
			];
			$SalidasRest = $this->editByProducto($data, $fk_producto, $fecha, $cajero)->result;
			$this->response->result = $SalidasRest;
			return $this->response->SetResponse(true);
		}

		// Obtener salida por producto y fecha
		public function getSalidaById($fk_producto, $fecha, $cajero) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("salidas")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $cajero)
				->fetch();
			if($producto) {
				return $producto->salidas;
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Agregar kardex
		public function addKardex($fk_producto, $cantidad, $cajero, $tipo) {
			$fecha = date('Y-m-d');
			$result = $this->getKardexFecha($fecha, $fk_producto, $cajero)->result;
			if($this->getKardexFecha($fecha, $fk_producto, $cajero)->response){
				$inicial = $result->inicial;
				$entradas = $result->entradas;
				$salidas = $result->salidas;
				$salidas2 = intval($salidas)+intval($cantidad);
				$final = intval($inicial)+intval($entradas)-$salidas2;
				$dataa = [
					'entradas'=>$entradas,
					'salidas'=>$salidas2,
					'final'=>$final
				];
				return $this->editByProducto($dataa, $fk_producto, $fecha, $cajero);
			}else{
				$inicial = $this->getStockKardex($cajero, $fk_producto);
				$entradas = 0;
				$salidas = $cantidad;
				$final = intval($inicial)+intval($entradas)-intval($salidas);
				$dataaa = [
					'fk_producto'=>$fk_producto,
					'inicial'=>$inicial,
					'entradas'=>$entradas,
					'salidas'=>$salidas,
					'final'=>$final,
					'fk_cajero'=>$cajero
				];
				return $this->add($dataaa);
			}
		}

		// Agregar kardex para transferencia
		public function addKardexTrans($fk_producto, $cantidad, $cajero, $tipo, $fecha) {
			$result = $this->getKardexFecha($fecha, $fk_producto, $cajero)->result;
			if($this->getKardexFecha($fecha, $fk_producto, $cajero)->response){
				$inicial = $result->inicial;
				$entradas = $result->entradas;
				$salidas = $result->salidas;
				if($tipo > 0) {
					$entradas = intval($entradas) + intval($cantidad);
				}else{
					$salidas = intval($salidas) + intval($cantidad);
				}
				$final = intval($inicial)+intval($entradas)-intval($salidas);
				$dataa = [
					'entradas'=>$entradas,
					'salidas'=>$salidas,
					'final'=>$final
				];
				return $this->editByProducto($dataa, $fk_producto, $fecha, $cajero);
			}else{
				$inicial = $this->getStockKardex($cajero, $fk_producto);
				$entradas = 0;
				$salidas = 0;
				if($tipo > 0) {
					$entradas = $cantidad;
				}else{
					$salidas = $cantidad;
				}
				$final = intval($inicial)+intval($entradas)-intval($salidas);
				$dataaa = [
					'fk_producto'=>$fk_producto,
					'inicial'=>$inicial,
					'entradas'=>$entradas,
					'salidas'=>$salidas,
					'final'=>$final,
					'fk_cajero'=>$cajero
				];
				return $this->add($dataaa);
			}
		}

		// Obtener kardex por fecha
		public function getKardexFecha($fecha, $fk_producto, $cajero) {
			$kardex = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_kardex, $this->table.fk_producto, $this->table.fecha, $this->table.inicial, $this->table.entradas, $this->table.salidas, $this->table.final, $this->table.fk_cajero")
				->where('fk_producto', $fk_producto)
				->where('fecha', $fecha)
				->where('fk_cajero', $cajero)
				->fetch();
			if($kardex) {
				$this->response->result = $kardex;
				return $this->response->SetResponse(true);
			}else{
				return $this->response->SetResponse(false, 'No existe el registro');
			}
		}

		// Obtener stock de kardex
		public function getStockKardex($cajero, $fk_producto) {
			$kardex = $this->db
				->from($this->table)
				->select(null)->select("$this->table.final")
				->where('fk_producto', $fk_producto)
				->where('fk_cajero', $cajero)
				->orderBy("fecha DESC")
                ->limit("1")
				->fetch();
			if($kardex) {
				/* $this->response->result = $kardex->final;
				return $this->response->SetResponse(true); */
				return $kardex->final;
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

		// Suma entradas, salidas, final
		public function entradasSalidasFinal($entradas, $salidas, $producto, $fecha, $cajero) {
			/* $entrad = $this->getEntradaById($producto, $fecha, $cajero);
			$entradaa = $entrad+$entradas;
			$salid = $this->getSalidaById($producto, $fecha, $cajero);
			$salidaa = $salid+$salidas;
			$fina = $this->getFinalById($producto, $fecha, $cajero);
			$finall = $fina+$entradas-$salidas; */
			$data = [
				'entradas'=> new Literal('(entradas + '.$entradas.')'),
				'salidas'=> new Literal('(salidas + '.$salidas.')'),
				'final'=> new Literal('(final + '.$entradas.' - '.$salidas.')')
			];
			return $entradasSum = $this->editByProducto($data, $producto, $fecha, $cajero);
		}

		// Suma entradas, salidas, final con fecha mayor
		public function entradasSalidasFinalM($entradas, $salidas, $producto, $fecha, $cajero) {
			/* $inicia = $this->getInicialByIdFecha($producto, $fecha, $cajero);
			$iniciall = intval($inicia)+intval($entradas)-intval($salidas);
			$fina = $this->getFinalByIdMFecha($producto, $fecha, $cajero);
			$finall = intval($fina)+intval($entradas)-intval($salidas); */
			$data = [
				'inicial'=> new Literal('(inicial + '.intval($entradas).' - '.intval($salidas).')'),
				'final'=> new Literal('(final + '.intval($entradas).' - '.intval($salidas).')')
			];
			return $entradasSum = $this->editByProductoFecha($data, $producto, $fecha, $cajero);
		}

		// Obtener kardex tiendita entre fechas por cajero
        public function getTiendita($producto, $inicio, $fin, $cajero) {
			return $this->db->getPdo()->query(
				"SELECT /* DATE_FORMAT(fecha,'%d-%m-%Y') AS  */fecha,inicial, entradas, salidas, final, DATE_FORMAT( fecha,'%d-%m-%Y') AS fechaf
				,(SELECT peso FROM cat_producto WHERE id_producto = fk_producto) AS peso 
				FROM kardex_tiendita 
				WHERE fk_producto = $producto
				AND fk_cajero = $cajero
				AND DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
				ORDER BY fecha;"
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
