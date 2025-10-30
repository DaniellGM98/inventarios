<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";
    private $prov = "";

    public function setTitulo($tittle, $inicio, $fin, $prov){
        $this->titulo=$tittle;
        $this->inicio=$inicio;
        $this->fin=$fin;
        $this->prov=$prov;
    }

	function Header(){

        $arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        $arrFe = explode('-', $this->inicio);
        $subtitulo = "Del ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]];
        if(utf8_decode($this->inicio) != utf8_decode($this->fin)){
            $arrFe = explode('-', $this->fin);
	        $subtitulo .= " al ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]]." del ".$arrFe[0];
        }else{
            $subtitulo .= " del ".$arrFe[0];
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'R');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'R');
		$this->Ln(10);
        $this->SetX(15);
        $this->Cell(187, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->Cell(25, 5, 'FECHA', 0, 0, 'C');
        $this->Cell(50, 5, 'PRODUCTO', 0, 0, 'L');
        $this->Cell(22, 5, 'PESO UNIT', 0, 0, 'L');
        $this->Cell(19, 5, 'CANTIDAD', 0, 0, 'C');
        $this->Cell(26, 5, 'TOTAL', 0, 0, 'C');
        $this->Cell(30, 5, 'DESTINO', 0, 1, 'L');
        $this->SetX(15);
        $this->Cell(187, 0, '', 'T', 0, 'C');
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

    $miReporte->setTitulo($vista, $inicio, $fin, $total);
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

    $prov = '';
    $peso = 0;

    foreach($registros as $prod){
        $relleno = $contador % 2;
        
        if ($prov != $prod->proveedor){
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
            if($peso > 0){
                $miReporte->Cell(111, 5, '', 0, 0, 'C', $relleno);
                $miReporte->Cell(25, 5, number_format($peso,3), 0, 0, 'R', $relleno);
                $miReporte->Cell(50, 5, '', 0, 1, 'L', $relleno);
                $contador = $contador + 1;
                $peso = 0;
            }
            $relleno = $contador % 2;
            $miReporte->Cell(186, 5, $prod->proveedor, 0, 1, 'L', $relleno);
            $contador = $contador + 1;
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
            $prov = $prod->proveedor;
            $relleno = $contador % 2;
        }
        
        $miReporte->Cell(18, 5, $prod->fecha, 0, 0, 'C', $relleno);
        $miReporte->Cell(50, 5, $prod->producto, 0, 0, 'L', $relleno);
        $miReporte->Cell(23, 5, number_format($prod->pesou,3), 0, 0, 'C', $relleno);
        $miReporte->Cell(20, 5, $prod->cantidad, 0, 0, 'C', $relleno);
        $miReporte->Cell(25, 5, number_format($prod->peso,3), 0, 0, 'C', $relleno);
        $miReporte->Cell(50, 5, $prod->cliente, 0, 1, 'L', $relleno);
        $peso = $peso + $prod->peso;
        $contador = $contador + 1;
    }

    $relleno = $contador % 2;
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
    $miReporte->Cell(114, 5, '', 0, 0, 'C', $relleno);
    $miReporte->Cell(15, 5, number_format($peso,3), 0, 0, 'R', $relleno);
    $miReporte->Cell(57, 5, '', 0, 1, 'L', $relleno);
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);

    $miReporte->Cell(185, 0, '', 'T', 1, 'L');
    $miReporte->Output();
exit();
?>