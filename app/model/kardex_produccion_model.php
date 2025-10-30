<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;
	use Envms\FluentPDO\Literal;

	class KardexProduccionModel {
		private $db;
		private $table = 'kardex_produccion';
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

		// Editar kardex_produccion
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
				$this->response->SetResponse(false, "catch: Edit model kardex_produccion $ex");
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
			//$this->response->result = $EntradasRest;
			//return $this->response->SetResponse(true);
            return $EntradasRest;
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

        // Editar kardex_produccion por producto
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
				$this->response->SetResponse(false, "catch: Edit model kardex_produccion $ex");
			}
			return $this->response;
		}

        // Obtener kardex_produccion por fecha
		public function kardexByDate($fecha, $fk_producto) {
			$kardexByDate = $this->db
				->from($this->table)
                ->select(null)->select('COUNT(*) Total')
				->where('fecha', $fecha)
                ->where('fk_producto', $fk_producto)
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

        // Obtener inicial de kardex_produccion por fecha
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
				/* $this->response->result = $kardexInicial->inicial;
				return $this->response->SetResponse(true); */
				return $kardexInicial->inicial;
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

        // Obtener final de kardex_produccion por fecha
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
				/* $this->response->result = $kardexFinal->final;
				return $this->response->SetResponse(true); */
				return $kardexFinal->final;
			}else{
				//return $this->response->SetResponse(false, 'No existe el registro');
				return '0';
			}
		}

        // Agregar kardex_produccion
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
				$this->response->SetResponse(false, "catch: Add model kardex_produccion $ex");
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
			return $this->editByProducto($data, $fk_producto, $fecha);
			//$this->response->result = $entradasSum;
			//return $this->response->SetResponse(true);
		}

        // Suma inicial y final en kardex_produccion
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

        // Editar kardex_produccion por producto y fecha
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
				$this->response->SetResponse(false, "catch: Edit model kardex_produccion $ex");
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

		// Sumar salidas
		public function salidasSum($_cantidad, $_fk_producto, $fecha) {
			/* $salida = $this->getSalidaById($fk_producto, $fecha);
			$salidass = $salida+$cantidad;
			$fina = $this->getFinalById($fk_producto, $fecha);
			$finall = $fina-$cantidad; */
			$data = [
				'salidas'=> new Literal('(salidas + '.$_cantidad.')'),
				'final'=> new Literal('(final - '.$_cantidad.')')
			];
			return $SalidasRest = $this->editByProducto($data, $_fk_producto, $fecha);			
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
		} 

		// Obtener kardex produccion entre fechas
        public function getProduccion($producto, $inicio, $fin) {
			return $this->db->getPdo()->query(
				"SELECT fecha, inicial, entradas, salidas, final, DATE_FORMAT( fecha,'%d-%m-%Y') AS fechaf,
				(SELECT peso FROM cat_producto WHERE id_producto = fk_producto) AS peso
				FROM kardex_produccion 
				WHERE fk_producto = $producto
				AND fecha BETWEEN DATE_FORMAT('$inicio', '%Y-%m-%d') AND DATE_FORMAT('$fin', '%Y-%m-%d')
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
