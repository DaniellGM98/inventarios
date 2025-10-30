<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";

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
        $this->Cell(65, 10, '', 0, 0, 'C');
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Cell(65, 10, '', 0, 0, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->setX(15);
        $this->Cell(188, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->setX(15);
		$this->Cell(87, 5, 'PRODUCTO', 0, 0, 'L');
		$this->Cell(20, 5, 'PESO UNI', 0, 0, 'C');
		$this->Cell(20, 5, 'PRECIO U', 0, 0, 'C');
		$this->Cell(20, 5, '', 0, 0, 'C');
		$this->Cell(20, 5, 'CANTIDAD', 0, 0, 'C');
		$this->Cell(20, 5, 'IMPORTE', 0, 1, 'R');
        $this->setX(15);
        $this->Cell(188, 0, '', 'T', 0, 'L');
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

    $prods = 0;
    $total = 0;
    foreach($registros as $prod){
        $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
        $relleno = $contador % 2;

        $miReporte->Cell(30, 5, 'Ticket '.$prod->id_venta, 0, 0, 'L', $relleno);
        $miReporte->Cell(40, 5, $prod->fechaf, 0, 0, 'L', $relleno);
        $miReporte->Cell(17, 5, $prod->vendedor, 0, 0, 'L', $relleno);
        $miReporte->Cell(100, 5, $prod->beneficiario, 0, 1, 'R', $relleno);
        
        $contador = $contador + 1;
        $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
        $prods += $prod->num_prod;
        $total += $prod->total;
        foreach($prod->detalles as $deta){
            $relleno = $contador % 2;
            $miReporte->Cell(87, 5, $deta->descripcion, 0, 0, 'L', $relleno);
            $miReporte->Cell(20, 5, number_format($deta->peso,3), 0, 0, 'C', $relleno);
            $miReporte->Cell(20, 5, number_format($deta->precio,2), 0, 0, 'C', $relleno);
            $miReporte->Cell(20, 5, '', 0, 0, 'C', $relleno);
            $miReporte->Cell(20, 5, $deta->cantidad, 0, 0, 'C', $relleno);
            $miReporte->Cell(20, 5, number_format($deta->importe,2), 0, 1, 'R', $relleno);
            $contador = $contador + 1;
        }
        $relleno = $contador % 2;
        $miReporte->Cell(50, 5, $prod->num_prod.' productos', 0, 0, 'L', $relleno);
        $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
        $miReporte->Cell(117, 5, 'TOTAL', 0, 0, 'R', $relleno);
        $miReporte->Cell(20, 5, '$ '.number_format($prod->total,2), 0, 1, 'R', $relleno);
        $contador = $contador + 1;
    }
    
    $miReporte->setX(15);
    $miReporte->Cell(188, 0, '', 'B', 1, 'L');
    $relleno = $contador % 2;
    $miReporte->Cell(50, 5, $prods.' productos', 0, 0, 'L', $relleno);
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
    $miReporte->Cell(117, 5, 'TOTAL', 0, 0, 'R', $relleno);
    $miReporte->Cell(20, 5, '$ '.number_format($total,2), 0, 1, 'R', $relleno);
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
    $miReporte->setX(15);
    $miReporte->Cell(188, 0, '', 'T', 1, 'L');
    $miReporte->Output();
exit();
?>