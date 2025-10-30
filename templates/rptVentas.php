<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";
    private $total = 0;

    public function setTitulo($tittle, $inicio, $fin){
        $this->titulo=$tittle;
        $this->inicio=$inicio;
        $this->fin=$fin;
    }

	function Header(){

        $arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        $arrFe = explode('-', $this->inicio);
        $subtitulo = "Del ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]];
        if($this->inicio != $this->fin){
            $arrFe = explode('-', $this->fin);
            $subtitulo .= " al ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]]." del ".$arrFe[0];
        }else{
            $subtitulo .= " del ".$arrFe[0];
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->Cell(257, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
		$this->Cell(35, 5, 'PRODUCTO', 0, 0, 'L');
		$this->Cell(12, 5, 'PESO', 0, 0, 'C');
		$this->Cell(35, 5, 'DONADOR', 0, 0, 'C');
		$this->Cell(12, 5, 'CUOTA', 0, 0, 'C');
		$this->Cell(16, 5, 'INV INI', 0, 0, 'C');
		$this->Cell(16, 5, 'ENTRAD', 0, 0, 'C');
		$this->Cell(16, 5, 'TOT ENT', 0, 0, 'C');
		$this->Cell(16, 5, 'VENTA', 0, 0, 'C');
		$this->Cell(16, 5, 'INV FIN', 0, 0, 'C');
		$this->Cell(16, 5, 'MERMAS', 0, 0, 'C');
		$this->Cell(16, 5, 'TRAB', 0, 0, 'C');
		$this->Cell(16, 5, 'DONACI', 0, 0, 'C');
		$this->Cell(16, 5, 'INSTIT', 0, 0, 'C');
		$this->Cell(18, 5, 'T VENTA', 0, 1, 'R');
        $this->Cell(257, 0, '', 'T', 0, 'L');
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
    $miReporte->setTitulo($vista, $inicio, $fin);
    $miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $miReporte->SetDisplayMode('real');
    $miReporte->SetAuthor('DDS.media'); 
    $miReporte->SetCreator('www.ddsmedia.net'); 
    $miReporte->SetTitle($vista); 
    $miReporte->SetSubject($vista);
    $miReporte->SetFillColor(240, 240, 240); 
    $miReporte->AddPage();
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
    $miReporte->SetXY(15,60);
    $miReporte->SetMargins(15, 60); 
    $contador = 2;

    $fecha = '';		
    $total = 0;
    foreach($registros as $prod){
        $relleno = $contador % 2;
        if($relleno) $miReporte->SetFillColor(240, 240, 240);
        else $miReporte->SetFillColor(255, 255, 255);
        $relleno = true;
        
        if ($fecha != $prod->fechaf){
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
            if($total > 0){
                $miReporte->Cell(233, 5, 'TOTAL', 0, 0, 'R', $relleno);
                $miReporte->Cell(18, 5, '$ '.number_format($total,2), 0, 1, 'R', $relleno);
                $contador = $contador + 1;
                $total = 0;
            }
            $relleno = $contador % 2;
            if($relleno) $miReporte->SetFillColor(240, 240, 240);
            else $miReporte->SetFillColor(255, 255, 255);
            $relleno = true;
            $miReporte->setX(10);
            $miReporte->Cell(251, 5, $prod->fechaf, 0, 1, 'L', $relleno);
            $contador = $contador + 1;
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
            $fecha = $prod->fechaf;
            $relleno = $contador % 2;
            if($relleno) $miReporte->SetFillColor(240, 240, 240);
            else $miReporte->SetFillColor(255, 255, 255);
            $relleno = true;
        }

        //$ventas = $prod->precio*$prod->ventas;
        $ventas = $prod->ventast;

        $miReporte->setX(10);
        $miReporte->Cell(35, 5, $prod->producto, 0, 0, 'L', $relleno);
        $miReporte->Cell(12, 5, number_format($prod->pesou,3), 0, 0, 'C', $relleno);
        $miReporte->Cell(35, 5, $prod->donador, 0, 0, 'L', $relleno);
        $miReporte->Cell(12, 5, number_format($prod->precio,2), 0, 0, 'C', $relleno);
        $miReporte->Cell(16, 5, $prod->inicial, 0, 0, 'C', $relleno);
        $miReporte->Cell(16, 5, $prod->entradas, 0, 0, 'C', $relleno);
        $miReporte->Cell(16, 5, ($prod->inicial+$prod->entradas), 0, 0, 'C', $relleno);
        $miReporte->Cell(16, 5, $prod->ventas, 0, 0, 'C', $relleno);
        $miReporte->Cell(16, 5, $prod->final, 0, 0, 'C', $relleno);
        if($prod->mermas=='0'){
            $miReporte->Cell(16, 5, '', 0, 0, 'C', $relleno);
        }else{
            $miReporte->Cell(16, 5, $prod->mermas, 0, 0, 'C', $relleno);
        }
        if($prod->trab=='0'){
            $miReporte->Cell(16, 5, '', 0, 0, 'C', $relleno);
        }else{
            $miReporte->Cell(16, 5, $prod->trab, 0, 0, 'C', $relleno);
        }
        if($prod->donacion=='0'){
            $miReporte->Cell(16, 5, '', 0, 0, 'C', $relleno);
        }else{
            $miReporte->Cell(16, 5, $prod->donacion, 0, 0, 'C', $relleno);
        }    
        if($prod->inst=='0'){
            $miReporte->Cell(14, 5, '', 0, 0, 'C', $relleno);
        }else{
            $miReporte->Cell(14, 5, $prod->inst, 0, 0, 'C', $relleno);
        }
        $miReporte->Cell(18, 5, number_format($ventas,2), 0, 1, 'R', $relleno);
        $this->total = $this->total + $prod->ventast;
        $contador = $contador + 1;
    }

    $relleno = $contador % 2;
    $miReporte->setX(10);
    if($relleno) $miReporte->SetFillColor(240, 240, 240);
    else $miReporte->SetFillColor(255, 255, 255);
    $relleno = true;
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
    $miReporte->Cell(233, 5, 'TOTAL', 0, 0, 'R', $relleno);
    $miReporte->Cell(24, 5, ' $ '.number_format($this->total,2), 0, 1, 'R', $relleno);
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
    $miReporte->setX(10);
    $miReporte->Cell(257, 0, '', 'T', 1, 'L');
    $miReporte->Output();
exit();
?>