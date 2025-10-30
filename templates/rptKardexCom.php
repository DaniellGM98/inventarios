<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";
    private $producto = "";
    private $productostr = "";

    public function setTitulo($titulo, $inicio, $fin, $producto, $productostr){
        $this->titulo=$titulo;
        $this->inicio=$inicio;
        $this->fin=$fin;
        $this->producto=$producto;
        $this->productostr=$productostr;
    }

	function Header(){
        if(utf8_decode($this->inicio) == utf8_decode($this->fin)){
            $subtitulo = 'Del día '.utf8_decode($this->inicio);
        }else{
            $subtitulo = 'Del día '.utf8_decode($this->inicio).' al día '.utf8_decode($this->fin);
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(10);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Cell(0, 10, $this->productostr, 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->SetX(20);
        $this->Cell(240, 0, '', 'T', 0, 'L');
		$this->Ln(1);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(20);
		$this->Cell(30, 5, 'DIA', 0, 0, 'C');
        $this->Cell(25, 5, 'FECHA', 0, 0, 'C');
        $this->Cell(25, 5, 'INV INICIAL', 0, 0, 'C');
        $this->Cell(25, 5, 'ENTRADAS', 0, 0, 'C');
        $this->Cell(25, 5, 'SALIDAS', 0, 0, 'C');
        $this->Cell(25, 5, 'INV FINAL', 0, 0, 'C');
        $this->Cell(30, 5, 'ENTRADAS MES', 0, 0, 'C');
        $this->Cell(30, 5, 'SALIDAS MES', 0, 0, 'C');
        $this->Cell(25, 5, 'INV MES', 0, 1, 'C');
        $this->SetX(20);
        $this->Cell(240, 0, '', 'T', 0, 'L');
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

$miReporte = new PDF('L', 'mm', 'letter');
$miReporte->setTitulo($vista, $inicio, $fin, $producto, $productostr);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetAuthor('DDS.media'); 
$miReporte->SetCreator('www.fpdf.org'); 
$miReporte->SetTitle($vista); 
$miReporte->SetFillColor(240, 240, 240); 
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->SetXY(20,60);
$miReporte->SetMargins(20, 60); 

$diaStr = array('Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado');

$feArr = explode('-', $inicio);
$tsIni = mktime(0,0,0,$feArr[1],$feArr[2],$feArr[0]); 
$feArrf = explode('-', $fin);
$tsFin = mktime(0,0,0,$feArrf[1],$feArrf[2],$feArrf[0]); 
$segDif = $tsIni - $tsFin; 
$diasDif = $segDif / (60 * 60 * 24);  
$diasDif = abs($diasDif); 
$diasDif = floor($diasDif)+1; 

    $countReg = 0;
   
        $contador = 2;
        $prod = $registros[$countReg];
        $pesoProd = $prod->peso;
        $iniIni = number_format($prod->inicial*$pesoProd,3);
        $entMes = 0;		
        $salMes = 0;
        $feArr = explode('-', $inicio);
        $diaIni = $feArr[2];

        for($j = 0; $j < $diasDif; $j++){
	
            $prod = $registros[$countReg];

            $relleno = $contador % 2;
            
            $fechita = $feArr[0].'-'.$feArr[1].'-'.($diaIni+$j);
            //echo $fechita.':::';
            
            $feArr = explode('-', $fechita);
            $diaN = date('w', mktime(0, 0, 0, $feArr[1], $feArr[2], $feArr[0]));
            
            if( date("Y-m-d", mktime(0, 0, 0, $feArr[1], $feArr[2], $feArr[0])) == $prod->fecha){
            
                $miReporte->Cell(30, 5, $diaStr[$diaN], 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, $prod->fechaf , 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, number_format($prod->inicial *$pesoProd,3), 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, number_format($prod->entradas *$pesoProd,3), 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, number_format($prod->salidas *$pesoProd,3), 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, number_format($prod->final *$pesoProd,3), 0, 0, 'C', $relleno);
                
                $iniIni = number_format($prod->final *$pesoProd,3);
                $entMes = $entMes + ($prod->entradas *$pesoProd);
                $salMes = $salMes + ($prod->salidas *$pesoProd);
                
                $countReg ++;
                //$Registro = mysql_fetch_assoc($ResultSetActividad);
                
            }else{
                $miReporte->Cell(30, 5, $diaStr[$diaN], 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, date("d-m-Y", mktime(0, 0, 0, $feArr[1], $feArr[2], $feArr[0])), 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, $iniIni, 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, '', 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, '', 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, $iniIni, 0, 0, 'C', $relleno);
            }
            
            $entMesStr = '';			
            $salMesStr = '';			
            $finMesStr = '';
            if(date('t',mktime(0, 0, 0, $feArr[1], $feArr[2], $feArr[0])) == date('j',mktime(0, 0, 0, $feArr[1], $feArr[2], $feArr[0]))){
                $entMesStr = number_format($entMes,3);			
                $salMesStr = number_format($salMes,3);			
                $finMesStr = $iniIni;
                $entMes = 0;
                $salMes = 0;

                
            }
            $miReporte->Cell(30, 5, $entMesStr, 0, 0, 'C', $relleno);
            $miReporte->Cell(30, 5, $salMesStr, 0, 0, 'C', $relleno);
            $miReporte->Cell(25, 5, $finMesStr, 0, 1, 'C', $relleno);
            $contador = $contador + 1;
        }
        $miReporte->Cell(0, 0, '', 'T', 1, 'L');

//    echo json_encode($pesoProd); exit;

$miReporte->Output();
exit();
?>