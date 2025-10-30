<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";

    public function setTitulo($tittle){
        $this->titulo=$tittle;        
    }

	function Header(){
		
		$this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);

        $mesStr = array('',' de Enero de ',' de Febrero de ',' de Marzo de ',' de Abril de ', 
				' de Mayo de ',' de Junio de ',' de Julio de ',' de Agosto de ', 
				' de Septiembre de ',' de Octubre de ',' de Noviembre de ',' de Diciembre de ');
        $subtitulo = "Al ".date('d').$mesStr[date('n')].date('Y H:i:s');

		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'R');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'R');
        $this->Cell(0, 10, '', 0, 1, 'R');
        $this->Cell(88, 0, '', 'B', 0, 'L');
		$this->Cell(10, 0, '', 0, 0, 'L');
		$this->Cell(88, 0, '', 'B', 1, 'L');
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
		$this->Cell(60, 5, 'DONADOR', 0, 0, 'C');
		$this->Cell(28, 5, 'EXISTENCIAS', 0, 0, 'L');
		$this->Cell(10, 5, '', 0, 0, 'L');
		$this->Cell(60, 5, 'DONADOR', 0, 0, 'C');
		$this->Cell(28, 5, 'EXISTENCIAS', 0, 1, 'C');
		$this->Cell(88, 0, '', 'T', 0, 'L');
		$this->Cell(10, 0, '', 0, 0, 'L');
		$this->Cell(88, 0, '', 'T', 1, 'L');
        //$this->SetX(20);
        //$this->Cell(250, 0, '', 'T', 0, 'L');
	}

	function Footer(){
		
		$paginas = "Página " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
		$this->SetY(-15);
		$this->SetTextColor(77, 73, 72);
	    $this->Ln(4);
	    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
	    $this->Cell(0, 3, $paginas, 0, 0, 'C');
	}
}


$miReporte = new PDF('P', 'mm', 'letter'); 
$miReporte->setTitulo($vista);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetAuthor('DDS.media'); 
$miReporte->SetCreator('www.fpdf.org'); 
$miReporte->SetTitle($vista); 
$miReporte->SetFillColor(240, 240, 240); 
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->SetXY(20,60);
$miReporte->SetMargins(20, 60); 
$contador = 2;

$cve = '';
$pesoTot = 0;

$arrCve = array('AA' => 'AMBA ABARROTES', 'AP' => 'AMBA PERECEDEROS', 
				'BA' => 'BANCOS ABARROTES', 'BP' => 'BANCOS PERECEDEROS', 
				'LA' => 'LOCALES ABARROTES', 'LP' => 'LOCALES PERECEDEROS', 
				'CO' => 'COMPRAS', 'BACEH' => 'BACEH');
$arrCves = array();
$arrAct = array();

foreach($registros as $prod){
    if ($cve != $prod->clave){
		if(count($arrAct) > 0){
			$arrCves[] = $arrAct;
			$arrAct = array();
		}
		$cve = $prod->clave;
	}
	$arrAct[] = $prod;
}

$arrCves[] = $arrAct;

$arrFinal = array(
    array('clavei' => 'titulo', 
          'provei' => $arrCve[$arrCves[0][0]->clave], 
          'pesoti' => ''
          )
);

$lnIzq = count($arrCves[0]) + 2;
$l = 1;
$pesot = 0;

foreach ($arrCves[0] as $reg) {
    $regFinal = array('clavei' => $reg->clave, 
					  'provei' => $reg->proveedor, 
					  'pesoti' => number_format($reg->exist,3));
    $arrFinal[] = $regFinal;
    $pesot = $pesot + str_replace(',', '', $reg->exist);
	$l = $l + 1; 
}

$arrFinal[] = array('clavei' => 'total', 
					  'provei' => number_format($pesot,3), 
					  'pesoti' => '', 
					  'claved' => '', 
					  'proved' => '', 
					  'pesotd' => ''
					  );

$l = $l + 1;					  
$pesot = 0;	

if (count($arrCves) > 1) {

	$arrFinal[0]['claved'] = 'titulo';
	$arrFinal[0]['proved'] = $arrCve[$arrCves[1][0]->clave];
	$arrFinal[0]['pesotd'] = '';

	$lnDer = count($arrCves[1]) + 2;
	$i = 1;
	foreach ($arrCves[1] as $reg) {
		$arrFinal[$i]['claved'] = $reg->clave;
		$arrFinal[$i]['proved'] = $reg->proveedor;
		$arrFinal[$i]['pesotd'] = number_format($reg->exist,3);
		$i = $i + 1;
		$pesot = $pesot + str_replace(',', '', $reg->exist);
	}
	$arrFinal[$i]['claved'] = 'total';
	$arrFinal[$i]['proved'] = number_format($pesot,3);
	$arrFinal[$i]['pesotd'] = '';
	$i = $i + 1;
	$pesot = 0;
}

for ($j=2; $j<count($arrCves) ; $j++) { 
	$pesot = 0;
	if ($lnIzq < $lnDer) {
		$lnIzq = $lnIzq + count($arrCves[$j]) + 2;

		$arrFinal[$l]['clavei'] = 'titulo';
		$arrFinal[$l]['provei'] = $arrCve[$arrCves[$j][0]->clave];
		$arrFinal[$l]['pesoti'] = '';
		$l = $l + 1;
		
		foreach ($arrCves[$j] as $reg) {
			$arrFinal[$l]['clavei'] = $reg->clave;
			$arrFinal[$l]['provei'] = $reg->proveedor;
			$arrFinal[$l]['pesoti'] = number_format($reg->exist,3);
			$l = $l + 1;
			$pesot = $pesot + str_replace(',', '', $reg->exist);
		}
		$arrFinal[$l]['clavei'] = 'total';
		$arrFinal[$l]['provei'] = number_format($pesot,3);
		$arrFinal[$l]['pesoti'] = '';
		$l = $l + 1;
	}else{
		$lnDer = $lnDer + count($arrCves[$j]) + 2;

		$arrFinal[$i]['claved'] = 'titulo';
		$arrFinal[$i]['proved'] = $arrCve[$arrCves[$j][0]->clave];
		$arrFinal[$i]['pesotd'] = '';
		$i = $i + 1;
		
		foreach ($arrCves[$j] as $reg) {
			$arrFinal[$i]['claved'] = $reg->clave;
			$arrFinal[$i]['proved'] = $reg->proveedor;
			$arrFinal[$i]['pesotd'] = number_format($reg->exist,3);
			$i = $i + 1;
			$pesot = $pesot + str_replace(',', '', $reg->exist);
		}
		$arrFinal[$i]['claved'] = 'total';
		$arrFinal[$i]['proved'] = number_format($pesot,3);
		$arrFinal[$i]['pesotd'] = '';
		$i = $i + 1;
	}
}

//echo json_encode($arrFinal); exit;

foreach ($arrFinal as $line) {
	$relleno = $contador % 2;

	$miReporte->Ln(1);
	$miReporte->SetX(10);
	$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
	if(isset($line['clavei'])){
		if ($line['clavei'] == 'titulo') {
			$miReporte->Cell(88, 5, $line['provei'], 0, 0, 'L', $relleno);
		}else if ($line['clavei'] == 'total') {
			$miReporte->Cell(60, 5, '', 0, 0, 'L', $relleno);
			$miReporte->Cell(28, 5, $line['provei'], 0, 0, 'R', $relleno);
			$pesoTot = $pesoTot + str_replace(',', '', $line['provei']);
		}else{
			$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
			$miReporte->Cell(60, 5, $line['provei'], 0, 0, 'L', $relleno);
			$miReporte->Cell(28, 5, $line['pesoti'], 0, 0, 'R', $relleno);
		}
	}else{
		$miReporte->Cell(88, 5, '', 0, 0, 'L', $relleno);
	}

	$miReporte->Cell(10, 5, '', 0, 0, 'L');

	
	$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
	if(isset($line['claved'])){
		if ($line['claved'] == 'titulo') {
			$miReporte->Cell(88, 5, $line['proved'], 0, 1, 'L', $relleno);
		}else if ($line['claved'] == 'total') {
			$miReporte->Cell(60, 5, '', 0, 0, 'L', $relleno);
			$miReporte->Cell(28, 5, $line['proved'], 0, 1, 'R', $relleno);
			$pesoTot = $pesoTot + str_replace(',', '', $line['proved']);
		}else{
			$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
			$miReporte->Cell(60, 5, $line['proved'], 0, 0, 'L', $relleno);
			$miReporte->Cell(28, 5, $line['pesotd'], 0, 1, 'R', $relleno);
		}
	}else{
		$miReporte->Cell(88, 5, '', 0, 1, 'L', $relleno);
	}

	$contador = $contador + 1; 
}

//$miReporte->Cell(0, 0, '', 'T', 1, 'L');
$miReporte->Ln(20);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
$miReporte->Cell(88, 5, 'TOTAL EXISTENCIAS: ', 0, 0, 'R');
$miReporte->Cell(10, 5, '', 0, 0, 'C');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
$miReporte->Cell(88, 5, number_format($pesoTot,3), 0, 1, 'L');
$miReporte->Output();
exit();
?>