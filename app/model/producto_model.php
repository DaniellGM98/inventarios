<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response,
		Envms\FluentPDO\Literal;
	require_once './core/defines.php';

	class ProductoModel {
		private $db;
		private $table = 'cat_producto';
		private $tableP = 'cat_proveedor';
		private $tableKT = 'kardex_tiendita';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}		

		// Obtener todos los productos
		public function getAll($pagina, $limite, $tipo, $busqueda, $orden = 'descripcion') {
			if($tipo==null){
				$tipo=0;
			}
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$this->response->result = $this->db
				->from($this->table)->disableSmartJoin()
				->select(null)->select("SQL_CALC_FOUND_ROWS id_producto, fk_proveedor, codigo_barras, descripcion, peso, precio, $this->table.estado, stock, stock_tiendita, stock_comunidad, stock_produccion, $this->table.fecha_modificacion, CONCAT_WS(' ', $this->tableP.nombre, $this->tableP.apellidos) as proveedor, IF($this->table.presentacion = 1, 'Pieza', IF($this->table.presentacion = 2, 'Caja', 'Bulto')) as presenta, IF($this->table.tipo = 1, 'Abarrotes', 'Perecederos') as tipos, peso * stock AS exist")
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("CONCAT_WS(' ', id_producto, fk_proveedor, codigo_barras, descripcion, peso, precio) LIKE '%$busqueda%'")
				// ->where("tipo=$tipo")
				->where("$this->table.estado", 1)
				->limit("$pagina, $limite")
				->orderBy($orden)
				->fetchAll();
			$this->response->total = $this->db->getPdo()->query('SELECT FOUND_ROWS()')->fetchColumn();

			$this->response->totalSQL = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("$this->table.estado", 1)
				->getQuery();
			$this->response->totalProd = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("$this->table.estado", 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Obtener productos por cajero y codigo de barras
		public function getByCajero($cajero=0, $cod) {
			if($cajero==null){
				$cajero=0;
			}
			$data = array();
			if(strlen($cod)>0) {
				return $this->buscaProd($cajero, $cod);
			}else{
				/* return  */$kardexByCajero = $this->getKardexByCajero($cajero);
				foreach($kardexByCajero as $kardexm) {
					if($kardexm->stock_tiendita > 0) {
						$product = $this->detalle($kardexm->fk_producto);
						$row					= array();
						$row['id_producto']		= $product->id_producto;
						$row['codigo']			= $product->codigo_barras;
						$row['descripcion']		= $product->descripcion;
						$row['stock_tiendita']	= $kardexm->stock_tiendita;
						$row['stock_cajero']	= $kardexm->final;
						$data[]					= $row;
					}
				}
				$this->response->result = $data;
				return $this->response->SetResponse(true);
			}
		}

		// Obtener producto
		public function buscaProd($cajero=0, $cod) {
			if($cajero==null){
				$cajero=0;
			}
			$arrProd = $this->db
				->from($this->table)
				->select(null)->select("id_producto, fk_proveedor, codigo_barras, descripcion, presentacion, tipo, peso, precio, $this->table.estado, stock, stock_tiendita, stock_comunidad, stock_produccion, $this->table.fecha_modificacion, CONCAT_WS(' ', $this->tableP.nombre, $this->tableP.apellidos) as proveedor, IF($this->table.presentacion = 1, 'Pieza', IF($this->table.presentacion = 2, 'Caja', 'Bulto')) as presenta, IF($this->table.tipo = 1, 'Abarrotes', 'Perecederos') as tipos")
				->leftJoin("$this->tableP ON $this->table.fk_proveedor = $this->tableP.id_proveedor")
				->where("$this->table.estado", 1)
				->where("$this->table.codigo_barras", $cod)
				->orderBy("$this->table.descripcion")
				->fetchAll();
			if($cajero == 0){
				$this->response->result = $arrProd;
				return $this->response->SetResponse(true);
			}else{
				foreach($arrProd as $prod){
					$prod->stock_cajero = $this->getStock($prod->id_producto, $cajero)->final;
				}
				$this->response->result = $arrProd;
				return $this->response->SetResponse(true);
			}
		}

		// Obtener kardex por cajero
		public function getKardexByCajero($cajero=0) {
			$kardexByCajero = array();
			$cajaQuery = "fk_cajero=$cajero";
			if($cajero=='0'){
				$cajaQuery='1=1';
			}
			if($cajero==null){
				$cajero=0;
			}
				/* $arr = $this->db
				->from($this->tableKT)
				->select(null)->select("fk_producto, $this->table.stock_tiendita")
				->innerJoin("$this->table ON $this->tableKT.fk_producto = $this->table.id_producto")
				->where($cajaQuery)
				->groupBy("fk_producto")
				->fetchAll(); */
				
				$arr = $this->db->getPdo()->query(
					"SELECT fk_producto, (SELECT stock_tiendita FROM cat_producto WHERE id_producto=fk_producto) as stock_tiendita FROM kardex_tiendita WHERE $cajaQuery GROUP BY fk_producto;"
				)->fetchAll();
				
				foreach($arr as $row){
					$ob = $this->getLastKardex($row->fk_producto, $cajero);
					if($ob!=null){
						$row->final = $ob->final;						
					}else{
						$row->final = '0';
					}
				}
				return $this->response->result = $arr;

				/* $arrr = array();
				 foreach($arr as $row){
					$ob = $this->getLastKardex($row->fk_producto, $cajero);
					if(!is_object($ob)){
						$ob->fk_producto=$row->fk_producto;
						$ob->final=0;
					}
					$ob->stock_tiendita = $row->stock_tiendita;
					$arrr[]=$ob;
				}
				return $this->response->result = $arrr; */
		}

		
		// Obtener un producto
		public function detalle($Identificador) {
		$det = $this->response->result = $this->db
				->from($this->table)
				->select(null)->select("id_producto, fk_proveedor, codigo_barras, descripcion, presentacion, tipo, peso, precio, stock, stock_tiendita, stock_comunidad, stock_produccion")
				->where("id_producto=$Identificador")
				->limit("1")
				->fetch();
			return $det;
		}

		// Obtener kardex tiendita por producto y cajero
		public function getStock($producto, $cajero=0) {
			$arr = $this->db
				->from($this->tableKT)
				->select(null)->select("final")
				->where("$this->tableKT.fk_producto", $producto)
				->where("$this->tableKT.fk_cajero", $cajero)
				->orderBy("$this->tableKT.fecha DESC")
				->limit("1")
				->fetch();
				if($arr==false){
					return (object)['final' => '0'];
				}else{
					return $arr;
				}
		}

		// Obtener Stock Tiendita
		public function getLastKardex($producto, $cajero) {
			$arr = $this->response->result = $this->db
				->from($this->tableKT)
				->where("fk_producto", $producto)
				->where("fk_cajero", $cajero)
				->orderBy("$this->tableKT.fecha DESC")
				->limit("1")
				->fetch();
				return $arr;
			}

		// Obtener Stock Tiendita
		public function getProductStockTiendita($id_producto) {
			$arr = $this->db
				->from($this->table)
				->select(null)->select("stock_tiendita")
				->where("$this->table.id_producto", $id_producto)
				->fetch();
			return $arr->stock_tiendita;
		}

		// Obtener todos los registros de la tabla cat_producto por proveedor
		public function listarXProveedor($proveedor) {
				$arr = $this->db
				->from($this->table)
				->select(null)->select("$this->table.id_producto, $this->table.fk_proveedor, $this->table.codigo_barras, $this->table.descripcion, $this->table.peso, $this->table.precio, $this->table.stock, $this->table.stock_tiendita, $this->table.stock_comunidad, $this->table.stock_produccion, CONCAT($this->tableP.nombre, ' ', $this->tableP.apellidos) as proveedor,  IF($this->table.presentacion = 1, 'Pieza', IF($this->table.presentacion = 2, 'Caja', 'Bulto')) as presenta, IF($this->table.tipo = 1, 'Abarrotes', 'Perecederos') as tipos")
				->innerJoin("$this->tableP ON $this->table.fk_proveedor = $this->tableP.id_proveedor")
				->where("$this->table.fk_proveedor=$proveedor")
				->where("$this->table.estado", 1)
				->orderBy("$this->table.descripcion")
				->fetchAll();
				return $arr;
		}

		// Obtener stock por clave
		public function stockByClave() {
			$arr = $this->db
			->from($this->table)
			->select(null)->select("$this->tableP.clave, CONCAT($this->tableP.nombre,' ',$this->tableP.apellidos) AS proveedor, SUM((REPLACE($this->table.peso,',','') * REPLACE($this->table.stock,',',''))) AS exist")
			->innerJoin("$this->tableP ON $this->table.fk_proveedor = $this->tableP.id_proveedor")
			->where("$this->table.estado", 1)
			->where("$this->table.stock > ?", 0)
			->orderBy("$this->tableP.clave, proveedor")
			->groupBy("proveedor")
			->fetchAll();
			return $arr;
		}

		// Agregar producto
		public function add($data) {
			date_default_timezone_set('America/Mexico_City');
            $data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se agrego el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model producto $ex");
			}
			return $this->response;
		}

		// Obtener producto por id
		public function get($id) {
			$producto = $this->db
				->from($this->table)
				// ->select(null)->select("fk_proveedor, codigo_barras, descripcion, presentacion, tipo, peso, precio")
				->where('id_producto', $id)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Editar producto
		public function edit($data, $id) {
			date_default_timezone_set('America/Mexico_City');
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id_producto', $id)
					->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else	$this->response->SetResponse(false, 'No se actualizo el registro');
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model producto $ex");
			}
			return $this->response;
		}

		// Eliminar producto
		public function del($id){
			$set = array('estado' => 2,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id_producto', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Buscar producto
		public function find($busqueda) {
			$productos = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', fk_proveedor, codigo_barras, descripcion, presentacion, tipo, peso, precio) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetchAll();
			$this->response->result = $productos;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', fk_proveedor, codigo_barras, descripcion, presentacion, tipo, peso, precio) LIKE '%$busqueda%'")
				->where("estado", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		}

		// Suma stock del producto
		public function stockSum($cantidad, $fk_producto) {
			/* $stoc = $this->getStockById($fk_producto)->result->stock;
			$stock = $stoc+$cantidad; */
			$data = [				
				'stock'=> new Literal('(stock + '.$cantidad.')')
			];
			$stockSum = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockSum;
			return $this->response->SetResponse(true);
		}

		// Suma stock_comunidad del producto
		public function stockComunidadSum($cantidad, $fk_producto) {
			/* $stoc = $this->getStockComunidadById($fk_producto)->result->stock_comunidad;
			$stock = $stoc+$cantidad; */
			$data = [
				'stock_comunidad'=> new Literal('(stock_comunidad + '.$cantidad.')')
			];
			$stockSum = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockSum;
			return $this->response->SetResponse(true);
		}

		// Suma stock_produccion del producto
		public function stockProduccionSum($cantidad, $fk_producto) {
			/* $stoc = $this->getStockProduccionById($fk_producto)->result->stock_produccion;
			$stock = $stoc+$cantidad; */
			$data = [
				'stock_produccion'=> new Literal('(stock_produccion + '.$cantidad.')')
			];
			$stockSum = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockSum;
			return $this->response->SetResponse(true);
		}

		// Suma stock_tiendita del producto
		public function stockTienditaSum($cantidad, $fk_producto) {
			/* $stoc = $this->getStockTienditaById($fk_producto)->result->stock_tiendita;
			$stock = $stoc+$cantidad; */
			$data = [
				'stock_tiendita'=> new Literal('(stock_tiendita + '.$cantidad.')')
			];
			$stockSum = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockSum;
			return $this->response->SetResponse(true);
		}

		// Resta stock del producto
		public function stockRest($cantidad, $fk_producto) {
			/* $stoc = $this->getStockById($fk_producto)->result->stock;
			$stock = $stoc-$cantidad; */
			$data = [
				'stock'=> new Literal('(stock - '.$cantidad.')')
			];
			$stockRest = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockRest;
			return $this->response->SetResponse(true);
		}

		// Resta stock_comunidad del producto
		public function stockComunidadRest($cantidad, $fk_producto) {
			/* $stoc = $this->getStockComunidadById($fk_producto)->result->stock_comunidad;
			$stock = $stoc-$cantidad; */
			$data = [
				'stock_comunidad'=> new Literal('(stock_comunidad - '.$cantidad.')')
			];
			$stockRest = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockRest;
			return $this->response->SetResponse(true);
		}

		// Resta stock_produccion del producto
		public function stockProduccionRest($cantidad, $fk_producto) {
			/* $stoc = $this->getStockProduccionById($fk_producto)->result->stock_produccion;
			$stock = $stoc-$cantidad; */
			$data = [
				'stock_produccion'=> new Literal('(stock_produccion - '.$cantidad.')')
			];
			$stockRest = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockRest;
			return $this->response->SetResponse(true);
		}

		// Resta stock_tiendita del producto
		public function stockTienditaRest($cantidad, $fk_producto) {
			/* $stoc = $this->getStockTienditaById($fk_producto)->result->stock_tiendita;
			$stock = $stoc-$cantidad; */
			$data = [
				'stock_tiendita'=> new Literal('(stock_tiendita - '.$cantidad.')')
			];
			$stockRest = $this->edit($data, $fk_producto)->result;
			$this->response->result = $stockRest;
			return $this->response->SetResponse(true);
		}

		// Resta stock del producto por tipo de stock
		public function stockSumByStock($terminacion, $cantidad, $fk_producto) {

			$kardexBy = $terminacion=='entrada_tiendita'
				?	'stock_tiendita'
				:	($terminacion=='entrada_produccion'
					?	'stock_produccion'
					:	($terminacion=='entrada_comunidad'
							?	'stock_comunidad'
							:	'stock'));

			/* $stoc = $this->getStockByStock($kardexBy, $fk_producto)->result->$kardexBy;
			$stock = $stoc+$cantidad; */
			$data = [
				"$kardexBy"=> new Literal('('.$kardexBy.' + '.$cantidad.')')
				//"$kardexBy"=>strval($stock)
			];
			return $stockRest = $this->edit($data, $fk_producto);
		}

		// Obtener stock por id
		public function getStockById($fk_producto) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("stock")
				->where('id_producto', $fk_producto)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener stock_comunidad por id
		public function getStockComunidadById($fk_producto) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("stock_comunidad")
				->where('id_producto', $fk_producto)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener stock_produccion por id
		public function getStockProduccionById($fk_producto) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("stock_produccion")
				->where('id_producto', $fk_producto)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener stock_tiendita por id
		public function getStockTienditaById($fk_producto) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("stock_tiendita")
				->where('id_producto', $fk_producto)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener stock por id y por tipo de stock
		public function getStockByStock($kardexBy, $fk_producto) {
			$producto = $this->db
				->from($this->table)
				->select(null)->select("$kardexBy")
				->where('id_producto', $fk_producto)
				->fetch();
			if($producto) {
				$this->response->result = $producto;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Obtener todos los productos
		public function getStockByAlm($alm) {
			$arr = ['stock','stock_tiendita','stock_comunidad','stock_produccion'];
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select("id_producto, fk_proveedor, codigo_barras, descripcion, peso, precio, $this->table.estado, $this->table.fecha_modificacion, CONCAT_WS(' ', $this->tableP.nombre, $this->tableP.apellidos) as proveedor, IF($this->table.presentacion = 1, 'Pieza', IF($this->table.presentacion = 2, 'Caja', 'Bulto')) as presenta, IF($this->table.tipo = 1, 'Abarrotes', 'Perecederos') as tipos,
				".$arr[$alm]." as stock
				")
				->innerJoin("$this->tableP ON $this->tableP.id_proveedor = $this->table.fk_proveedor")
				->where("$this->table.estado", 1)
				->where($arr[$alm].'> 0')
				->orderBy("$this->table.descripcion")
				->fetchAll();
			return $this->response->SetResponse(true);
		}
		
		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->select('CONCAT(nombre, " ", apellidos) AS proveedor')
				->innerJoin('cat_proveedor ON id_proveedor = fk_proveedor')
				->where($field, $value)
				->where($this->table.'.estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function getByProv($codigo, $prov){
			$this->response->result = $this->db
				->from($this->table)
				->where('codigo_barras', $codigo)
				->where('fk_proveedor', $prov)
				->where('estado', 1)
				->fetch();
			return $this->response->SetResponse(true);
		}


		/* //Consulta directa
		$this->response->result = $this->db->getPdo()->query(
					"SELECT
						$this->tableKT.fk_producto
						
						IFNULL((SELECT SUM(cantidad) FROM $this->tableDE WHERE producto_id=$this->table.id AND ($this->tableDE.caducidad IS NULL || DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m%-%d'), INTERVAL 10 DAY) < $this->tableDE.caducidad)), 0) AS stockSinCaducar,
						
					FROM producto 
					WHERE $this->table.status = 1
					GROUP BY $this->table.id
					ORDER BY $orden
					LIMIT $inicial, $limite;"
				)->fetchAll(); */
	}
?>